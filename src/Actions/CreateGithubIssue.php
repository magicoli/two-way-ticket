<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
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

        throw_if(
            $repository === '' || $token === '',
            RuntimeException::class,
            (string) __('two-way-ticket::two-way-ticket.issue.not_configured'),
        );

        $response = Http::withToken($token)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->post(sprintf('https://api.github.com/repos/%s/issues', $repository), $this->payload($ticket))
            ->throw();

        $ticket->forceFill([
            'github_issue_url' => (string) $response->json('html_url'),
            'github_issue_number' => (int) $response->json('number'),
        ])->save();

        // GitHub's create endpoint takes no state: an issue is always born open. A ticket already
        // triaged before it was ever pushed would therefore arrive open, and since status belongs
        // to GitHub once linked, the next sync would reopen it locally — silently undoing the
        // triage. Delegated rather than repeated here: UpdateGithubIssue already sends the state
        // and its reason together.
        if ($ticket->status === TicketStatus::Closed) {
            app(UpdateGithubIssue::class)->handle($ticket);

            return $ticket;
        }

        // Projects are not in the payload above because REST does not carry them at all; they are
        // reconciled through GraphQL. UpdateGithubIssue does it for the closed branch already.
        app(UpdateGithubProjects::class)->handle($ticket);

        return $ticket;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Ticket $ticket): array
    {
        $titlePrefix = config()->string('two-way-ticket.github.title_prefix', '');

        $payload = [
            'title' => $titlePrefix . $ticket->title,
            // The description verbatim, never regenerated: it was composed once at creation
            // (Ticket::composeDescription) and is a plain, two-way-synced field from then on.
            // Generating anything here would drift the moment either side edits it.
            'body' => (string) $ticket->description,
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
}
