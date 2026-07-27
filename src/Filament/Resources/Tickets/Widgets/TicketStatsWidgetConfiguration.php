<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets;

use Closure;
use Filament\Widgets\WidgetConfiguration;

/**
 * What `TicketStatsWidget::make()` returns, so the widget can be switched on or off where it is
 * registered — `->widgets([TicketStatsWidget::make()->visible($bool)])`.
 *
 * The condition is stored on the WIDGET CLASS, not here, and that is not an oversight: Filament
 * resolves widget visibility statically (`Page::filterVisibleWidgets()` calls
 * `normalizeWidgetClass($widget)::canView()`), so anything kept on this object would never be
 * consulted. One registration per class is the only shape Filament supports anyway.
 */
class TicketStatsWidgetConfiguration extends WidgetConfiguration
{
    /**
     * @param  bool|Closure(): bool  $condition
     */
    public function visible(bool|Closure $condition = true): static
    {
        TicketStatsWidget::visibleWhen($condition);

        return $this;
    }

    /**
     * @param  bool|Closure(): bool  $condition
     */
    public function hidden(bool|Closure $condition = true): static
    {
        TicketStatsWidget::visibleWhen(fn(): bool => !(bool) value($condition));

        return $this;
    }
}
