<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Support\Enums\VerticalAlignment;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * The read-only header shared by the view and edit pages — same fields, same order, same look,
 * defined once.
 *
 * Laid out as ONE formatted line the way GitHub does it, not a grid of labelled fields:
 * `[Closed] [completed] reported by User Name 26/07/2026 15:43 Closed 26/07/2026 15:47 [GitHub #1]`
 * Status, reason and the issue link are badges; the wording that would have been a label is a
 * prefix instead, and every optional part disappears entirely when it has no value.
 *
 * Status and state_reason are deliberately NOT editable inputs: changing them will go through a
 * controlled action offering the same dialog from the list, the edit page and the view page alike.
 */
class TicketHeader
{
    public static function make(): Flex
    {
        return Flex::make([
            TextEntry::make('status')
                ->hiddenLabel()
                ->badge()
                ->grow(false),
            TextEntry::make('state_reason')
                ->hiddenLabel()
                ->badge()
                ->visible(fn (Ticket $record): bool => filled($record->state_reason))
                ->grow(false),
            TextEntry::make('user.name')
                ->hiddenLabel()
                ->prefix(__('two-way-ticket::two-way-ticket.issue.reported_by').' ')
                ->visible(fn (Ticket $record): bool => filled($record->user?->name))
                ->grow(false),
            TextEntry::make('created_at')
                ->hiddenLabel()
                ->dateTime()
                ->grow(false),
            TextEntry::make('closed_at')
                ->hiddenLabel()
                ->prefix(__('two-way-ticket::two-way-ticket.status.closed').' ')
                ->dateTime()
                ->visible(fn (Ticket $record): bool => filled($record->closed_at))
                ->grow(false),
            TextEntry::make('github_issue_url')
                ->hiddenLabel()
                ->badge()
                ->url(fn (Ticket $record): ?string => $record->github_issue_url)
                ->openUrlInNewTab()
                ->formatStateUsing(fn (?string $state, Ticket $record): string => __('two-way-ticket::two-way-ticket.field.github').' #'.$record->github_issue_number)
                ->visible(fn (Ticket $record): bool => $record->github_issue_number !== null)
                ->grow(false),
        ])
            ->verticalAlignment(VerticalAlignment::Center)
            ->columnSpanFull();
    }
}
