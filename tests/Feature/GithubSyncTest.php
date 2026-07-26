<?php

use Illuminate\Support\Facades\Http;
use Magicoli\TwoWayTicket\Actions\CreateGithubIssue;
use Magicoli\TwoWayTicket\Actions\SyncGithubIssues;
use Magicoli\TwoWayTicket\Actions\UpdateGithubIssue;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Models\Ticket;

it('pushes a ticket to GitHub with all its labels, assignees, and milestone', function (): void {
    // Oli, 2026-07-26: "on choisit manuellement de lier ou pas une issue locale à GitHub. Si on
    // la lie, peu importe son ou ses labels, l'issue est synchronisée" — no gating at all beyond
    // the manual "Push to GitHub" click itself.
    Http::fake([
        'api.github.com/repos/example/example/issues' => Http::response([
            'html_url' => 'https://github.com/example/example/issues/42',
            'number' => 42,
        ], 201),
    ]);

    $ticket = Ticket::factory()->create([
        'title' => 'Real bug',
        'labels' => ['bug', 'billing'],
        'assignees' => ['oli'],
        'milestone' => 'v1.1',
    ]);

    resolve(CreateGithubIssue::class)->handle($ticket);

    expect($ticket->fresh())
        ->github_issue_url->toBe('https://github.com/example/example/issues/42')
        ->github_issue_number->toBe(42);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/issues')
        && $request['title'] === 'Real bug'
        && $request['labels'] === ['bug', 'billing']
        && $request['assignees'] === ['oli']
        && $request['milestone'] === 'v1.1');
});

it('pushes a linked ticket back onto its GitHub issue, description as the body verbatim', function (): void {
    // The outgoing half of the sync, fired on every save (EditTicket::afterSave).
    Http::fake([
        'api.github.com/repos/example/example/issues/5' => Http::response(['number' => 5]),
    ]);

    $ticket = Ticket::factory()->linked(5)->closed()->create([
        'title' => 'Revised title',
        'description' => "New body\n\nwith line breaks.",
        'labels' => ['bug'],
        'assignees' => ['oli'],
    ]);

    resolve(UpdateGithubIssue::class)->handle($ticket);

    Http::assertSent(fn ($request): bool => $request->method() === 'PATCH'
        && $request['title'] === 'Revised title'
        && $request['body'] === "New body\n\nwith line breaks."
        && $request['labels'] === ['bug']
        && $request['assignees'] === ['oli']
        && $request['state'] === 'closed');
});

it('writes the issue body in English whatever the reporter\'s own language', function (): void {
    // Oli, 2026-07-26: "Le formattage github doit être en anglais dans tous les cas, peu importe
    // la langue de l'utilisateur" — this text lands on GitHub, read by whoever passes by.
    Http::fake([
        'api.github.com/repos/example/example/issues' => Http::response([
            'html_url' => 'https://github.com/example/example/issues/50',
            'number' => 50,
        ], 201),
    ]);

    app()->setLocale('fr');

    $ticket = Ticket::factory()->create([
        'title' => 'Quelque chose casse',
        'description' => 'La page plante.',
        'app_version' => '1.2.3',
        'page_url' => 'https://private.internal.test/admin/tickets?tab=closed',
    ]);

    resolve(CreateGithubIssue::class)->handle($ticket);

    Http::assertSent(function ($request) use ($ticket): bool {
        return str_contains($request['body'], '**App version:** 1.2.3')
            && ! str_contains($request['body'], "Version de l'app")
            // Path only: the host may be private or local, so the full URL is useless to a
            // GitHub reader and leaks an internal address.
            && str_contains($request['body'], '**Page:** `/admin/tickets`')
            && ! str_contains($request['body'], 'private.internal.test')
            // No reporter line, no "created from ticket #N" footer — that id is a LOCAL one and
            // reads as a GitHub issue number next to a real issue.
            && ! str_contains($request['body'], 'Reported by')
            && ! str_contains($request['body'], '#'.$ticket->id);
    });

    app()->setLocale('en');
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
    expect($ticket->fresh())->status->toBe(TicketStatus::Closed)->closed_at->not->toBeNull();
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
                'assignees' => [['login' => 'oli']],
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
    expect($imported->assignees)->toBe(['oli']);
    expect($imported->status)->toBe(TicketStatus::Open);
});

it('keeps state_reason as a verbatim mirror, never turned into a guessed status', function (): void {
    // Oli, 2026-07-26: "si github a un champ state_reason, on en a un aussi, si il n'en a pas, on
    // n'en a pas" — closed always means Closed, full stop; the WHY is stored as-is, not
    // interpreted (wontfix/duplicate stay in the labels GitHub already gives us).
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
        ->status->toBe(TicketStatus::Closed)
        ->state_reason->toBe('not_planned');
});

it('syncs GitHub Projects, which only exist in the GraphQL API', function (): void {
    // The REST issue payload has no `projects` key at all — Projects (v2) are GraphQL-only.
    $ticket = Ticket::factory()->linked(20)->create();

    Http::fake([
        'api.github.com/graphql' => Http::response(['data' => ['repository' => ['issues' => [
            'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
            'nodes' => [[
                'number' => 20,
                'projectItems' => ['nodes' => [['project' => ['title' => 'Roadmap']]]],
            ]],
        ]]]]),
        'api.github.com/repos/example/example/issues/20' => Http::response(['number' => 20, 'state' => 'open']),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    resolve(SyncGithubIssues::class)->handle();

    expect($ticket->fresh())->projects->toBe(['Roadmap']);
});

it('leaves stored projects alone when the token cannot read them, instead of wiping them', function (): void {
    // A token without `read:project` gets a 200 carrying an `errors` array. Treating that as
    // "no projects" would silently clear every ticket's — unknown is not the same as empty.
    $ticket = Ticket::factory()->linked(21)->create(['projects' => ['Roadmap']]);

    Http::fake([
        'api.github.com/graphql' => Http::response(['errors' => [['type' => 'INSUFFICIENT_SCOPES']]]),
        'api.github.com/repos/example/example/issues/21' => Http::response(['number' => 21, 'state' => 'open']),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    resolve(SyncGithubIssues::class)->handle();

    expect($ticket->fresh())->projects->toBe(['Roadmap']);
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
