<?php

use Livewire\Livewire;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

it('hides resolved tickets by default, showing them via the Closed/All tabs instead of a filter', function (): void {
    // Oli, 2026-07-26: "Par défaut on doit uniquement afficher les tickets ouverts/non résolus."
    // Oli, 2026-07-26 (follow-up): the status SelectFilter was replaced by Open/Closed/All tabs,
    // a general condition that doesn't live in the filters row.
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $open = Ticket::factory()->create(['title' => 'Still open']);
    $resolved = Ticket::factory()->resolved()->create(['title' => 'Already resolved']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$resolved]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('activeTab', 'closed')
        ->assertCanSeeTableRecords([$resolved])
        ->assertCanNotSeeTableRecords([$open]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$open, $resolved]);
});
