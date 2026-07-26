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
 * Genuinely bidirectional (see SPEC.md §8 — the one thing the previous package never did):
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

        throw_if($repository === '' || $token === '', RuntimeException::class, (string) __('two-way-ticket::two-way-ticket.issue.not_configured'));

        $updated = $this->updateLinkedTickets($repository, $token);
        $imported = $this->importUntrackedIssues($repository, $token);

        return ['updated' => $updated, 'imported' => $imported];
    }

    private function updateLinkedTickets(string $repository, string $token): int
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

            if ($this->applyIssueState($ticket, $response->json())) {
                $updated++;
            }
        }

        return $updated;
    }

    private function importUntrackedIssues(string $repository, string $token): int
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

                $this->importIssue($issue);
                $imported++;
            }

            $page++;
        } while (count($issues) === 100);

        return $imported;
    }

    /**
     * @param  array<string, mixed>  $issue
     */
    private function importIssue(array $issue): void
    {
        $ticket = new Ticket([
            'title' => (string) $issue['title'],
            'description' => (string) ($issue['body'] ?? ''),
            'app_version' => '',
            'role' => 'GitHub',
            'labels' => self::names($issue['labels'] ?? [], 'name'),
            'milestone' => $issue['milestone']['title'] ?? null,
            'github_issue_url' => (string) $issue['html_url'],
            'github_issue_number' => (int) $issue['number'],
        ]);

        $this->applyIssueState($ticket, $issue, save: false);

        $ticket->save();
    }

    /**
     * @param  array<string, mixed>  $issue
     */
    private function applyIssueState(Ticket $ticket, array $issue, bool $save = true): bool
    {
        // Oli, 2026-07-26: "on s'aligne à 100% sur le système de GitHub" — a straight mirror of
        // issue.state/state_reason/closed_at/assignees, nothing derived or guessed.
        $newStatus = $issue['state'] === 'closed' ? TicketStatus::Closed : TicketStatus::Open;
        $closedAt = $newStatus === TicketStatus::Closed
            ? CarbonImmutable::parse((string) ($issue['closed_at'] ?? 'now'))
            : null;
        $stateReason = $issue['state_reason'] ?? null;
        $assignees = self::names($issue['assignees'] ?? [], 'login');

        $unchanged = $ticket->status === $newStatus
            && $ticket->state_reason === $stateReason
            && $ticket->assignees === $assignees
            && (($ticket->closed_at === null && $closedAt === null)
                || ($ticket->closed_at !== null && $closedAt !== null && $ticket->closed_at->equalTo($closedAt)));

        if ($unchanged) {
            return false;
        }

        $ticket->status = $newStatus;
        $ticket->closed_at = $closedAt;
        $ticket->state_reason = $stateReason;
        $ticket->assignees = $assignees;

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
        return collect($items)->map(fn (array $item): string => (string) $item[$key])->values()->all();
    }
}
