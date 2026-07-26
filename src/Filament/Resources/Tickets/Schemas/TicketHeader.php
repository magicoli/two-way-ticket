<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * The read-only header shared by the view and edit pages — same fields, same order, same look,
 * defined once. Status and state_reason are deliberately NOT editable inputs here: changing them
 * will go through a controlled action offering the same dialog from the list, the edit page and
 * the view page alike.
 */
class TicketHeader
{
    public static function make(): Group
    {
        return Group::make([
            TextEntry::make('status')->label(__('two-way-ticket::two-way-ticket.field.status'))->badge(),
            TextEntry::make('state_reason')
                ->label(__('two-way-ticket::two-way-ticket.field.state_reason'))
                ->badge(),
            TextEntry::make('user.name')->label(__('two-way-ticket::two-way-ticket.field.reported_by')),
            TextEntry::make('created_at')
                ->label(__('two-way-ticket::two-way-ticket.field.reported_at'))
                ->dateTime(),
            TextEntry::make('closed_at')
                ->label(__('two-way-ticket::two-way-ticket.field.closed_at'))
                ->dateTime(),
            TextEntry::make('github_issue_url')
                ->label(__('two-way-ticket::two-way-ticket.field.github_issue'))
                ->badge()
                ->url(fn (Ticket $record): ?string => $record->github_issue_url)
                ->openUrlInNewTab()
                ->formatStateUsing(fn (?string $state, Ticket $record): ?string => $record->github_issue_number !== null
                    ? '#'.$record->github_issue_number
                    : null),
        ])->columnSpanFull()->columns(6);
    }
}
