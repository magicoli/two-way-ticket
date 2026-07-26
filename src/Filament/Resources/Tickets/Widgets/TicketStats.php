<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * The four counts that drive triage, above the list.
 *
 * These ARE the list's controls — there used to be a row of tabs saying the same thing, and two
 * rows of controls for one choice is one too many.
 *
 * Every one is a LINK to the matching selection: a number you can't act on just makes you go and
 * rebuild the filter by hand. Three of them scope the whole list (see
 * ListTickets::getTableQuery); only "Bugs" goes through a table filter, because a label is not
 * a state.
 *
 * The active one is coloured and carries a check icon while the others go gray, so the colour
 * says WHICH selection you're looking at rather than merely decorating.
 */
class TicketStats extends StatsOverviewWidget
{
    /**
     * NOT lazy, and that's the whole reason the highlight works: a lazy widget renders in a
     * SECOND request of its own, which carries none of the page's query string — so
     * request()->query('view') came back empty every time and the active stat never moved.
     */
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $open = fn () => Ticket::query()->where('status', TicketStatus::Open->value);
        $view = request()->query('view', 'open');
        /** @var array<string, mixed> $filters */
        $filters = (array) request()->query('filters', []);
        $onBugs = in_array('bug', (array) data_get($filters, 'labels.values', []), true);

        return [
            $this->stat(
                __('two-way-ticket::two-way-ticket.stats.open'),
                $open()->count(),
                TicketStatus::Open->getIcon(),
                TicketStatus::Open->getColor(),
                isActive: $view === 'open' && ! $onBugs,
                target: ['view' => 'open'],
            ),
            $this->stat(
                __('two-way-ticket::two-way-ticket.stats.bug'),
                $open()->whereJsonContains('labels', 'bug')->count(),
                'heroicon-o-bug-ant',
                'danger',
                isActive: $onBugs,
                target: ['view' => 'open', 'filters' => ['labels' => ['values' => ['bug']]]],
            ),
            $this->stat(
                __('two-way-ticket::two-way-ticket.stats.in_progress'),
                // Open AND assigned: a closed ticket someone was on is finished, not in progress.
                $open()->whereJsonLength('assignees', '>', 0)->count(),
                'heroicon-o-play',
                'warning',
                isActive: $view === 'in_progress',
                target: ['view' => 'in_progress'],
            ),
            $this->stat(
                __('two-way-ticket::two-way-ticket.stats.closed'),
                Ticket::query()->where('status', TicketStatus::Closed->value)->count(),
                TicketStatus::Closed->getIcon(),
                TicketStatus::Closed->getColor(),
                isActive: $view === 'closed',
                target: ['view' => 'closed'],
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
            // Colour alone was too quiet to read at a glance, and it can't carry the "Closed"
            // stat at all — its own colour IS gray. The check makes the selection unmistakable.
            ->description($isActive ? __('two-way-ticket::two-way-ticket.stats.showing') : null)
            ->descriptionIcon($isActive ? Heroicon::OutlinedCheckCircle : null)
            // Clicking the one you're already on goes back to the default view rather than to
            // "everything" — Open is that default, so it always simply means "show me the open
            // ones" instead of toggling itself off.
            ->url(TicketResource::getUrl('index', $isActive && $target !== ['view' => 'open']
                ? ['view' => 'open']
                : $target));
    }
}
