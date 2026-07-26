# Two-Way Ticket

*Helpdesk truckin' down the track* — a flexible ticketing system for Laravel / Filament, built
around one idea: **align on GitHub's own model instead of reinventing it.** Labels, assignee,
milestone, and Projects mirror GitHub directly — sync is genuinely bidirectional (an issue opened
straight on GitHub shows up here too, not just the other way around).

See [SPEC.md](SPEC.md) for the full design.

## Status

V1 in progress: model, Filament resource, GitHub push + bidirectional sync (labels, assignees,
milestone, Projects), and a token-protected JSON API are in place. Comments and the webhook are
planned for V2 (see SPEC.md).

## Installation

```bash
composer require magicoli/two-way-ticket
php artisan vendor:publish --tag=two-way-ticket-migrations
php artisan migrate
```

Set in `.env`:

```
TWO_WAY_TICKET_API_TOKEN=
TWO_WAY_TICKET_GITHUB_TOKEN=
TWO_WAY_TICKET_GITHUB_REPOSITORY=owner/repo
```

## GitHub token

Create a **classic** personal access token at
[github.com/settings/tokens/new](https://github.com/settings/tokens/new) and put it in
`TWO_WAY_TICKET_GITHUB_TOKEN`.

| Scope | Needed for | Without it |
|---|---|---|
| `repo` | Reading, creating and updating issues | Nothing works — sync and push both fail |
| `project` | Reading the Projects (v2) an issue belongs to | Everything else keeps working; the `projects` field is simply left as-is |

`project` is genuinely optional: Projects (v2) exist only in GitHub's GraphQL API, and a token
without that scope gets an `INSUFFICIENT_SCOPES` error back. The sync treats that as *unknown*
rather than *empty*, so it never wipes stored values and never fails the run — you just don't get
project data.

**Fine-grained tokens** work too and are the better choice if you want to restrict access to a
single repository: give them *Issues: Read and write* (plus *Projects: Read-only* if you want
project sync). Note that a fine-grained token's Projects permission lives on the **organisation or
user account**, not the repository, so scoping it to one repo doesn't restrict project visibility.

Deploy keys are **not** an option here: they authenticate git transport (clone/push over SSH) and
give no access to the issues API at all. Same for SSH keys in general — the REST and GraphQL APIs
only accept tokens.

## Publishing translations

```bash
php artisan vendor:publish --tag=two-way-ticket-translations
```

Only needed to override the wording; the package's own translations (en/fr/nl) load on their own.

## License

AGPL-3.0-or-later — see [LICENSE](LICENSE).
