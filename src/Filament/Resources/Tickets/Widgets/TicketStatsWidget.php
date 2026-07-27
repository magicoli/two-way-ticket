<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets;

use Closure;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;
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
 * The active one is coloured while the others go gray, and its card is tinted by the host app
 * through `twt-stat-active` — a caption saying "showing" was too quiet to notice.
 */
class TicketStatsWidget extends StatsOverviewWidget
{
    /**
     * NOT lazy, and that's the whole reason the highlight works: a lazy widget renders in a
     * SECOND request of its own, which carries none of the page's query string — so
     * request()->query('view') came back empty every time and the active stat never moved.
     */
    protected static bool $isLazy = false;

    /** @var bool|Closure(): bool|null */
    protected static bool|Closure|null $isVisible = null;

    /**
     * Show or hide it where it is registered:
     *
     *     ->widgets([TicketStatsWidget::make()->visible($bool)])
     *
     * Kept on the class because Filament asks the class, never the registration
     * ({@see TicketStatsWidgetConfiguration}).
     *
     * Passing null hands the decision back to the policy.
     *
     * @param  bool|Closure(): bool|null  $condition
     */
    public static function visibleWhen(bool|Closure|null $condition): void
    {
        static::$isVisible = $condition;
    }

    public static function make(array $properties = []): TicketStatsWidgetConfiguration
    {
        return new TicketStatsWidgetConfiguration(static::class, $properties);
    }

    /**
     * An explicit condition wins; without one, the same right as the list this summarises.
     */
    public static function canView(): bool
    {
        if (static::$isVisible !== null) {
            return (bool) value(static::$isVisible);
        }

        return Gate::allows('viewAny', Ticket::class);
    }

    protected function getStats(): array
    {
        $open = fn() => Ticket::query()->where('status', TicketStatus::Open->value);
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
                isActive: $view === 'open' && !$onBugs,
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
    private function stat(string $label, int $value, string $icon, string $color, bool $isActive, array $target): Stat
    {
        return Stat::make($label, $value)
            ->icon($icon)
            ->color($isActive ? $color : 'gray')
            // Filament hardcodes the stat card's background, so no built-in class can tint it:
            // the package marks the active card and the host app styles it (see the README).
            // Tailwind utilities like `bg-primary-500` do nothing from here: nothing compiles
            // them — they're absent from Filament's own CSS and a host theme only scans its own
            // sources, never this package. Hence a real class, styled by the stylesheet the
            // package ships (resources/css/two-way-ticket.css).
            ->extraAttributes($isActive ? ['class' => 'twt-stat-active'] : [])
            // Every stat toggles, Open included: it's the default view on ARRIVAL, not a state
            // you're stuck in, so clicking the active one always widens to everything.
            ->url(TicketResource::getUrl('index', $isActive ? ['view' => 'all'] : $target));
    }
}
