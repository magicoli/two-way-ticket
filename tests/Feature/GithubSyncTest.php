<?php

use Illuminate\Support\Facades\Http;
use Magicoli\TwoWayTicket\Actions\CreateGithubIssue;
use Magicoli\TwoWayTicket\Actions\SyncGithubIssues;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Models\Ticket;

it('refuses to push a ticket whose labels are all private', function (): void {
    $ticket = Ticket::factory()->withLabels('billing')->create();

    resolve(CreateGithubIssue::class)->handle($ticket);
})->throws(RuntimeException::class);

it('pushes a ticket with a mix of labels, stripping the private one from the payload', function (): void {
    // Oli, 2026-07-26: "nos labels customs peuvent tout à fait se synchroniser vers github [...]
    // la seule chose particulière c'est de pouvoir en garder qui sont privés (pas d'envoi du
    // label, et pas d'envoi de l'issue si elle n'a que des labels privés)".
    Http::fake([
        'api.github.com/repos/example/example/issues' => Http::response([
            'html_url' => 'https://github.com/example/example/issues/43',
            'number' => 43,
        ], 201),
    ]);

    $ticket = Ticket::factory()->withLabels('bug', 'billing')->create(['title' => 'Mixed labels']);

    resolve(CreateGithubIssue::class)->handle($ticket);

    Http::assertSent(function ($request): bool {
        return in_array('bug', $request['labels'], true) && ! in_array('billing', $request['labels'], true);
    });
});

it('pushes a syncable ticket to GitHub and stores the issue reference', function (): void {
    Http::fake([
        'api.github.com/repos/example/example/issues' => Http::response([
            'html_url' => 'https://github.com/example/example/issues/42',
            'number' => 42,
        ], 201),
    ]);

    $ticket = Ticket::factory()->withLabels('bug')->create(['title' => 'Real bug']);

    resolve(CreateGithubIssue::class)->handle($ticket);

    expect($ticket->fresh())
        ->github_issue_url->toBe('https://github.com/example/example/issues/42')
        ->github_issue_number->toBe(42);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/issues')
        && $request['title'] === 'Real bug'
        && in_array('bug', $request['labels'], true));
});

it('mirrors a linked ticket status from its real GitHub issue state', function (): void {
    $ticket = Ticket::factory()->linked(7)->create();

    Http::fake([
        'api.github.com/repos/example/example/issues/7' => Http::response([
            'number' => 7,
            'state' => 'closed',
            'closed_at' => '2026-07-25T10:00:00Z',
        ]),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    $result = resolve(SyncGithubIssues::class)->handle();

    expect($result['updated'])->toBe(1);
    expect($ticket->fresh())->status->toBe(TicketStatus::Resolved)->resolved_at->not->toBeNull();
});

it('imports a GitHub issue that has no local ticket at all', function (): void {
    // The defining feature (SPEC.md §8): an issue opened straight on GitHub, never through this
    // app, still ends up here after a sync.
    Http::fake([
        'api.github.com/repos/example/example/issues*' => Http::response([
            [
                'number' => 99,
                'title' => 'Reported straight on GitHub',
                'body' => 'Steps here.',
                'state' => 'open',
                'html_url' => 'https://github.com/example/example/issues/99',
                'labels' => [['name' => 'bug'], ['name' => 'good first issue']],
                'milestone' => null,
            ],
        ]),
    ]);

    $result = resolve(SyncGithubIssues::class)->handle();

    expect($result['imported'])->toBe(1);

    $imported = Ticket::query()->where('github_issue_number', 99)->first();
    expect($imported)->not->toBeNull();
    expect($imported->title)->toBe('Reported straight on GitHub');
    expect($imported->labels)->toBe(['bug', 'good first issue']);
    expect($imported->status)->toBe(TicketStatus::New);
});

it('closes a linked ticket as Resolved regardless of why GitHub closed it', function (): void {
    // Oli, 2026-07-26: "closed sur GitHub ne veut pas forcément dire Resolved [...] on s'aligne
    // à 100% sur le système de GitHub" — Resolved means closed, full stop. The reason (wontfix,
    // duplicate...) lives in github_state_reason and the labels, never guessed into a status.
    $ticket = Ticket::factory()->linked(8)->create();

    Http::fake([
        'api.github.com/repos/example/example/issues/8' => Http::response([
            'number' => 8,
            'state' => 'closed',
            'state_reason' => 'not_planned',
            'closed_at' => '2026-07-25T10:00:00Z',
        ]),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    resolve(SyncGithubIssues::class)->handle();

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::Resolved)
        ->github_state_reason->toBe('not_planned');
});

it('leaves local progress alone while an issue stays open, except moving a reopened ticket off Resolved', function (): void {
    $inProgress = Ticket::factory()->linked(10)->create(['status' => 'in_progress']);
    $wasResolved = Ticket::factory()->linked(11)->resolved()->create();

    Http::fake([
        'api.github.com/repos/example/example/issues/10' => Http::response(['number' => 10, 'state' => 'open']),
        'api.github.com/repos/example/example/issues/11' => Http::response(['number' => 11, 'state' => 'open', 'state_reason' => 'reopened']),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    resolve(SyncGithubIssues::class)->handle();

    expect($inProgress->fresh())->status->toBe(TicketStatus::InProgress);
    expect($wasResolved->fresh())->status->toBe(TicketStatus::Triaged)->resolved_at->toBeNull();
});

it('does not re-import an issue that already has a local ticket', function (): void {
    Ticket::factory()->linked(99)->create();

    Http::fake([
        // Order matters: Http::fake() matches the FIRST pattern in the array, and the
        // wildcarded list pattern below would otherwise also match this exact single-issue URL.
        'api.github.com/repos/example/example/issues/99' => Http::response([
            'number' => 99,
            'state' => 'open',
        ]),
        'api.github.com/repos/example/example/issues*' => Http::response([
            [
                'number' => 99,
                'title' => 'Already tracked',
                'state' => 'open',
                'html_url' => 'https://github.com/example/example/issues/99',
                'labels' => [],
                'milestone' => null,
            ],
        ]),
    ]);

    $result = resolve(SyncGithubIssues::class)->handle();

    expect($result['imported'])->toBe(0);
    expect(Ticket::query()->where('github_issue_number', 99)->count())->toBe(1);
});
