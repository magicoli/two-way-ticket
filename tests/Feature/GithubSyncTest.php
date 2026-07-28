<?php

use Illuminate\Support\Facades\Http;
use Magicoli\TwoWayTicket\Actions\CreateGithubIssue;
use Magicoli\TwoWayTicket\Actions\SyncGithubIssues;
use Magicoli\TwoWayTicket\Actions\UpdateGithubIssue;
use Magicoli\TwoWayTicket\Actions\UpdateGithubProjects;
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

    Http::assertSent(
        fn($request): bool => (
            str_contains((string) $request->url(), '/issues')
            && $request['title'] === 'Real bug'
            && $request['labels'] === ['bug', 'billing']
            && $request['assignees'] === ['oli']
            && $request['milestone'] === 'v1.1'
        ),
    );
});

it('pushes a linked ticket back onto its GitHub issue, description as the body verbatim', function (): void {
    // The outgoing half of the sync, fired on every save (EditTicket::afterSave).
    Http::fake([
        'api.github.com/repos/example/example/issues/5' => Http::response(['number' => 5]),
    ]);

    $ticket = Ticket::factory()
        ->linked(5)
        ->closed()
        ->create([
            'title' => 'Revised title',
            'description' => "New body\n\nwith line breaks.",
            'labels' => ['bug'],
            'assignees' => ['oli'],
        ]);

    resolve(UpdateGithubIssue::class)->handle($ticket);

    Http::assertSent(
        fn($request): bool => (
            $request->method() === 'PATCH'
            && $request['title'] === 'Revised title'
            && $request['body'] === "New body\n\nwith line breaks."
            && $request['labels'] === ['bug']
            && $request['assignees'] === ['oli']
            && $request['state'] === 'closed'
        ),
    );
});

it('pushes the description verbatim, never regenerating a body', function (): void {
    // Once created, the description is an ordinary two-way-synced field. Regenerating anything
    // at push time would drift the moment either side edits it.
    Http::fake([
        'api.github.com/repos/example/example/issues' => Http::response([
            'html_url' => 'https://github.com/example/example/issues/50',
            'number' => 50,
        ], 201),
    ]);

    $ticket = Ticket::factory()->create([
        'description' => "Edited by hand.\n\nStill mine.",
        'app_version' => '1.2.3',
        'page_url' => 'https://private.internal.test/admin/tickets?tab=closed',
    ]);

    resolve(CreateGithubIssue::class)->handle($ticket);

    Http::assertSent(fn($request): bool => $request['body'] === "Edited by hand.\n\nStill mine.");
});

it('mirrors a description edited on GitHub', function (): void {
    $ticket = Ticket::factory()->linked(32)->create(['description' => 'Original']);

    Http::fake([
        'api.github.com/graphql' => Http::response(['errors' => [['type' => 'INSUFFICIENT_SCOPES']]]),
        'api.github.com/repos/example/example/issues/32' => Http::response([
            'number' => 32,
            'state' => 'open',
            'body' => 'Rewritten on GitHub',
        ]),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    resolve(SyncGithubIssues::class)->handle();

    expect($ticket->fresh())->description->toBe('Rewritten on GitHub');
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
    // The defining feature (DEVELOPERS.md §8): an issue opened straight on GitHub, never through this
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

    expect($ticket->fresh())->status->toBe(TicketStatus::Closed)->state_reason->toBe('not_planned');
});

it('mirrors a title renamed on GitHub', function (): void {
    $ticket = Ticket::factory()->linked(31)->create(['title' => 'Old wording']);

    Http::fake([
        'api.github.com/graphql' => Http::response(['errors' => [['type' => 'INSUFFICIENT_SCOPES']]]),
        'api.github.com/repos/example/example/issues/31' => Http::response([
            'number' => 31,
            'state' => 'open',
            'title' => 'Renamed on GitHub',
        ]),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    resolve(SyncGithubIssues::class)->handle();

    expect($ticket->fresh())->title->toBe('Renamed on GitHub');
});

it('removes locally a label that was removed on GitHub', function (): void {
    // Oli, 2026-07-26: labels deleted on the repo stayed here — the sync mirrored status and
    // assignees but never labels, so they only ever accumulated. Mirroring REPLACES.
    $ticket = Ticket::factory()
        ->linked(30)
        ->create(['labels' => ['bug', 'wontfix'], 'milestone' => 'v1.0']);

    Http::fake([
        'api.github.com/graphql' => Http::response(['errors' => [['type' => 'INSUFFICIENT_SCOPES']]]),
        'api.github.com/repos/example/example/issues/30' => Http::response([
            'number' => 30,
            'state' => 'open',
            'labels' => [['name' => 'bug']],
            'milestone' => null,
        ]),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    $result = resolve(SyncGithubIssues::class)->handle();

    expect($result['updated'])->toBe(1);
    expect($ticket->fresh())->labels->toBe(['bug'])->milestone->toBeNull();
});

it('syncs GitHub Projects, which only exist in the GraphQL API', function (): void {
    // The REST issue payload has no `projects` key at all — Projects (v2) are GraphQL-only.
    $ticket = Ticket::factory()->linked(20)->create();

    Http::fake([
        'api.github.com/graphql' => Http::response([
            'data' => [
                'repository' => [
                    'issues' => [
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        'nodes' => [[
                            'number' => 20,
                            'projectItems' => ['nodes' => [['project' => ['title' => 'Roadmap']]]],
                        ]],
                    ],
                ],
            ],
        ]),
        'api.github.com/repos/example/example/issues/20' => Http::response(['number' => 20, 'state' => 'open']),
        'api.github.com/repos/example/example/issues*' => Http::response([]),
    ]);

    resolve(SyncGithubIssues::class)->handle();

    expect($ticket->fresh())->projects->toBe(['Roadmap']);
});

it('leaves stored projects alone when the token cannot read them, instead of wiping them', function (): void {
    // A token without `read:project` gets a 200 carrying an `errors` array. Treating that as
    // "no projects" would silently clear every ticket's — unknown is not the same as empty.
    $ticket = Ticket::factory()
        ->linked(21)
        ->create(['projects' => ['Roadmap']]);

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

it('closes the issue it just created when the ticket was already closed', function (): void {
    // GitHub's create endpoint takes no state, so a ticket triaged before it was ever pushed used
    // to arrive open — and since status belongs to GitHub once linked, the next sync reopened it
    // locally. Twenty-two tickets went back to open that way, recovered only from a backup.
    Http::fake([
        'api.github.com/repos/example/example/issues' => Http::response([
            'html_url' => 'https://github.com/example/example/issues/7',
            'number' => 7,
        ], 201),
        'api.github.com/repos/example/example/issues/7' => Http::response([], 200),
    ]);

    $ticket = Ticket::factory()->create([
        'title' => 'Already triaged',
        'status' => TicketStatus::Closed,
        'state_reason' => 'completed',
    ]);

    resolve(CreateGithubIssue::class)->handle($ticket);

    Http::assertSent(
        fn($request): bool => (
            $request->method() === 'PATCH'
            && str_contains((string) $request->url(), '/issues/7')
            && $request['state'] === 'closed'
            && $request['state_reason'] === 'completed'
        ),
    );
});

it('leaves an open ticket open, without a second call', function (): void {
    Http::fake([
        'api.github.com/repos/example/example/issues' => Http::response([
            'html_url' => 'https://github.com/example/example/issues/8',
            'number' => 8,
        ], 201),
    ]);

    $ticket = Ticket::factory()->create(['title' => 'Still open', 'status' => TicketStatus::Open]);

    resolve(CreateGithubIssue::class)->handle($ticket);

    Http::assertSentCount(1);
});

it('adds the issue to the project the ticket names', function (): void {
    // Projects (v2) exist only in GraphQL, so REST carries none of this: an issue pushed with a
    // project set locally arrived with no project at all until this was reconciled separately.
    Http::fake([
        'api.github.com/graphql' => Http::sequence()
            ->push([
                'data' => [
                    'repository' => [
                        'issue' => [
                            'id' => 'ISSUE_NODE',
                            'projectItems' => ['nodes' => []],
                        ],
                    ],
                ],
            ])
            ->push([
                'data' => [
                    'repositoryOwner' => [
                        'projectsV2' => [
                            'nodes' => [
                                ['id' => 'PROJECT_NODE', 'title' => 'Roadmap'],
                            ],
                        ],
                    ],
                ],
            ])
            ->push(['data' => ['addProjectV2ItemById' => ['item' => ['id' => 'ITEM_NODE']]]]),
    ]);

    $ticket = Ticket::factory()->create([
        'projects' => ['Roadmap'],
        'github_issue_url' => 'https://github.com/example/example/issues/7',
        'github_issue_number' => 7,
    ]);

    resolve(UpdateGithubProjects::class)->handle($ticket);

    Http::assertSent(
        fn($request): bool => (
            str_contains((string) $request->url(), '/graphql')
            && str_contains((string) ($request['query'] ?? ''), 'addProjectV2ItemById')
            && $request['variables']['project'] === 'PROJECT_NODE'
            && $request['variables']['content'] === 'ISSUE_NODE'
        ),
    );
});

it('removes the issue from a project the ticket no longer names', function (): void {
    Http::fake([
        'api.github.com/graphql' => Http::sequence()
            ->push([
                'data' => [
                    'repository' => [
                        'issue' => [
                            'id' => 'ISSUE_NODE',
                            'projectItems' => [
                                'nodes' => [
                                    ['id' => 'ITEM_NODE', 'project' => ['id' => 'PROJECT_NODE', 'title' => 'Dropped']],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->push(['data' => ['deleteProjectV2Item' => ['deletedItemId' => 'ITEM_NODE']]]),
    ]);

    // Through an update, the way it actually happens: the project is taken off an existing ticket.
    $ticket = Ticket::factory()->create([
        'projects' => ['Dropped'],
        'github_issue_url' => 'https://github.com/example/example/issues/7',
        'github_issue_number' => 7,
    ]);

    $ticket->update(['projects' => []]);

    resolve(UpdateGithubProjects::class)->handle($ticket);

    Http::assertSent(
        fn($request): bool => (
            str_contains((string) ($request['query'] ?? ''), 'deleteProjectV2Item')
            && $request['variables']['item'] === 'ITEM_NODE'
        ),
    );
});

it('never breaks a save when GitHub refuses the projects call', function (): void {
    // A token without the `project` scope gets a 200 carrying an errors array. The local value
    // stands and the save goes through — the same way the incoming sync treats unreadable
    // projects as unknown rather than empty.
    Http::fake([
        'api.github.com/graphql' => Http::response(['errors' => [['type' => 'INSUFFICIENT_SCOPES']]], 200),
    ]);

    $ticket = Ticket::factory()->create([
        'projects' => ['Roadmap'],
        'github_issue_url' => 'https://github.com/example/example/issues/7',
        'github_issue_number' => 7,
    ]);

    expect(fn() => resolve(UpdateGithubProjects::class)->handle($ticket))->not->toThrow(Throwable::class);
    expect($ticket->fresh()->projects)->toBe(['Roadmap']);
});
