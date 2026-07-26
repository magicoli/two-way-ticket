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
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)->test(ViewTicket::class, ['record' => $ticket->id])->assertOk();
    Livewire::actingAs($user)->test(EditTicket::class, ['record' => $ticket->id])->assertOk();
});

it('renders the create page, where there is no record for the header to describe', function (): void {
    // The create page runs the same form schema with a null record, so every TicketHeader closure
    // has to accept one — a non-nullable hint took the whole page down with a TypeError, and
    // nothing here was rendering CreateTicket to catch it.
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    Livewire::actingAs($user)->test(CreateTicket::class)->assertOk();
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
