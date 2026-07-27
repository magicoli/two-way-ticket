<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Policies\TicketPolicy;

/**
 * Registers the browsable ticket list/management resource — attach this to whichever panel(s)
 * should actually TRIAGE tickets (typically 'admin' only). Deliberately separate from
 * {@see ReportIssuePlugin}: attaching a Filament Resource to a tenant-scoped panel makes Filament
 * try to auto-scope it by tenant, which breaks for a genuinely global model (confirmed the hard
 * way in word-up, see DEVELOPERS.md's own note on #17/#23) — a panel that only needs the "report an
 * issue" button should never need this plugin at all.
 */
class TicketsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'two-way-ticket';
    }

    /** @var bool|Closure(): bool|null */
    protected static bool|Closure|null $isVisible = null;

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Show or hide the backlog where the plugin is registered:
     *
     *     TicketsPlugin::make()->visible(auth()->user()?->isAdmin() ?? false)
     *
     * Without it, {@see TicketPolicy} decides. Kept on the class because the resource is asked
     * statically, long after this plugin object was configured.
     *
     * Passing null hands the decision back to the policy.
     *
     * @param  bool|Closure(): bool|null  $condition
     */
    public function visible(bool|Closure|null $condition = true): static
    {
        static::$isVisible = $condition;

        return $this;
    }

    /**
     * The condition set at registration, or null when the host app set none.
     */
    public static function isVisible(): ?bool
    {
        return static::$isVisible === null ? null : (bool) value(static::$isVisible);
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TicketResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
