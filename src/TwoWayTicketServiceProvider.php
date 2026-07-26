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
            ->hasMigrations(['create_tickets_table', 'add_github_state_reason_to_tickets_table'])
            ->hasRoute('api');
    }

    /**
     * Not `->hasTranslations()`: Spatie's package tools expects `resources/lang`, but this
     * package's translations live at plain `lang/` (matching cerealkiller97/filament-bug-reports'
     * own convention) — load them explicitly instead.
     */
    public function packageBooted(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'two-way-ticket');
    }
}
