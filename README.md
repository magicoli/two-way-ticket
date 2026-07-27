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

Then, in your admin panel provider:

```php
use Magicoli\TwoWayTicket\ReportIssuePlugin;
use Magicoli\TwoWayTicket\TicketsPlugin;

$panel
    ->plugins([
        TicketsPlugin::make(),
        ReportIssuePlugin::make(),
    ])
```

And in any other panel where people should be able to report:

```php
use Magicoli\TwoWayTicket\ReportIssuePlugin;

$panel
    ->plugins([
        ReportIssuePlugin::make(),
    ])
```

That's it.

### AI guidelines

The package ships [Laravel Boost](https://github.com/laravel/boost) guidelines, so coding agents
file what they find here instead of leaving it in a TODO file or in the conversation. Boost never
pulls in a third-party package's guidelines on its own — deliberately, so a dependency can't grow
your context behind your back. Tell it once per project:

```bash
php artisan boost:install          # Laravel 12
php artisan boost:update --discover # Laravel 13
```

Both are interactive — tick two-way-ticket in the list they offer. The answer is stored in
`boost.json`, and from then on a plain `boost:update` keeps the section current on its own.
Running `boost:update` without `--discover` *before* that first opt-in changes nothing: it
replays the stored list, and ours isn't in it yet.

Everything Boost writes stays between the `<laravel-boost-guidelines>` markers of `CLAUDE.md` and
`AGENTS.md`, so whatever you keep outside them survives every update.

## Who may report, who may triage

Two different rights, one ordinary Laravel policy on the `Ticket` model — nothing to learn that is
specific to this package.

- **`create`** is reporting an issue. Any authenticated user, by default.
- **`viewAny`, `view`, `update`, `delete`** are triaging: seeing the whole backlog and acting on
  it. Administrators, by default.

"Administrator" has no framework-standard definition, so the shipped policy reads the signals your
user model probably already carries and stops at the first one it finds: `isAdmin()`,
`hasRole('admin')` (what spatie/laravel-permission gives you), an `is_admin` attribute, and
failing all that, the user with id 1.

This matters more than it looks: Filament delegates resource authorization to policies and treats
a **missing** policy as *allowed*, so a package that ships none puts its backlog in front of
everyone who can reach the panel — customers included, on a panel that has any.

Your own rules replace ours the usual way. Write the policy where you write your others,
`app/Policies/TicketPolicy.php`, then register it in the `boot()` method of
`app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\Gate;
use Magicoli\TwoWayTicket\Models\Ticket;

public function boot(): void
{
    Gate::policy(Ticket::class, \App\Policies\TicketPolicy::class);
}
```

Registering it is not optional the way it is for your own models: Laravel finds a policy by
name, and for `Magicoli\TwoWayTicket\Models\Ticket` it looks for
`Magicoli\TwoWayTicket\Policies\TicketPolicy` — ours. It never looks in your `App\Policies`
namespace for a model it doesn't own. One line, once, and it wins whatever the boot order.

The list, its pages, the stats widget and the "Report an issue" button all follow, because they
all ask the policy. Sell support to some members only and you write `create`; open the backlog to
a support team and you write `viewAny`.

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

## Optional

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

## Multi-tenant apps

Only relevant if your app uses Filament tenancy, and then only for `TicketsPlugin`.

Give it a panel **without** tenancy — which is where you would put it anyway: tickets are global,
and an admin does not expect one backlog per tenant. Filament scopes a resource to the current
tenant whether the resource asks for it or not, so on a tenant-scoped panel the ticket list fails
at render rather than simply showing everything.

`ReportIssuePlugin` is unaffected and can go anywhere.

## License

AGPL-3.0-or-later — see [LICENSE](LICENSE).
