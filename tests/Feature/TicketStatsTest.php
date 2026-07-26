<?php

use Illuminate\Http\Request;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStats;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

/**
 * The stats are a Livewire component of their own, so they never appear in the list page's HTML —
 * a first attempt at this asserted against the page and found nothing while the widget was
 * rendering perfectly well on its own.
 */
function statsFor(array $query = []): array
{
    app()->instance('request', Request::create('/admin/tickets', 'GET', $query));

    return (fn () => $this->getStats())->call(new TicketStats);
}

function statValues(array $query = []): array
{
    return array_map(fn ($stat) => $stat->getValue(), statsFor($query));
}

function statUrls(array $query = []): array
{
    return array_map(fn ($stat) => $stat->getUrl(), statsFor($query));
}

beforeEach(function (): void {
    User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

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
    Ticket::factory()->closed()->create(['title' => 'Closed but assigned', 'assignees' => ['oli']]);

    expect(statValues()[2])->toBe(1);
});

it('links each stat to a tab, the same mechanism the Open/Closed buttons already use', function (): void {
    // From a neutral tab, so none of them is active and each shows its real target rather than
    // the clear-selection link.
    $urls = statUrls(['tab' => 'all']);

    expect($urls[0])->toContain('tab=open')
        ->and($urls[2])->toContain('tab=in_progress')
        ->and($urls[3])->toContain('tab=closed')
        // Only Bugs needs a filter: a label isn't a state, so there's no tab for it.
        ->and($urls[1])->toContain('filters%5Blabels%5D');
});

it('highlights the active stat and greys the rest', function (): void {
    $colours = fn (array $query) => array_map(fn ($stat) => $stat->getColor(), statsFor($query));

    // Closed's own colour IS gray, so it's checked through in_progress, whose colour is distinct.
    expect($colours(['tab' => 'in_progress']))->toBe(['gray', 'gray', 'warning', 'gray'])
        // Default view is the open tab, so Open is the one lit up.
        ->and($colours([])[0])->toBe('success');
});

it('clears the selection when the active stat is clicked again', function (): void {
    expect(statUrls(['tab' => 'in_progress'])[2])->toContain('tab=all')
        ->and(statUrls(['tab' => 'closed'])[3])->toContain('tab=all');
});

it('is registered above the list', function (): void {
    expect((fn () => $this->getHeaderWidgets())->call(new ListTickets))
        ->toContain(TicketStats::class);
});
