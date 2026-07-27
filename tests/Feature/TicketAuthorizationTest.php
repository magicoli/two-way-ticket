<?php

use Illuminate\Support\Facades\Gate;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStatsWidget;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Tests\Fixtures\AdminAwareUser;
use Magicoli\TwoWayTicket\Tests\Fixtures\OpenTicketPolicy;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

it('lets an admin triage', function (): void {
    $admin = AdminAwareUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $admin->admin = true;

    $this->actingAs($admin);

    expect(Gate::allows('viewAny', Ticket::class))->toBeTrue();
    expect(TicketResource::canViewAny())->toBeTrue();
    expect(TicketStatsWidget::canView())->toBeTrue();
});

it('keeps the backlog away from a user the app does not call an admin', function (): void {
    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;

    $this->actingAs($member);

    expect(Gate::allows('viewAny', Ticket::class))->toBeFalse();
    expect(TicketResource::canViewAny())->toBeFalse();
    expect(TicketStatsWidget::canView())->toBeFalse();
});

it('hides the ticket list from a non-admin over http', function (): void {
    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;

    $this->actingAs($member)->get('/admin/tickets')->assertForbidden();
});

it('still lets that user report an issue', function (): void {
    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;

    $this->actingAs($member);

    expect(Gate::allows('create', Ticket::class))->toBeTrue();
    $this->get('/admin/report-issue')->assertOk();
});

it('falls back to the first user when the model says nothing', function (): void {
    $first = User::create(['name' => 'First', 'email' => 'first@example.test']);
    $second = User::create(['name' => 'Second', 'email' => 'second@example.test']);

    expect(Gate::forUser($first)->allows('viewAny', Ticket::class))->toBeTrue();
    expect(Gate::forUser($second)->allows('viewAny', Ticket::class))->toBeFalse();
});

it('lets the app replace the default policy', function (): void {
    Gate::policy(Ticket::class, OpenTicketPolicy::class);

    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;

    $this->actingAs($member);

    expect(Gate::allows('viewAny', Ticket::class))->toBeTrue();
    expect(Gate::allows('create', Ticket::class))->toBeFalse();
});
