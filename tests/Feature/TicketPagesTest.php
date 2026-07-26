<?php

use Livewire\Livewire;
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

it('renders both pages for a bare ticket, with every optional header part absent', function (): void {
    // Oli, 2026-07-26: "on n'affiche 'reported by', 'Closed' et 'Github' que si il y a une valeur".
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test']);

    $ticket = Ticket::factory()->create(['title' => 'Bare minimum']);

    Livewire::actingAs($user)->test(ViewTicket::class, ['record' => $ticket->id])->assertOk();
    Livewire::actingAs($user)->test(EditTicket::class, ['record' => $ticket->id])->assertOk();
});
