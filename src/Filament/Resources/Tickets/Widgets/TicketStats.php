<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * The four counts that actually drive triage, above the list.
 *
 * Every one of them is a LINK to the matching selection — a number you can't act on just makes
 * you go and rebuild the filter by hand. Filament exposes the list's own state in the query
 * string (`?tab=`, `?filters[...]`), so these are ordinary URLs, not a parallel filtering scheme.
 */
class TicketStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $open = Ticket::query()->where('status', TicketStatus::Open->value);

        return [
            Stat::make(__('two-way-ticket::two-way-ticket.stats.open'), $open->clone()->count())
                ->icon(TicketStatus::Open->getIcon())
                ->color(TicketStatus::Open->getColor())
                ->url(TicketResource::getUrl('index', ['tab' => 'open'])),

            Stat::make(
                __('two-way-ticket::two-way-ticket.stats.bug'),
                $open->clone()->whereJsonContains('labels', 'bug')->count(),
            )
                ->icon('heroicon-o-bug-ant')
                ->color('danger')
                ->url(TicketResource::getUrl('index', [
                    'tab' => 'open',
                    'filters' => ['labels' => ['values' => ['bug']]],
                ])),

            // "Assigned to someone" is the closest thing to in-progress we can honestly report:
            // GitHub has no in-progress state, and inventing one locally is exactly what this
            // package refuses to do.
            Stat::make(
                __('two-way-ticket::two-way-ticket.stats.in_progress'),
                $open->clone()->whereJsonLength('assignees', '>', 0)->count(),
            )
                ->icon('heroicon-o-play')
                ->color('warning')
                ->url(TicketResource::getUrl('index', [
                    'tab' => 'open',
                    'filters' => ['assigned' => ['isActive' => true]],
                ])),

            Stat::make(
                __('two-way-ticket::two-way-ticket.stats.closed'),
                Ticket::query()->where('status', TicketStatus::Closed->value)->count(),
            )
                ->icon(TicketStatus::Closed->getIcon())
                ->color('gray')
                ->url(TicketResource::getUrl('index', ['tab' => 'closed'])),
        ];
    }
}
