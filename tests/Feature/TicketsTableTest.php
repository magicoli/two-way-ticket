<?php

use Livewire\Livewire;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

it('hides resolved tickets by default, but shows them one filter click away', function (): void {
    // Oli, 2026-07-26: "Par défaut on doit uniquement afficher les tickets ouverts/non résolus."
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $open = Ticket::factory()->create(['title' => 'Still open']);
    $resolved = Ticket::factory()->resolved()->create(['title' => 'Already resolved']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$resolved]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->filterTable('status', ['new', 'triaged', 'in_progress', 'resolved'])
        ->assertCanSeeTableRecords([$open, $resolved]);
});
