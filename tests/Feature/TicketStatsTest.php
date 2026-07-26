<?php

use Livewire\Livewire;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStats;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

/**
 * The stats are a Livewire component of their own, so they don't appear in the list page's HTML —
 * a first attempt at this asserted against the page and found nothing, while the widget was
 * rendering perfectly well on its own.
 */
beforeEach(function (): void {
    User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    Ticket::factory()->create(['title' => 'Plain open']);
    Ticket::factory()->create(['title' => 'A bug', 'labels' => ['bug']]);
    Ticket::factory()->create(['title' => 'A bug being worked on', 'labels' => ['bug'], 'assignees' => ['oli']]);
    Ticket::factory()->closed()->create(['title' => 'Done']);
});

it('counts open, bugs, in progress and closed', function (): void {
    $stats = (fn () => $this->getStats())->call(new TicketStats);

    expect(collect($stats)->map(fn ($stat) => $stat->getValue())->all())
        // 3 open (one of them closed and so excluded), 2 of those labelled bug, 1 assigned.
        ->toBe([3, 2, 1, 1]);
});

it('links every stat to the selection it counts', function (): void {
    $user = User::query()->first();

    $html = Livewire::actingAs($user)->test(TicketStats::class)->html();

    expect($html)
        // Oli, 2026-07-26: "il faut que ces blocs soient des liens pour afficher tout de suite la
        // sélection" — a count you can't act on just makes you rebuild the filter by hand.
        ->toContain('tab=open')
        ->toContain('tab=closed')
        ->toContain('filters%5Blabels%5D')
        ->toContain('filters%5Bassigned%5D');
});

it('is registered above the list', function (): void {
    expect((fn () => $this->getHeaderWidgets())->call(new ListTickets))
        ->toContain(TicketStats::class);
});
