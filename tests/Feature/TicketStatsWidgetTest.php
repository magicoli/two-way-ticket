<?php

use Illuminate\Http\Request;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStatsWidget;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\AdminUser;

/**
 * The stats are a Livewire component of their own, so they never appear in the list page's HTML —
 * a first attempt at this asserted against the page and found nothing while the widget was
 * rendering perfectly well on its own.
 */
function statsFor(array $query = []): array
{
    app()->instance('request', Request::create('/admin/tickets', 'GET', $query));

    return (fn() => $this->getStats())->call(new TicketStatsWidget());
}

function statValues(array $query = []): array
{
    return array_map(fn($stat) => $stat->getValue(), statsFor($query));
}

function statUrls(array $query = []): array
{
    return array_map(fn($stat) => $stat->getUrl(), statsFor($query));
}

beforeEach(function (): void {
    AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    Ticket::factory()->create(['title' => 'Plain open']);
    Ticket::factory()->create(['title' => 'A bug', 'labels' => ['bug']]);
    Ticket::factory()->create(['title' => 'Being worked on', 'labels' => ['bug'], 'assignees' => ['oli']]);
    Ticket::factory()->closed()->create(['title' => 'Done']);
});

it('counts open, bugs, in progress and closed', function (): void {
    // 3 open, 2 of them labelled bug, 1 of those assigned; 1 closed.
    expect(statValues())->toBe([3, 2, 1, 1]);
});

it('does not count a closed ticket as in progress just because someone was assigned', function (): void {
    // Oli, 2026-07-26, verified in the UI: "assigné une issue fermée, et elle est comptée comme
    // in progress". In progress is a SUBSET of open — a finished ticket isn't in progress.
    Ticket::factory()
        ->closed()
        ->create(['title' => 'Closed but assigned', 'assignees' => ['oli']]);

    expect(statValues()[2])->toBe(1);
});

it('links each stat to a tab, the same mechanism the Open/Closed buttons already use', function (): void {
    // From a neutral tab, so none of them is active and each shows its real target rather than
    // the clear-selection link.
    $urls = statUrls(['view' => 'all']);

    expect($urls[0])
        ->toContain('view=open')
        ->and($urls[2])
        ->toContain('view=in_progress')
        ->and($urls[3])
        ->toContain('view=closed')
        // Only Bugs needs a filter: a label isn't a state, so there's no tab for it.
        ->and($urls[1])
        ->toContain('filters%5Blabels%5D');
});

it('highlights the active stat and greys the rest', function (): void {
    $colours = fn(array $query) => array_map(fn($stat) => $stat->getColor(), statsFor($query));

    // Closed's own colour IS gray, so it's checked through in_progress, whose colour is distinct.
    expect($colours(['view' => 'in_progress']))
        ->toBe(['gray', 'gray', 'warning', 'gray'])
        // Default view is the open tab, so Open is the one lit up.
        ->and($colours([])[0])
        ->toBe('success');
});

it('widens to everything when the active stat is clicked again, Open included', function (): void {
    // Oli, 2026-07-26: "Open est l'affichage par défaut, mais uniquement quand on arrive sur la
    // page. On doit pouvoir le désactiver aussi (pour tout afficher justement)."
    expect(statUrls(['view' => 'in_progress'])[2])
        ->toContain('view=all')
        ->and(statUrls(['view' => 'closed'])[3])
        ->toContain('view=all')
        // On arrival, Open is the active one, so it offers the way out.
        ->and(statUrls([])[0])
        ->toContain('view=all');
});

it('renders inside the page request, or the highlight can never move', function (): void {
    // A lazy widget loads in a second request that carries none of the page's query string, so
    // request()->query('view') came back empty every time and the active stat stayed put.
    expect(TicketStatsWidget::isLazy())->toBeFalse();
});

it('marks the active stat with more than a colour', function (): void {
    // Colour alone can't carry Closed — its own colour IS gray — and the small caption wasn't
    // enough either, so the card itself gets a class the host app tints (see README).
    $classes = fn(array $query) => array_map(fn($stat) => $stat->getExtraAttributes()['class'] ?? '', statsFor($query));

    expect($classes(['view' => 'closed'])[3])
        ->toBe('twt-stat-active')
        ->and($classes(['view' => 'closed'])[0])
        ->toBe('');
});

it('is registered above the list', function (): void {
    expect((fn() => $this->getHeaderWidgets())->call(new ListTickets()))->toContain(TicketStatsWidget::class);
});

/**
 * filament:optimize caches each panel's registered widgets with var_export(); a registered
 * TicketStatsWidget::make() is an object, so it emits ClassName::__set_state([...]). Without that
 * method the cached panel fataled on load, 500-ing every request. Round-trip through var_export()
 * exactly as the cache does.
 */
it('survives the var_export round-trip filament:optimize uses to cache panels', function (): void {
    $config = TicketStatsWidget::make();

    $restored = eval('return ' . var_export($config, true) . ';');

    expect($restored)
        ->toBeInstanceOf(\Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStatsWidgetConfiguration::class)
        ->and($restored->widget)
        ->toBe(TicketStatsWidget::class);
});
