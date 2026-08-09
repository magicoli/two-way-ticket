# Changelog

## 0.3.0
- feat: `NavigationItemsPlugin` — places `NavigationItem` lists at a render hook; `ReportIssuePlugin` uses it instead of a bespoke button
- feat: default `TicketPolicy` gates the backlog to admins, reporting stays open to any authenticated user
- feat: `visible($bool|Closure)` on `TicketsPlugin`, `ReportIssuePlugin` and `TicketStatsWidget`
- feat: editable `app_version` field on the report/ticket form, pre-filled with the running build
- fix: reporting uses its own `report` ability instead of `create` (the full triage form)
- fix: `app_version` NOT NULL constraint failures on create, and on clearing it while editing
- fix: ticket status and Projects weren't pushed to GitHub at creation, only on later syncs
- fix: the admin test panel was silently unregistered since a stray fixture edit, failing the whole suite
- update: `app_version` moved from the GitHub sidebar into the report column
- doc: install steps, AI-agent guidelines (assignees, ticket references), ° vs # convention

## 0.1.2
- new: installs on Laravel 12 as well as 13 — Filament v5 already accepted both, only our own
  `illuminate/contracts` constraint stood in the way; the suite runs green against 12.64 and 13.22
  with no source change, so upgrading a host app stays a decision of its own
- update: the README keeps the base install to three commands and moves everything optional under
  its own heading

## 0.1.1
- update: requires PHP 8.3 instead of 8.4 — nothing here needed 8.4, only two lines of syntax a
  formatter kept reintroducing; `mago.toml` now pins the version so it stops
- new: the stylesheet is publishable like the translations, for overriding rather than for making
  anything work (`--tag=two-way-ticket-styles`)
- update: `TicketStats` renamed to `TicketStatsWidget`, so every widget carries the suffix; it can
  be placed on a dashboard, not only above the ticket list
- fix: the README skipped the `composer config repositories…` step the package cannot be installed
  without while it is not on Packagist

## 0.1.0
- new: local-first ticket tracking for Laravel / Filament — report, triage and close issues inside
  your own app, with a Filament resource for the backlog and a separate "Report an issue" plugin
  for panels whose users must never see that backlog
- new: any ticket can be promoted to a real GitHub issue, one at a time or in bulk, and stays in
  sync both ways from then on — title, description, status, labels, assignees, milestone and
  Projects, in either direction, last write wins
- new: issues opened directly on GitHub are pulled in on sync, even when they never went through
  the app
- new: a ticket carries exactly GitHub's own fields, `state_reason` included, and nothing else —
  no invented priorities or workflow states to map onto something GitHub doesn't have
- new: token-protected JSON API (`/api/tickets`) so scripts and coding agents can file and update
  tickets without a browser
- new: English, French and Dutch translations, overridable per app; migrations, stylesheet and
  Boost guidelines ship with the package and need no publishing step
