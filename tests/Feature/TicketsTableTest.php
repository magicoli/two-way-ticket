<?php

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
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

it('shows the page path under the title, without costing a column', function (): void {
    // Oli, 2026-07-26: as its own column the URL ate width for a secondary detail. Stacked under
    // the title instead — and stacking must not cost the other columns their sortable headers.
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    Ticket::factory()->create([
        'title' => 'Something broke',
        'page_url' => 'https://private.internal.test/admin/tickets?tab=closed',
    ]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->assertSee('/admin/tickets')
        ->assertSee('Title')
        ->assertSee('Milestone');
});

it('shows status and reason as two separate badges in one column', function (): void {
    // Oli, 2026-07-26: two badges, not one merged label — status is the important one, the
    // reason is secondary and wraps underneath when the column is tight.
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $ticket = Ticket::factory()->closed()->create(['state_reason' => 'duplicate']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('activeTab', 'closed')
        ->assertTableColumnStateSet('status', ['closed', 'duplicate'], $ticket)
        ->assertSee('Closed')
        ->assertSee('Duplicate');
});

it('closes a ticket from the list, asking for a reason the way GitHub does', function (): void {
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $ticket = Ticket::factory()->create(['title' => 'Still open', 'labels' => ['bug']]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->callAction(TestAction::make('close')->table($ticket), [
            'state_reason' => 'duplicate',
            'labels' => ['bug', 'duplicate'],
        ]);

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::Closed)
        ->state_reason->toBe('duplicate')
        ->labels->toBe(['bug', 'duplicate'])
        ->closed_at->not->toBeNull();
});

it('leaves labels untouched when the close modal\'s label picker is left empty', function (): void {
    // Empty means "don't bother", not "strip every label this ticket has".
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $ticket = Ticket::factory()->create(['labels' => ['bug']]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->callAction(TestAction::make('close')->table($ticket), ['state_reason' => 'completed']);

    expect($ticket->fresh())->labels->toBe(['bug'])->state_reason->toBe('completed');
});

it('closes several tickets at once, skipping the ones already closed', function (): void {
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $open = Ticket::factory()->create(['title' => 'Open one']);
    $alreadyClosed = Ticket::factory()->closed()->create(['title' => 'Closed one', 'state_reason' => 'completed']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('activeTab', 'all')
        ->selectTableRecords([$open->getKey(), $alreadyClosed->getKey()])
        ->callAction(TestAction::make('close')->table()->bulk(), ['state_reason' => 'not_planned']);

    expect($open->fresh())->status->toBe(TicketStatus::Closed)->state_reason->toBe('not_planned');
    // Untouched: it was already closed, so its original reason stands.
    expect($alreadyClosed->fresh())->state_reason->toBe('completed');
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
