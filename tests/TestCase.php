<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Magicoli\TwoWayTicket\Tests\Fixtures\TestAdminPanelProvider;
use Magicoli\TwoWayTicket\Tests\Fixtures\TestAppPanelProvider;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;
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
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            // Order matters — see cerealkiller97/filament-bug-reports' own TestCase docblock:
            // Filament's SupportServiceProvider must register before Livewire's, or Livewire's
            // DataStore singleton gets dropped and every render blows up.
            SupportServiceProvider::class,
            LivewireServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            TwoWayTicketServiceProvider::class,
            TestAdminPanelProvider::class,
            TestAppPanelProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('two-way-ticket.user_model', User::class);
        $app['config']->set('two-way-ticket.api.token', 'test-token');
        $app['config']->set('two-way-ticket.github.token', 'test-github-token');
        $app['config']->set('two-way-ticket.github.repository', 'example/example');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        (include __DIR__.'/../database/migrations/create_tickets_table.php.stub')->up();
        (include __DIR__.'/../database/migrations/add_github_state_reason_to_tickets_table.php.stub')->up();
        (include __DIR__.'/../database/migrations/align_tickets_with_github_model.php.stub')->up();
        (include __DIR__.'/../database/migrations/drop_steps_from_tickets_table.php.stub')->up();
        (include __DIR__.'/../database/migrations/rename_github_state_reason_to_state_reason.php.stub')->up();
    }
}
