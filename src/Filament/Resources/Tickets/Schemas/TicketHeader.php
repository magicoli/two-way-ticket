<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\VerticalAlignment;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * The read-only header shared by the view and edit pages — same fields, same order, same look,
 * defined once.
 *
 * ONE line the way GitHub does it, not a grid of labelled fields:
 * `[Closed] [completed] reported by User Name 25/07/2026 11:50 Closed 26/07/2026 15:47 [GitHub #1]`
 *
 * Everything that isn't a badge is a single text run, composed from the parts that actually have
 * a value — Flex wraps each child in its own gapped <div>, so three separate entries for
 * reporter/reported-at/closed-at left odd holes whenever one of them was empty.
 *
 * Status and state_reason are deliberately NOT editable inputs: changing them will go through a
 * controlled action offering the same dialog from the list, the edit page and the view page alike.
 *
 * Every closure here takes a NULLABLE record: the create page runs this same form schema with no
 * record at all, so a non-nullable hint blows the whole page up. The header hides itself there —
 * there is no status, reporter or date to show for a ticket that doesn't exist yet.
 */
class TicketHeader
{
    public static function make(): Flex
    {
        return Flex::make([
            TextEntry::make('status')
                ->hiddenLabel()
                ->badge()
                ->size(TextSize::Large)
                ->grow(false),
            TextEntry::make('state_reason')
                ->hiddenLabel()
                ->badge()
                ->color('gray')
                ->visible(fn (?Ticket $record): bool => filled($record?->state_reason))
                ->grow(false),
            TextEntry::make('created_at')
                ->hiddenLabel()
                ->state(fn (?Ticket $record): string => $record instanceof Ticket ? self::summary($record) : '')
                ->grow(false),
            TextEntry::make('github_issue_url')
                ->hiddenLabel()
                ->badge()
                ->url(fn (?Ticket $record): ?string => $record?->github_issue_url)
                ->openUrlInNewTab()
                ->formatStateUsing(fn (?string $state, ?Ticket $record): string => __('two-way-ticket::two-way-ticket.field.github').' #'.$record?->github_issue_number)
                ->visible(fn (?Ticket $record): bool => $record?->github_issue_number !== null)
                ->grow(false),
        ])
            ->visible(fn (?Ticket $record): bool => $record?->exists ?? false)
            ->verticalAlignment(VerticalAlignment::Center)
            ->columnSpanFull();
    }

    /**
     * "reported by User Name 25/07/2026 11:50 · Closed 26/07/2026 15:47", minus whichever parts
     * have no value. Dates go through Carbon's isoFormat, the only formatting that puts the
     * day/month in the order the reader's own locale expects — never a hardcoded US-style format.
     */
    private static function summary(Ticket $record): string
    {
        $reporter = $record->user?->name;

        return collect([
            filled($reporter)
                ? __('two-way-ticket::two-way-ticket.issue.reported_by').' '.$reporter
                : null,
            $record->created_at?->isoFormat('lll'),
            $record->closed_at !== null
                ? __('two-way-ticket::two-way-ticket.status.closed').' '.$record->closed_at->isoFormat('lll')
                : null,
        ])->filter()->implode(' · ');
    }
}
