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

it('composes the description once from the structured fields, in English, path only', function (): void {
    // Oli, 2026-07-26: "on formate la description en fonction des champs structurés du formulaire
    // (pas de titre pour les champs vides)". English because it lands on GitHub; the page is
    // reduced to its path since the host may be private or local.
    config()->set('two-way-ticket.app_version', '2.0.0');
    $user = User::create(['name' => 'Reporter', 'email' => 'reporter@example.test']);

    app()->setLocale('fr');

    Livewire::actingAs($user)
        ->withQueryParams(['from' => 'https://private.internal.test/admin/tickets?tab=closed'])
        ->test(ReportIssue::class)
        ->fillForm([
            'title' => 'Composed',
            'description' => 'It breaks.',
            'steps' => [['step' => 'Open it'], ['step' => 'Look']],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    app()->setLocale('en');

    // A markdown list: single newlines collapse, so plain lines would render as one paragraph.
    expect(Ticket::query()->where('title', 'Composed')->value('description'))->toBe(
        "It breaks.\n\n".
        "## Steps to reproduce\n1. Open it\n2. Look\n\n".
        "## Details\n- **Version:** 2.0.0\n- **Page:** `/admin/tickets`",
    );
});

it('gives an empty section no heading at all', function (): void {
    $user = User::create(['name' => 'Reporter', 'email' => 'reporter@example.test']);
    config()->set('two-way-ticket.app_version', '');

    Livewire::actingAs($user)
        ->test(ReportIssue::class)
        ->fillForm(['title' => 'Bare', 'description' => 'Just this.'])
        ->call('submit')
        ->assertHasNoErrors();

    expect(Ticket::query()->where('title', 'Bare')->value('description'))->toBe('Just this.');
});

it('lets the reporter clear the pre-filled page and pick labels', function (): void {
    // Oli, 2026-07-26: the page URL is pre-filled but editable "si le problème est générique et
    // pas lié spécifiquement à la page depuis laquelle on a cliqué".
    $user = User::create(['name' => 'Reporter', 'email' => 'reporter@example.test']);

    Livewire::actingAs($user)
        ->withQueryParams(['from' => 'https://example.test/app/quick-publish'])
        ->test(ReportIssue::class)
        ->assertFormSet(['page_url' => 'https://example.test/app/quick-publish'])
        ->fillForm([
            'title' => 'General remark',
            'page_url' => null,
            'labels' => ['question'],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::query()->where('title', 'General remark')->first();

    expect($ticket->page_url)->toBeNull();
    expect($ticket->labels)->toBe(['question']);
});

it('actually resolves translations, not just raw keys', function (): void {
    // Regression (Oli, 2026-07-25): the service provider never registered the "two-way-ticket"
    // translation namespace at all, so every __('two-way-ticket::...') call rendered as the raw,
    // untranslated key string on screen.
    expect(__('two-way-ticket::two-way-ticket.report_issue.title'))->toBe('Report an issue');
    expect(__('two-way-ticket::two-way-ticket.ticket.plural'))->toBe('Tickets');
});
