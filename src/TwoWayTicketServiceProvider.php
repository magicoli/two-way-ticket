<?php

namespace Magicoli\TwoWayTicket;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TwoWayTicketServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('two-way-ticket')
            ->hasConfigFile('two-way-ticket')
            // Timestamped like any Laravel migration: the prefix is what guarantees the order
            // they run in (an ALTER can't precede its own CREATE), and it's the convention every
            // package is expected to follow. Publishing strips and regenerates the prefix anyway.
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
    }
}
