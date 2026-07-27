<?php

use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Models\Ticket;

function withApiToken(string $token = 'test-token'): array
{
    return ['Authorization' => "Bearer {$token}"];
}

it('rejects requests without a token', function (): void {
    $this->getJson('/api/tickets')->assertUnauthorized();
});

it('rejects requests with the wrong token', function (): void {
    $this->getJson('/api/tickets', withApiToken('wrong'))->assertUnauthorized();
});

it('creates a ticket', function (): void {
    $response = $this->postJson(
        '/api/tickets',
        [
            'title' => 'Video export hangs at 90%',
            'description' => 'Happens on every long video.',
            'steps' => ['Open a campaign', 'Click "Generate video"'],
            'labels' => ['bug'],
            'assignees' => ['oli'],
        ],
        withApiToken(),
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.title', 'Video export hangs at 90%')
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.labels', ['bug'])
        ->assertJsonPath('data.assignees', ['oli']);

    $this->assertDatabaseHas('tickets', ['title' => 'Video export hangs at 90%']);
});

it('captures page_url from the Referer header when not given explicitly', function (): void {
    $response = $this->postJson(
        '/api/tickets',
        ['title' => 'Broken layout'],
        [
            ...withApiToken(),
            'Referer' => 'https://example.test/project/acme/quick-publish',
        ],
    );

    $response->assertCreated()->assertJsonPath('data.page_url', 'https://example.test/project/acme/quick-publish');
});

it('rejects a store request without a title', function (): void {
    $this->postJson('/api/tickets', [], withApiToken())->assertUnprocessable()->assertJsonValidationErrors('title');
});

it('lists tickets and filters by status', function (): void {
    Ticket::factory()->create(['title' => 'Open one']);
    Ticket::factory()->linked()->create(['title' => 'Another open one']);
    Ticket::factory()->linked()->closed()->create(['title' => 'Closed one']);

    $this->getJson('/api/tickets', withApiToken())->assertOk()->assertJsonCount(3, 'data');

    $this
        ->getJson('/api/tickets?status=closed', withApiToken())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Closed one');
});

it('filters by label', function (): void {
    Ticket::factory()->withLabels('bug')->create(['title' => 'A bug']);
    Ticket::factory()->withLabels('billing')->create(['title' => 'A billing question']);

    $this
        ->getJson('/api/tickets?label=billing', withApiToken())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'A billing question');
});

it('shows a single ticket', function (): void {
    $ticket = Ticket::factory()->create(['title' => 'Specific ticket']);

    $this
        ->getJson("/api/tickets/{$ticket->id}", withApiToken())
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id)
        ->assertJsonPath('data.title', 'Specific ticket');
});

it('updates a ticket', function (): void {
    $ticket = Ticket::factory()->create(['title' => 'Original title']);

    $this
        ->patchJson(
            "/api/tickets/{$ticket->id}",
            ['title' => 'Revised title', 'labels' => ['bug', 'urgent-fix']],
            withApiToken(),
        )
        ->assertOk()
        ->assertJsonPath('data.title', 'Revised title');

    expect($ticket->fresh())->title->toBe('Revised title')->labels->toBe(['bug', 'urgent-fix']);
});

it('closes a ticket without any GitHub issue at all', function (): void {
    $ticket = Ticket::factory()->create(['title' => 'No GitHub issue for this one']);

    $this
        ->patchJson("/api/tickets/{$ticket->id}", ['status' => 'closed'], withApiToken())
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');

    expect($ticket->fresh())->status->toBe(TicketStatus::Closed)->closed_at->not->toBeNull();
});
