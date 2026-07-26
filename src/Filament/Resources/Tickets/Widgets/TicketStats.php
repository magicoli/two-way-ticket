<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * The four counts that drive triage, above the list.
 *
 * Every one is a LINK to the matching selection — a number you can't act on just makes you go and
 * rebuild the filter by hand. Three of them are plain tabs, the same mechanism the Open/Closed
 * buttons already use; only "Bugs" needs a filter, because a label isn't a state.
 *
 * The active one is coloured and the others go gray, so the colour says WHICH selection you're
 * looking at rather than merely decorating. Clicking the active one clears back to everything.
 */
class TicketStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $open = fn () => Ticket::query()->where('status', TicketStatus::Open->value);
        $tab = request()->query('tab', 'open');
        /** @var array<string, mixed> $filters */
        $filters = (array) request()->query('filters', []);
        $onBugs = in_array('bug', (array) data_get($filters, 'labels.values', []), true);

        return [
            $this->stat(
                __('two-way-ticket::two-way-ticket.stats.open'),
                $open()->count(),
                TicketStatus::Open->getIcon(),
                TicketStatus::Open->getColor(),
                isActive: $tab === 'open' && ! $onBugs,
                target: ['tab' => 'open'],
            ),
            $this->stat(
                __('two-way-ticket::two-way-ticket.stats.bug'),
                $open()->whereJsonContains('labels', 'bug')->count(),
                'heroicon-o-bug-ant',
                'danger',
                isActive: $onBugs,
                target: ['tab' => 'open', 'filters' => ['labels' => ['values' => ['bug']]]],
            ),
            $this->stat(
                __('two-way-ticket::two-way-ticket.stats.in_progress'),
                // Open AND assigned: a closed ticket someone was on is finished, not in progress.
                $open()->whereJsonLength('assignees', '>', 0)->count(),
                'heroicon-o-play',
                'warning',
                isActive: $tab === 'in_progress',
                target: ['tab' => 'in_progress'],
            ),
            $this->stat(
                __('two-way-ticket::two-way-ticket.stats.closed'),
                Ticket::query()->where('status', TicketStatus::Closed->value)->count(),
                TicketStatus::Closed->getIcon(),
                TicketStatus::Closed->getColor(),
                isActive: $tab === 'closed',
                target: ['tab' => 'closed'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $target
     */
    private function stat(
        string $label,
        int $value,
        string|\BackedEnum $icon,
        string $color,
        bool $isActive,
        array $target,
    ): Stat {
        return Stat::make($label, $value)
            ->icon($icon)
            ->color($isActive ? $color : 'gray')
            // Clicking the active one clears the selection instead of re-applying it.
            ->url(TicketResource::getUrl('index', $isActive ? ['tab' => 'all'] : $target));
    }
}
