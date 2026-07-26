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
 * Push a linked ticket's current state back onto its GitHub issue — the outgoing half of the
 * bidirectional sync, triggered on every save (see EditTicket::afterSave()). Body is the
 * description verbatim, both ways: what you see locally is what GitHub has, no regenerated
 * footers that would pile up on imported issues.
 */
final class UpdateGithubIssue
{
    /**
     * @throws RequestException
     * @throws Throwable
     * @throws ConnectionException
     */
    public function handle(Ticket $ticket): Ticket
    {
        if ($ticket->github_issue_number === null) {
            return $ticket;
        }

        $repository = config()->string('two-way-ticket.github.repository', '');
        $token = config()->string('two-way-ticket.github.token', '');

        throw_if($repository === '' || $token === '', RuntimeException::class, (string) __('two-way-ticket::two-way-ticket.issue.not_configured'));

        Http::withToken($token)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->patch(sprintf('https://api.github.com/repos/%s/issues/%d', $repository, $ticket->github_issue_number), [
                'title' => $ticket->title,
                'body' => (string) $ticket->description,
                'labels' => (array) $ticket->labels,
                'assignees' => (array) $ticket->assignees,
                'state' => $ticket->status->value,
            ])
            ->throw();

        return $ticket;
    }
}
