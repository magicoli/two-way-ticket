<?php

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\AdminUser;

it('hides closed tickets by default, showing them via the Closed/All tabs', function (): void {
    // Oli, 2026-07-26: "Par défaut on doit uniquement afficher les tickets ouverts."
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $open = Ticket::factory()->create(['title' => 'Still open']);
    $closed = Ticket::factory()->closed()->create(['title' => 'Already closed']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$closed]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('scope', 'closed')
        ->assertCanSeeTableRecords([$closed])
        ->assertCanNotSeeTableRecords([$open]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('scope', 'all')
        ->assertCanSeeTableRecords([$open, $closed]);
});

it('shows the page path under the title, without costing the table its headers', function (): void {
    // Oli, 2026-07-26: as its own column the URL ate width for a secondary detail. Grouped with
    // the title instead — but a top-level Stack drops the table into Filament's header-less card
    // mode, so EVERY column loses its header and its sorting. Asserting on the header row itself
    // rather than on the label text, which also appears in the column-toggle menu (a first
    // attempt at this test passed while the header row was in fact gone).
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $ticket = Ticket::factory()->create([
        'title' => 'Something broke',
        'page_url' => 'https://private.internal.test/admin/tickets?tab=closed',
    ]);

    $html = Livewire::actingAs($user)->test(ListTickets::class)->html();

    expect($html)
        ->toContain('/admin/tickets')
        ->toContain('fi-ta-header-cell')
        // Clicking anywhere on the row still opens the ticket: the path is plain text, because
        // giving it its own URL turns the cell into a link and kills the row click.
        ->toContain('/tickets/' . $ticket->id)
        // One value per line, not joined with a stray ", ".
        ->not->toContain('Something broke, /admin/tickets');
});

it('puts the issue link in the GitHub action slot, not in a column of its own', function (): void {
    // Oli, 2026-07-26: a whole column just to show the issue number, next to a push action that
    // only ever appeared when there wasn't one. They share one slot now — mutually exclusive.
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    Ticket::factory()->linked(15)->create(['title' => 'Linked one']);

    $html = Livewire::actingAs($user)->test(ListTickets::class)->html();

    expect($html)
        ->toContain('#15')
        ->toContain('github.com/example/example/issues/15')
        // Rendered as a badge, not a button: it reads as data, not as something to trigger.
        ->toContain('fi-badge');
});

it('shows status and reason as two separate badges in one column', function (): void {
    // Oli, 2026-07-26: two badges, not one merged label — status is the important one, the
    // reason is secondary and wraps underneath when the column is tight.
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $ticket = Ticket::factory()->closed()->create(['state_reason' => 'duplicate']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('scope', 'closed')
        ->assertTableColumnStateSet('status', ['closed', 'duplicate'], $ticket)
        ->assertSee('Closed')
        ->assertSee('Duplicate');
});

it('closes a ticket from the list, asking for a reason the way GitHub does', function (): void {
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
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
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $ticket = Ticket::factory()->create(['labels' => ['bug']]);

    Livewire::actingAs($user)->test(ListTickets::class)->callAction(TestAction::make('close')->table($ticket), [
        'state_reason' => 'completed',
    ]);

    expect($ticket->fresh())->labels->toBe(['bug'])->state_reason->toBe('completed');
});

it('closes several tickets at once, skipping the ones already closed', function (): void {
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $open = Ticket::factory()->create(['title' => 'Open one']);
    $alreadyClosed = Ticket::factory()->closed()->create(['title' => 'Closed one', 'state_reason' => 'completed']);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->set('scope', 'all')
        ->selectTableRecords([$open->getKey(), $alreadyClosed->getKey()])
        ->callAction(TestAction::make('close')->table()->bulk(), ['state_reason' => 'not_planned']);

    expect($open->fresh())->status->toBe(TicketStatus::Closed)->state_reason->toBe('not_planned');
    // Untouched: it was already closed, so its original reason stands.
    expect($alreadyClosed->fresh())->state_reason->toBe('completed');
});

it('filters by labels and assignees, both ANDed with the active tab', function (): void {
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $match = Ticket::factory()->create(['title' => 'Bug assigned to oli', 'labels' => ['bug'], 'assignees' => ['oli']]);
    $wrongAssignee = Ticket::factory()->create([
        'title' => 'Bug assigned to someone else',
        'labels' => ['bug'],
        'assignees' => ['someone-else'],
    ]);
    $closedMatch = Ticket::factory()
        ->closed()
        ->create(['title' => 'Closed bug for oli', 'labels' => ['bug'], 'assignees' => ['oli']]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->filterTable('labels', ['values' => ['bug']])
        ->filterTable('assignees', ['values' => ['oli']])
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$wrongAssignee, $closedMatch]);
});

it('bulk-assigns labels by adding to what is already there, leaving other fields alone', function (): void {
    // Oli, 2026-07-26: bulk labels/projects/milestone. Adding rather than replacing is what makes
    // it usable for sweeping a backlog — and an omitted field must not wipe that attribute.
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $a = Ticket::factory()->create(['title' => 'A', 'labels' => ['bug'], 'milestone' => 'v1.0']);
    $b = Ticket::factory()->create(['title' => 'B', 'labels' => []]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->selectTableRecords([$a->getKey(), $b->getKey()])
        ->callAction(TestAction::make('assign')->table()->bulk(), ['labels' => ['enhancement']]);

    expect($a->fresh())->labels->toBe(['bug', 'enhancement'])->milestone->toBe('v1.0');
    expect($b->fresh())->labels->toBe(['enhancement']);
});

it('replaces instead of adding when asked to', function (): void {
    $user = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $ticket = Ticket::factory()->create(['labels' => ['bug', 'wontfix']]);

    Livewire::actingAs($user)
        ->test(ListTickets::class)
        ->selectTableRecords([$ticket->getKey()])
        ->callAction(TestAction::make('assign')->table()->bulk(), [
            'labels' => ['question'],
            'replace' => true,
        ]);

    expect($ticket->fresh())->labels->toBe(['question']);
});
