<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Carbon\Laravel\ServiceProvider as CarbonServiceProvider;
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
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStatsWidget;
use Magicoli\TwoWayTicket\ReportIssuePlugin;
use Magicoli\TwoWayTicket\Tests\Fixtures\TestAdminPanelProvider;
use Magicoli\TwoWayTicket\Tests\Fixtures\TestAppPanelProvider;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;
use Magicoli\TwoWayTicket\TicketsPlugin;
use Magicoli\TwoWayTicket\TwoWayTicketServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * `visible()` is remembered on the CLASS, because that is the only place Filament ever looks
     * ({@see \Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStatsWidgetConfiguration}).
     * A class outlives the application each test rebuilds, so without this one test's override
     * would silently decide the next test's answer.
     */
    protected function setUp(): void
    {
        parent::setUp();

        TicketStatsWidget::visibleWhen(null);
        TicketsPlugin::make()->visible(null);
        ReportIssuePlugin::make()->visible(null);
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            // Auto-discovered in any real Laravel app; Testbench doesn't pick it up, and without
            // it Carbon never follows app()->setLocale(), so every isoFormat() silently falls
            // back to English ordering — exactly the bug the date tests are here to catch.
            CarbonServiceProvider::class,
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
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
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
            // Not what a host app necessarily has — it is here so the policy's `is_admin` branch
            // can be exercised by a fixture that carries the attribute and nothing else.
            $table->boolean('is_admin')->nullable();
            $table->timestamps();
        });

        // Nothing to include by hand: the package registers its own migrations
        // (`->runsMigrations()`), so they run here exactly as they do in a host app. Which also
        // means the suite genuinely exercises that registration, rather than a parallel path
        // that could keep passing while the real one is broken.
    }
}
