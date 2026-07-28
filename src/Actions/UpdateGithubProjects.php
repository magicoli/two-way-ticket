<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Magicoli\TwoWayTicket\Models\Ticket;
use Throwable;

/**
 * Reconcile a linked ticket's Projects with its GitHub issue.
 *
 * Separate from {@see UpdateGithubIssue} because Projects (v2) simply do not exist in the REST
 * API: membership is added and removed through GraphQL mutations, on an issue identified by its
 * node id rather than its number. Everything else about an issue goes through REST.
 *
 * Never throws. A save must not fail because a token lacks the `project` scope or because GitHub
 * is having a bad day — the failure is logged and the local value stands, matching how the
 * incoming sync treats unreadable projects as unknown rather than empty.
 */
final class UpdateGithubProjects
{
    public function handle(Ticket $ticket): Ticket
    {
        if ($ticket->github_issue_number === null) {
            return $ticket;
        }

        // Nothing to reconcile for a ticket that names no project and never did: asking GitHub
        // what it belongs to would cost a query on every save to learn there is nothing to remove.
        // `wasChanged` is what catches the case that matters — the last project just taken off.
        if (blank($ticket->projects) && !$ticket->wasChanged('projects')) {
            return $ticket;
        }

        $repository = config()->string('two-way-ticket.github.repository', '');
        $token = config()->string('two-way-ticket.github.token', '');

        if ($repository === '' || $token === '') {
            return $ticket;
        }

        [$owner, $name] = array_pad(explode('/', $repository, 2), 2, '');

        try {
            $issue = $this->issueWithItems($token, $owner, $name, $ticket->github_issue_number);

            if ($issue === null) {
                return $ticket;
            }

            $desired = array_values(array_unique((array) $ticket->projects));
            $current = [];

            foreach ($issue['projectItems']['nodes'] ?? [] as $item) {
                $current[(string) ($item['project']['title'] ?? '')] = $item;
            }

            foreach (array_diff(array_keys($current), $desired) as $title) {
                $this->mutate(
                    $token,
                    <<<'GRAPHQL'
                        mutation($project: ID!, $item: ID!) {
                            deleteProjectV2Item(input: {projectId: $project, itemId: $item}) {
                                deletedItemId
                            }
                        }
                        GRAPHQL,
                    [
                        'project' => $current[$title]['project']['id'],
                        'item' => $current[$title]['id'],
                    ],
                );
            }

            $missing = array_diff($desired, array_keys($current));

            if ($missing === []) {
                return $ticket;
            }

            $available = $this->projectsOfOwner($token, $owner);

            foreach ($missing as $title) {
                if (!isset($available[$title])) {
                    Log::warning('[two-way-ticket] No GitHub project named "' . $title . '" for ' . $owner);

                    continue;
                }

                $this->mutate(
                    $token,
                    <<<'GRAPHQL'
                        mutation($project: ID!, $content: ID!) {
                            addProjectV2ItemById(input: {projectId: $project, contentId: $content}) {
                                item { id }
                            }
                        }
                        GRAPHQL,
                    [
                        'project' => $available[$title],
                        'content' => $issue['id'],
                    ],
                );
            }
        } catch (Throwable $e) {
            Log::warning(
                '[two-way-ticket] Could not sync projects for issue #' . $ticket->github_issue_number . ': '
                    . $e->getMessage(),
            );
        }

        return $ticket;
    }

    /**
     * The issue's node id and the project items it already belongs to — one query, because both
     * are needed and both come from the same node.
     *
     * @return array<string, mixed>|null
     */
    private function issueWithItems(string $token, string $owner, string $name, int $number): ?array
    {
        $data = $this->query(
            $token,
            <<<'GRAPHQL'
                query($owner: String!, $name: String!, $number: Int!) {
                    repository(owner: $owner, name: $name) {
                        issue(number: $number) {
                            id
                            projectItems(first: 50) {
                                nodes { id project { id title } }
                            }
                        }
                    }
                }
                GRAPHQL,
            ['owner' => $owner, 'name' => $name, 'number' => $number],
        );

        $issue = data_get($data, 'repository.issue');

        return is_array($issue) ? $issue : null;
    }

    /**
     * Projects belong to the account, not to the repository, so they are looked up on the owner —
     * `repositoryOwner` covers a user and an organisation alike.
     *
     * @return array<string, string> title => node id
     */
    private function projectsOfOwner(string $token, string $owner): array
    {
        $data = $this->query(
            $token,
            <<<'GRAPHQL'
                query($owner: String!) {
                    repositoryOwner(login: $owner) {
                        ... on ProjectV2Owner {
                            projectsV2(first: 50) { nodes { id title } }
                        }
                    }
                }
                GRAPHQL,
            ['owner' => $owner],
        );

        $projects = [];

        foreach (data_get($data, 'repositoryOwner.projectsV2.nodes') ?? [] as $project) {
            $projects[(string) $project['title']] = (string) $project['id'];
        }

        return $projects;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>|null
     */
    private function query(string $token, string $query, array $variables): ?array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->post('https://api.github.com/graphql', ['query' => $query, 'variables' => $variables]);

        // GraphQL answers 200 with an `errors` array — INSUFFICIENT_SCOPES lands there, not in the
        // status code.
        if ($response->failed() || filled($response->json('errors'))) {
            Log::warning('[two-way-ticket] GitHub Projects query refused: ' . $response->body());

            return null;
        }

        return $response->json('data');
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function mutate(string $token, string $mutation, array $variables): void
    {
        $this->query($token, $mutation, $variables);
    }
}
