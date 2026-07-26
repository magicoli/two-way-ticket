<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Actions;

use Magicoli\TwoWayTicket\Models\Ticket;
use Throwable;

/**
 * Set labels / projects / milestone on a ticket, in one place so the row and bulk actions can't
 * drift into two notions of "assign".
 *
 * An omitted (null) value leaves the field alone — that's what makes a bulk assignment usable at
 * all: you tag fifty tickets without wiping the milestone of every one of them.
 */
final class AssignTicketAttributes
{
    /**
     * @param  list<string>|null  $labels
     * @param  list<string>|null  $projects
     * @param  bool  $replace  Multi-value fields are merged with what's already there by default;
     *                         true swaps them for exactly what was passed.
     *
     * @throws Throwable
     */
    public function handle(
        Ticket $ticket,
        ?array $labels = null,
        ?array $projects = null,
        ?string $milestone = null,
        bool $replace = false,
    ): bool {
        $before = [$ticket->labels, $ticket->projects, $ticket->milestone];

        if ($labels !== null) {
            $ticket->labels = self::combine($ticket->labels, $labels, $replace);
        }

        if ($projects !== null) {
            $ticket->projects = self::combine($ticket->projects, $projects, $replace);
        }

        if ($milestone !== null) {
            $ticket->milestone = $milestone;
        }

        if ($before === [$ticket->labels, $ticket->projects, $ticket->milestone]) {
            return false;
        }

        $ticket->save();

        // Labels and milestone are mirrored FROM GitHub on every sync, so a linked ticket has to
        // be pushed or the next sync simply undoes this. (Projects can't be pushed — they're
        // GraphQL-only and this package reads them, so a linked ticket's projects will revert.)
        if ($ticket->isLinked()) {
            resolve(UpdateGithubIssue::class)->handle($ticket);
        }

        return true;
    }

    /**
     * @param  array<int, string>|null  $current
     * @param  list<string>  $incoming
     * @return list<string>
     */
    private static function combine(?array $current, array $incoming, bool $replace): array
    {
        if ($replace) {
            return array_values(array_unique($incoming));
        }

        return array_values(array_unique([...($current ?? []), ...$incoming]));
    }
}
