<?php

use Illuminate\Support\Facades\Gate;
use Magicoli\TwoWayTicket\Filament\Pages\ReportIssue;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStatsWidget;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\ReportIssuePlugin;
use Magicoli\TwoWayTicket\Tests\Fixtures\AdminAwareUser;
use Magicoli\TwoWayTicket\Tests\Fixtures\OpenTicketPolicy;
use Magicoli\TwoWayTicket\Tests\Fixtures\RoleUser;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;
use Magicoli\TwoWayTicket\TicketsPlugin;

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

it('shows or hides the widget from a boolean set where it is registered', function (): void {
    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;

    $this->actingAs($member);

    TicketStatsWidget::make()->visible(true);
    expect(TicketStatsWidget::canView())->toBeTrue();

    TicketStatsWidget::make()->visible(false);
    expect(TicketStatsWidget::canView())->toBeFalse();
});

it('shows or hides the backlog from a boolean set on the plugin', function (): void {
    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;

    $this->actingAs($member);

    TicketsPlugin::make()->visible(true);
    expect(TicketResource::canAccess())->toBeTrue();

    TicketsPlugin::make()->visible(false);
    expect(TicketResource::canAccess())->toBeFalse();
});

it('shows or hides reporting from a boolean set on the plugin', function (): void {
    $admin = AdminAwareUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $admin->admin = true;

    $this->actingAs($admin);

    ReportIssuePlugin::make()->visible(false);
    expect(ReportIssue::canAccess())->toBeFalse();
    $this->get('/admin/report-issue')->assertForbidden();

    ReportIssuePlugin::make()->visible(true);
    expect(ReportIssue::canAccess())->toBeTrue();
    $this->get('/admin/report-issue')->assertOk();
});

it('reads hasRole when the model has no isAdmin', function (): void {
    $member = RoleUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $admin = RoleUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $admin->roles = ['admin'];

    expect(Gate::forUser($member)->allows('viewAny', Ticket::class))->toBeFalse();
    expect(Gate::forUser($admin)->allows('viewAny', Ticket::class))->toBeTrue();
});

it('reads an is_admin attribute when the model has neither method', function (): void {
    $flagged = User::create(['name' => 'Flagged', 'email' => 'flagged@example.test', 'is_admin' => true]);
    $plain = User::create(['name' => 'Plain', 'email' => 'plain@example.test', 'is_admin' => false]);

    expect(Gate::forUser($flagged)->allows('viewAny', Ticket::class))->toBeTrue();
    // False is an answer: we do not keep looking for a signal that says yes, not even id 1.
    expect(Gate::forUser($plain)->allows('viewAny', Ticket::class))->toBeFalse();
});

it('lets visible() open the list while the policy still governs the actions', function (): void {
    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;
    $ticket = Ticket::create(['title' => 'Something']);

    $this->actingAs($member);

    TicketsPlugin::make()->visible(true);

    expect(TicketResource::canAccess())->toBeTrue();

    // Seeing the backlog is not acting on it: visible() answers "does this show", the policy
    // answers "may they touch it". Editing and deleting stay triage rights...
    expect(TicketResource::canEdit($ticket))->toBeFalse();
    expect(TicketResource::canDelete($ticket))->toBeFalse();

    // ...while creating is the REPORTING right, deliberately open — the same right that puts a
    // "Report an issue" button in front of this user anyway.
    expect(TicketResource::canCreate())->toBeTrue();
});

it('asks a visible() closure again for every user', function (): void {
    $admin = AdminAwareUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $admin->admin = true;
    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;

    TicketStatsWidget::make()->visible(fn(): bool => auth()->user()?->isAdmin() ?? false);

    $this->actingAs($admin);
    expect(TicketStatsWidget::canView())->toBeTrue();

    $this->actingAs($member);
    expect(TicketStatsWidget::canView())->toBeFalse();
});

it('hides the widget with hidden(), the inverse of visible()', function (): void {
    $admin = AdminAwareUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $admin->admin = true;

    $this->actingAs($admin);

    TicketStatsWidget::make()->hidden(true);
    expect(TicketStatsWidget::canView())->toBeFalse();

    TicketStatsWidget::make()->hidden(false);
    expect(TicketStatsWidget::canView())->toBeTrue();
});

it('keeps honouring authorizeReportingUsing, under visible()', function (): void {
    $member = AdminAwareUser::create(['name' => 'Member', 'email' => 'member@example.test']);
    $member->admin = false;

    $plugin = ReportIssuePlugin::make()->authorizeReportingUsing(fn(): bool => false);

    // The closure overrides the policy, which would allow any authenticated user to report...
    expect($plugin->canReport($member))->toBeFalse();

    // ...and visible() overrides the closure in turn.
    $plugin->visible(true);
    expect($plugin->canReport($member))->toBeTrue();
});
