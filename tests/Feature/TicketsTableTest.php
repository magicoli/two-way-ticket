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

it('narrows further with the status filter, ANDed with the active tab', function (): void {
    // Oli, 2026-07-26: "les tabs comme condition supplémentaire (condition des tabs ET
    // conditions des différents filtres)" — the status filter still picks among the real
    // statuses (new/triaged/in_progress...), it isn't replaced by the tabs.
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $new = Ticket::factory()->create(['title' => 'Brand new', 'status' => 'new']);
    $inProgress = Ticket::factory()->create(['title' => 'Being worked on', 'status' => 'in_progress']);
    $resolved = Ticket::factory()->resolved()->create(['title' => 'Already resolved']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->filterTable('status', ['values' => ['new']])
        ->assertCanSeeTableRecords([$new])
        ->assertCanNotSeeTableRecords([$inProgress, $resolved]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('activeTab', 'closed')
        ->filterTable('status', ['values' => ['new']])
        ->assertCanNotSeeTableRecords([$new, $inProgress, $resolved]);
});
