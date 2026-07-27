<?php

use Livewire\Livewire;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\CreateTicket;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\EditTicket;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ViewTicket;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

/**
 * Both pages render the same TicketHeader, whose entries resolve closures against the record —
 * a mistyped closure argument there took the whole view page down once, silently, since nothing
 * was actually rendering these pages in the suite.
 */
it('renders the view and edit pages for a fully populated ticket', function (): void {
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $ticket = Ticket::factory()->linked(12)->closed()->create([
        'title' => 'Everything set',
        'description' => "A body\n\nwith line breaks.",
        'labels' => ['bug'],
        'assignees' => ['oli'],
        'projects' => ['Roadmap'],
        'milestone' => 'v1.1',
        'state_reason' => 'completed',
        'app_version' => '1.2.3',
        'page_url' => 'https://example.test/admin/tickets?tab=closed',
        'user_id' => $user->id,
    ]);

    // Version and page live in the header now (badge + link), not as labelled fields in the
    // body — the page shows its path, so the header keeps its width.
    Livewire::actingAs($user)
        ->test(ViewTicket::class, ['record' => $ticket->id])
        ->assertOk()
        ->assertSee('1.2.3')
        ->assertSee('/admin/tickets');

    Livewire::actingAs($user)->test(EditTicket::class, ['record' => $ticket->id])->assertOk();
});

it('renders the create page, where there is no record for the header to describe', function (): void {
    // The create page runs the same form schema with a null record, so every TicketHeader closure
    // has to accept one — a non-nullable hint took the whole page down with a TypeError, and
    // nothing here was rendering CreateTicket to catch it.
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    Livewire::actingAs($user)->test(CreateTicket::class)->assertOk();
});

it('actually creates a ticket from the create page, stamping the build it was reported on', function (): void {
    // Oli, 2026-07-27, hit on the very first ticket filed in a second host app: rendering the page
    // was covered, submitting it was not. app_version is NOT NULL and no form field sets it, so
    // "Create" died on an integrity constraint — the API and the GitHub import each set it, the
    // one path a human uses did not.
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    config()->set('two-way-ticket.app_version', '2.3.4');

    Livewire::actingAs($user)->test(CreateTicket::class)
        ->fillForm(['title' => 'Confirm it works'])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->where('title', 'Confirm it works')->sole();

    expect($ticket->app_version)->toBe('2.3.4');
});

it('leaves app_version alone when it is set on purpose, empty included', function (): void {
    // The GitHub import stores an empty string deliberately: an issue opened on GitHub came from
    // no build of ours. The creating hook must not "helpfully" overwrite that.
    config()->set('two-way-ticket.app_version', '2.3.4');

    $imported = Ticket::create(['title' => 'From GitHub', 'app_version' => '']);

    expect($imported->app_version)->toBe('');
});

it('orders dates the way the reader\'s locale does, never month-first outside en', function (): void {
    // Oli, 2026-07-26, absolute rule: "juil. 25, 2026" n'a AUCUN sens pour un francophone, le
    // mois vient APRES le jour. Carbon's isoFormat is what actually respects that; Filament's
    // plain ->dateTime() default ('M j, Y H:i:s') does not, it just translates the month name
    // in place and keeps the US ordering.
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $ticket = Ticket::factory()->create([
        'title' => 'Dated',
        'created_at' => '2026-07-25 11:50:00',
    ]);

    app()->setLocale('fr');

    Livewire::actingAs($user)
        ->test(ViewTicket::class, ['record' => $ticket->id])
        ->assertSee('25 juil. 2026')
        ->assertDontSee('juil. 25, 2026');

    app()->setLocale('en');
});

it('renders both pages for a bare ticket, with every optional header part absent', function (): void {
    // Oli, 2026-07-26: "on n'affiche 'reported by', 'Closed' et 'Github' que si il y a une valeur".
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $ticket = Ticket::factory()->create(['title' => 'Bare minimum']);

    Livewire::actingAs($user)->test(ViewTicket::class, ['record' => $ticket->id])->assertOk();
    Livewire::actingAs($user)->test(EditTicket::class, ['record' => $ticket->id])->assertOk();
});
