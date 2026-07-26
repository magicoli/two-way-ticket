<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Tests\Fixtures;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Magicoli\TwoWayTicket\ReportIssuePlugin;

/**
 * A regular, non-triaging panel: ONLY the report button, no browsable ticket list at all — this
 * is the whole point of splitting the two plugins (DEVELOPERS.md §9 / TicketsPlugin's own docblock):
 * a panel like this one never gets TicketResource registered on it, so it can never hit the
 * tenant-scoping bug that broke word-up's project panel with the old, single-plugin package.
 */
class TestAppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->plugin(ReportIssuePlugin::make())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
