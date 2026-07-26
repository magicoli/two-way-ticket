<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Magicoli\TwoWayTicket\Models\Ticket;
use RuntimeException;
use Throwable;

/**
 * Push a ticket to GitHub as a real issue, then store the reference back onto it. Idempotent: a
 * ticket that already has an issue is returned untouched. Oli, 2026-07-26: "on choisit
 * manuellement de lier ou pas une issue locale à GitHub. Si on la lie, peu importe son ou ses
 * labels, l'issue est synchronisée" — linking is itself the gate, nothing label-based on top.
 */
final class CreateGithubIssue
{
    /**
     * @throws RequestException
     * @throws Throwable
     * @throws ConnectionException
     */
    public function handle(Ticket $ticket): Ticket
    {
        if ($ticket->github_issue_url !== null) {
            return $ticket;
        }

        $repository = config()->string('two-way-ticket.github.repository', '');
        $token = config()->string('two-way-ticket.github.token', '');

        throw_if($repository === '' || $token === '', RuntimeException::class, (string) __('two-way-ticket::two-way-ticket.issue.not_configured'));

        $response = Http::withToken($token)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->post(sprintf('https://api.github.com/repos/%s/issues', $repository), $this->payload($ticket))
            ->throw();

        $ticket->forceFill([
            'github_issue_url' => (string) $response->json('html_url'),
            'github_issue_number' => (int) $response->json('number'),
        ])->save();

        return $ticket;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Ticket $ticket): array
    {
        $titlePrefix = config()->string('two-way-ticket.github.title_prefix', '');

        $payload = [
            'title' => $titlePrefix.$ticket->title,
            'body' => $this->body($ticket),
            'labels' => (array) $ticket->labels,
        ];

        if ($ticket->milestone !== null) {
            $payload['milestone'] = $ticket->milestone;
        }

        if (filled($ticket->assignees)) {
            $payload['assignees'] = $ticket->assignees;
        }

        return $payload;
    }

    private function body(Ticket $ticket): string
    {
        $reporterName = $ticket->user?->getAttribute('name');
        $reporter = is_string($reporterName) && $reporterName !== '' ? $reporterName : (string) __('two-way-ticket::two-way-ticket.issue.unknown_reporter');

        if ($ticket->role !== '') {
            $reporter .= ' ('.$ticket->role.')';
        }

        return implode("\n", array_filter([
            $ticket->description,
            $ticket->description !== null ? '' : null,
            '**'.__('two-way-ticket::two-way-ticket.issue.reported_by').':** '.$reporter,
            '**'.__('two-way-ticket::two-way-ticket.issue.app_version').':** '.$ticket->app_version,
            $ticket->page_url !== null ? '**'.__('two-way-ticket::two-way-ticket.issue.page_url').':** '.$ticket->page_url : null,
            '',
            '---',
            (string) __('two-way-ticket::two-way-ticket.issue.footer', ['id' => $ticket->id]),
        ], fn (?string $line): bool => $line !== null));
    }
}
