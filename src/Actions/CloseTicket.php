<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Actions;

use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Models\Ticket;
use Throwable;

/**
 * Closing a ticket, in one place: the row action, the bulk action and anything added later all go
 * through this, so they can't drift into three slightly different notions of "closed".
 *
 * Pushes to GitHub when the ticket is linked — closing locally while the issue stays open there
 * would just be undone by the next sync.
 */
final class CloseTicket
{
    /**
     * @param  list<string>|null  $labels  Replaces the ticket's labels when given (closing as
     *                                     "duplicate" usually means labelling it as such too).
     *
     * @throws Throwable
     */
    public function handle(Ticket $ticket, string $stateReason, ?array $labels = null): Ticket
    {
        $ticket->status = TicketStatus::Closed;
        $ticket->state_reason = $stateReason;
        $ticket->closed_at ??= now();

        if ($labels !== null) {
            $ticket->labels = $labels;
        }

        $ticket->save();

        if ($ticket->isLinked()) {
            resolve(UpdateGithubIssue::class)->handle($ticket);
        }

        return $ticket;
    }
}
