<?php

use Livewire\Livewire;
use Magicoli\TwoWayTicket\Filament\Pages\ReportIssue;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

it('exposes the ticket list on a panel with TicketsPlugin attached', function (): void {
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $this->actingAs($user)->get('/admin/tickets')->assertOk();
});

it('does not expose any ticket list route at all on a panel with only ReportIssuePlugin', function (): void {
    // The whole point of splitting the two plugins (SPEC.md §9): a panel that only reports
    // issues should never even HAVE a browsable resource route, tenant-scoped or not.
    $user = User::create(['name' => 'Regular', 'email' => 'regular@example.test']);

    $this->actingAs($user)->get('/app/tickets')->assertNotFound();
});

it('exposes the report-an-issue page on both panels', function (): void {
    $user = User::create(['name' => 'Someone', 'email' => 'someone@example.test']);

    $this->actingAs($user)->get('/admin/report-issue')->assertOk();
    $this->actingAs($user)->get('/app/report-issue')->assertOk();
});

it('submits a report with only the minimal reporter-facing fields', function (): void {
    $user = User::create(['name' => 'Reporter', 'email' => 'reporter@example.test']);

    Livewire::actingAs($user)
        ->withQueryParams(['from' => 'https://example.test/app/quick-publish'])
        ->test(ReportIssue::class)
        ->fillForm([
            'title' => 'Something looks broken',
            'description' => 'It just does.',
            'steps' => [['step' => 'Open the page'], ['step' => 'Look at it']],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::query()->where('title', 'Something looks broken')->first();

    expect($ticket)->not->toBeNull();
    expect($ticket->page_url)->toBe('https://example.test/app/quick-publish');
    expect($ticket->status->value)->toBe('open');
    expect($ticket->user_id)->toBe($user->id);
});

it('actually resolves translations, not just raw keys', function (): void {
    // Regression (Oli, 2026-07-25): the service provider never registered the "two-way-ticket"
    // translation namespace at all, so every __('two-way-ticket::...') call rendered as the raw,
    // untranslated key string on screen.
    expect(__('two-way-ticket::two-way-ticket.report_issue.title'))->toBe('Report an issue');
    expect(__('two-way-ticket::two-way-ticket.ticket.plural'))->toBe('Tickets');
});
