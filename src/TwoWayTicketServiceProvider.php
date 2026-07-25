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
            ->hasMigration('create_tickets_table')
            ->hasRoute('api');
    }
}
