<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Tests;

use Illuminate\Foundation\Application;
use Magicoli\TwoWayTicket\TwoWayTicketServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TwoWayTicketServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('two-way-ticket.api.token', 'test-token');
        $app['config']->set('two-way-ticket.github.token', 'test-github-token');
        $app['config']->set('two-way-ticket.github.repository', 'example/example');
    }

    protected function defineDatabaseMigrations(): void
    {
        (include __DIR__.'/../database/migrations/create_tickets_table.php.stub')->up();
    }
}
