<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;

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

    public static function make(): static
    {
        return app(static::class);
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
