# Two-Way Ticket

*Helpdesk truckin' down the track* — a flexible ticketing system for Laravel / Filament, built
around one idea: **align on GitHub's own model instead of reinventing it.** Labels, assignee,
milestone, and Projects mirror GitHub directly — sync is genuinely bidirectional (an issue opened
straight on GitHub shows up here too, not just the other way around).

See [SPEC.md](SPEC.md) for the full design.

## Status

V1 in progress: model, Filament resource, GitHub push + bidirectional sync, and a token-protected
JSON API are in place. Comments, webhook, and GitHub Projects support are planned for V2 (see
SPEC.md).

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

## License

AGPL-3.0-or-later — see [LICENSE](LICENSE).
