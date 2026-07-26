<?php

use Livewire\Livewire;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

it('hides closed tickets by default, showing them via the Closed/All tabs', function (): void {
    // Oli, 2026-07-26: "Par défaut on doit uniquement afficher les tickets ouverts."
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $open = Ticket::factory()->create(['title' => 'Still open']);
    $closed = Ticket::factory()->closed()->create(['title' => 'Already closed']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$closed]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('activeTab', 'closed')
        ->assertCanSeeTableRecords([$closed])
        ->assertCanNotSeeTableRecords([$open]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$open, $closed]);
});

it('filters by labels and assignees, both ANDed with the active tab', function (): void {
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $match = Ticket::factory()->create(['title' => 'Bug assigned to oli', 'labels' => ['bug'], 'assignees' => ['oli']]);
    $wrongAssignee = Ticket::factory()->create(['title' => 'Bug assigned to someone else', 'labels' => ['bug'], 'assignees' => ['someone-else']]);
    $closedMatch = Ticket::factory()->closed()->create(['title' => 'Closed bug for oli', 'labels' => ['bug'], 'assignees' => ['oli']]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->filterTable('labels', ['values' => ['bug']])
        ->filterTable('assignees', ['values' => ['oli']])
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$wrongAssignee, $closedMatch]);
});
