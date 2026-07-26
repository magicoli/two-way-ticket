<?php

namespace Magicoli\TwoWayTicket;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TwoWayTicketServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('two-way-ticket')
            ->hasConfigFile('two-way-ticket')
            // Run straight from the package — no publishing step, and no copies in the host app
            // to drift from these. Two things make that work: the timestamp prefix (it's what
            // orders them, an ALTER can't precede its own CREATE) and the plain `.php` extension
            // (Laravel's migrator ignores anything not ending in .php, so a `.stub` would load
            // nothing at all). Publishing still works for anyone who wants their own copies.
            ->runsMigrations()
            ->hasMigrations([
                '2026_07_25_180156_create_tickets_table',
                '2026_07_26_013127_add_github_state_reason_to_tickets_table',
                '2026_07_26_015613_align_tickets_with_github_model',
                '2026_07_26_120228_drop_steps_from_tickets_table',
                '2026_07_26_120229_rename_github_state_reason_to_state_reason',
            ])
            ->hasRoute('api');
    }

    /**
     * Not `->hasTranslations()`: Spatie's package tools expects `resources/lang`, but this
     * package's translations live at plain `lang/` (matching cerealkiller97/filament-bug-reports'
     * own convention) — load them explicitly instead.
     *
     * Publishing is offered purely so a host app can OVERRIDE the wording: Laravel looks in
     * `lang/vendor/two-way-ticket` first and falls back here, so nothing needs publishing for
     * the package to be fully translated out of the box.
     */
    public function packageBooted(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'two-way-ticket');

        $this->publishes(
            [__DIR__.'/../lang' => lang_path('vendor/two-way-ticket')],
            'two-way-ticket-translations',
        );

        // The package's own stylesheet, served by Filament. These pages are generated here, so
        // looking right is this package's job — a host app shouldn't have to add CSS for the
        // selected stat to be visible.
        FilamentAsset::register(
            [Css::make('two-way-ticket', __DIR__.'/../resources/css/two-way-ticket.css')],
            package: 'magicoli/two-way-ticket',
        );
    }
}
