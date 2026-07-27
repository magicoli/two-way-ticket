<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Models\Ticket;
use RuntimeException;
use Throwable;

/**
 * Genuinely bidirectional (see DEVELOPERS.md §8 — the one thing the previous package never did):
 *
 * 1. Every ticket ALREADY linked to a GitHub issue gets its status mirrored from the issue's
 *    real open/closed state, same as before.
 * 2. Every issue in the configured repo that has NO matching local ticket gets imported as a new
 *    one, linked from creation — a ticket no longer has to be opened from THIS app to exist here.
 */
final class SyncGithubIssues
{
    /**
     * @return array{updated: int, imported: int}
     *
     * @throws RequestException
     * @throws Throwable
     * @throws ConnectionException
     */
    public function handle(): array
    {
        $repository = config()->string('two-way-ticket.github.repository', '');
        $token = config()->string('two-way-ticket.github.token', '');

        throw_if(
            $repository === '' || $token === '',
            RuntimeException::class,
            (string) __('two-way-ticket::two-way-ticket.issue.not_configured'),
        );

        $projects = $this->fetchProjectsByIssue($repository, $token);

        $updated = $this->updateLinkedTickets($repository, $token, $projects);
        $imported = $this->importUntrackedIssues($repository, $token, $projects);

        return ['updated' => $updated, 'imported' => $imported];
    }

    /**
     * GitHub Projects (v2) exist ONLY in the GraphQL API — the REST issue payload has no
     * `projects` key at all — and reading them needs a token carrying the `read:project` scope on
     * top of `repo`. Fetched for the whole repo in one paginated query rather than per issue.
     *
     * Returns null when projects can't be read (scope missing, GraphQL error, network failure).
     * That's deliberately distinct from an empty array: null means "unknown, leave whatever is
     * stored alone", so a token without the scope degrades to ignoring projects instead of
     * silently wiping every ticket's.
     *
     * @return array<int, list<string>>|null
     */
    private function fetchProjectsByIssue(string $repository, string $token): ?array
    {
        [$owner, $name] = array_pad(explode('/', $repository, 2), 2, '');

        if ($owner === '' || $name === '') {
            return null;
        }

        $query = <<<'GRAPHQL'
            query ($owner: String!, $name: String!, $after: String) {
                repository(owner: $owner, name: $name) {
                    issues(first: 100, after: $after, states: [OPEN, CLOSED]) {
                        pageInfo { hasNextPage endCursor }
                        nodes {
                            number
                            projectItems(first: 20) { nodes { project { title } } }
                        }
                    }
                }
            }
            GRAPHQL;

        $projects = [];
        $after = null;

        do {
            try {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->post('https://api.github.com/graphql', [
                        'query' => $query,
                        'variables' => ['owner' => $owner, 'name' => $name, 'after' => $after],
                    ]);
            } catch (Throwable) {
                return null;
            }

            // GraphQL answers 200 with an `errors` array — INSUFFICIENT_SCOPES lands here.
            if ($response->failed() || filled($response->json('errors'))) {
                return null;
            }

            $issues = $response->json('data.repository.issues');

            if (!is_array($issues)) {
                return null;
            }

            foreach ($issues['nodes'] ?? [] as $issue) {
                $projects[(int) $issue['number']] = self::names(
                    array_column($issue['projectItems']['nodes'] ?? [], 'project'),
                    'title',
                );
            }

            $after = $issues['pageInfo']['endCursor'] ?? null;
        } while (($issues['pageInfo']['hasNextPage'] ?? false) && $after !== null);

        return $projects;
    }

    /**
     * @param  array<int, list<string>>|null  $projects
     */
    private function updateLinkedTickets(string $repository, string $token, ?array $projects): int
    {
        $tickets = Ticket::query()->whereNotNull('github_issue_number')->get();
        $updated = 0;

        foreach ($tickets as $ticket) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
                ->get(sprintf('https://api.github.com/repos/%s/issues/%d', $repository, $ticket->github_issue_number));

            // 404/410: the issue is gone or unreachable — nothing left to mirror.
            if (in_array($response->status(), [404, 410], true)) {
                continue;
            }

            $response->throw();

            if ($this->applyIssueState($ticket, $response->json(), projects: $projects)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * @param  array<int, list<string>>|null  $projects
     */
    private function importUntrackedIssues(string $repository, string $token, ?array $projects): int
    {
        $knownNumbers = Ticket::query()->whereNotNull('github_issue_number')->pluck('github_issue_number')->all();
        $imported = 0;
        $page = 1;

        do {
            $response = Http::withToken($token)
                ->acceptJson()
                ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
                ->get(sprintf('https://api.github.com/repos/%s/issues', $repository), [
                    'state' => 'all',
                    'per_page' => 100,
                    'page' => $page,
                ])
                ->throw();

            /** @var list<array<string, mixed>> $issues */
            $issues = $response->json();

            foreach ($issues as $issue) {
                // GitHub's issues endpoint also returns pull requests — skip those.
                if (array_key_exists('pull_request', $issue)) {
                    continue;
                }

                if (in_array($issue['number'], $knownNumbers, true)) {
                    continue;
                }

                $this->importIssue($issue, $projects);
                $imported++;
            }

            $page++;
        } while (count($issues) === 100);

        return $imported;
    }

    /**
     * @param  array<string, mixed>  $issue
     * @param  array<int, list<string>>|null  $projects
     */
    private function importIssue(array $issue, ?array $projects): void
    {
        // title/description/labels/milestone aren't listed here because applyIssueState() sets
        // them, one line below, for imports and updates alike — it owns every GitHub-mirrored
        // field so there's a single place to look. They ARE synced; they're just not set twice.
        $ticket = new Ticket([
            'app_version' => '',
            'role' => 'GitHub',
            'github_issue_url' => (string) $issue['html_url'],
            'github_issue_number' => (int) $issue['number'],
        ]);

        $this->applyIssueState($ticket, $issue, save: false, projects: $projects);

        $ticket->save();
    }

    /**
     * @param  array<string, mixed>  $issue
     * @param  array<int, list<string>>|null  $projects
     */
    private function applyIssueState(Ticket $ticket, array $issue, bool $save = true, ?array $projects = null): bool
    {
        // Oli, 2026-07-26: "on s'aligne à 100% sur le système de GitHub" — a straight mirror of
        // issue.title/state/state_reason/closed_at/labels/milestone/assignees/projects, nothing
        // derived or guessed. Mirroring means REPLACING, not merging: a label removed on GitHub
        // has to disappear here too, which a merge would never do.
        //
        // Safe for the title because every local edit is pushed to GitHub as it's saved (see
        // EditTicket::afterSave), so GitHub always holds the newest version — "celle qui a été
        // modifiée en dernier". The one exception is a push that FAILED: the user gets a
        // persistent error, but the next sync will restore GitHub's older title over their edit.
        // Keeps the current title if the payload somehow carries none — mirroring must never
        // blank a field just because a key was missing.
        $title = (string) ($issue['title'] ?? $ticket->title ?? '');
        $description = array_key_exists('body', $issue) ? (string) $issue['body'] : $ticket->description;
        $newStatus = $issue['state'] === 'closed' ? TicketStatus::Closed : TicketStatus::Open;
        $closedAt = $newStatus === TicketStatus::Closed
            ? CarbonImmutable::parse((string) ($issue['closed_at'] ?? 'now'))
            : null;
        $stateReason = $issue['state_reason'] ?? null;
        $labels = self::names($issue['labels'] ?? [], 'name');
        $milestone = $issue['milestone']['title'] ?? null;
        $assignees = self::names($issue['assignees'] ?? [], 'login');
        // null means projects couldn't be read at all — keep whatever is stored rather than wipe it.
        $newProjects = $projects === null ? $ticket->projects : $projects[(int) $issue['number']] ?? [];

        $unchanged =
            $ticket->title === $title
            && $ticket->description === $description
            && $ticket->status === $newStatus
            && $ticket->state_reason === $stateReason
            && $ticket->labels === $labels
            && $ticket->milestone === $milestone
            && $ticket->assignees === $assignees
            && $ticket->projects === $newProjects
            && (
                $ticket->closed_at === null
                && $closedAt === null
                || $ticket->closed_at !== null
                && $closedAt !== null
                && $ticket->closed_at->equalTo($closedAt)
            );

        if ($unchanged) {
            return false;
        }

        $ticket->title = $title;
        $ticket->description = $description;
        $ticket->status = $newStatus;
        $ticket->closed_at = $closedAt;
        $ticket->state_reason = $stateReason;
        $ticket->labels = $labels;
        $ticket->milestone = $milestone;
        $ticket->assignees = $assignees;
        $ticket->projects = $newProjects;

        if ($save) {
            $ticket->save();
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<string>
     */
    private static function names(array $items, string $key): array
    {
        return collect($items)
            ->map(fn(array $item): string => (string) $item[$key])
            ->values()
            ->all();
    }
}
