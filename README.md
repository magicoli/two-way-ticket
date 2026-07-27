# Two-Way Ticket

*Helpdesk truckin' down the track* — a ticketing system for Laravel / Filament that keeps every
issue in one place: your own app.

Tickets are reported from inside your app, triaged there and closed there. Any one of them can be
**promoted to a real GitHub issue** when that is where it belongs, and from that moment the two
stay in sync **both ways**: edited on GitHub, it updates here; edited here, it updates there. An
issue opened directly on GitHub, by someone who never touched your app, is pulled in too.

Promoting is a deliberate, per-ticket choice, and that is the whole point. A client's support
request, a note to yourself, "can you make the button bigger" — none of that belongs on a public
issue tracker, and none of it has to go there. What is genuinely a development issue gets promoted
and picks up everything GitHub offers; the rest simply stays home. One backlog either way, so
nothing lives in two systems at once.

Nothing is lost in translation when a ticket is promoted, because a ticket **is** a GitHub issue in
every respect that matters: title, description, `open`/`closed` with GitHub's own `state_reason`,
labels, assignees, milestone and Projects. No invented priorities or extra workflow states to map
onto something GitHub doesn't have.

GitHub is entirely optional. Leave it unconfigured and this is a self-contained tracker that never
talks to anyone.

See [DEVELOPERS.md](DEVELOPERS.md) for the full design.

## What you get

- A Filament resource to browse, filter and triage tickets, with bulk push / close / labelling.
- A **"Report an issue"** button you can attach to any panel, separately from the admin-only list —
  reporters never need access to the backlog they report into.
- A token-protected JSON API (`/api/tickets`), so scripts and coding agents can file and update
  tickets without a browser.
- Translations in English, French and Dutch, overridable per app.

Not there yet: **comments** on a ticket, and a **GitHub webhook** to make incoming sync immediate
instead of on-demand. Both are planned — see [DEVELOPERS.md](DEVELOPERS.md) §6 and §8.

## Installation

Not on Packagist yet, so point Composer at the repository first:

```bash
composer config repositories.two-way-ticket vcs https://github.com/magicoli/two-way-ticket.git
composer require magicoli/two-way-ticket
php artisan migrate
```

Then, in the panel provider of an admin panel:

```php
use Magicoli\TwoWayTicket\ReportIssuePlugin;
use Magicoli\TwoWayTicket\TicketsPlugin;

$panel
    ->plugins([
        TicketsPlugin::make(),
        ReportIssuePlugin::make(),
    ])
```

`TicketsPlugin` registers a Filament resource, so give it a panel **without tenancy**: Filament
scopes resources to the current tenant whether they ask for it or not, and a ticket belongs to no
tenant. On a tenant-scoped panel it fails at render.

`ReportIssuePlugin` has no such constraint — add it on its own to every other panel where people
should be able to report, and they never need access to the backlog they report into:

```php
use Magicoli\TwoWayTicket\ReportIssuePlugin;

$panel
    ->plugins([
        ReportIssuePlugin::make(),
    ])
```

### Optional

Everything below is optional — for overriding, never to make the package work. Its translations
(en/fr/nl) and its stylesheet both load on their own.

The migrations run straight from the package — nothing to publish, and no copies in your app to
drift from ours. Publish them only if you want to own and edit them.

```bash
php artisan vendor:publish --tag=two-way-ticket-translations
php artisan vendor:publish --tag=two-way-ticket-styles
php artisan vendor:publish --tag=two-way-ticket-migrations
```

`TicketStatsWidget` is a plain Filament widget, so it can go on a dashboard too — add it to a
panel's `->widgets([...])`. Its counters link to the ticket list, so put it on a panel where
`TicketsPlugin` is registered.


Set in `.env`:

```
TWO_WAY_TICKET_API_TOKEN=
TWO_WAY_TICKET_GITHUB_TOKEN=
TWO_WAY_TICKET_GITHUB_REPOSITORY=owner/repo
```

Only the API token matters for a local-only tracker. Without the two GitHub values, everything
works except pushing and syncing, which say so plainly rather than failing quietly.

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

## License

AGPL-3.0-or-later — see [LICENSE](LICENSE).
