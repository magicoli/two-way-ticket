{{-- Two Way Ticket guidelines for AI code assistants --}}
{{-- Source: https://github.com/magicoli/two-way-ticket --}}
{{-- License: AGPL-3.0-or-later | (c) Magiiic --}}

## Two Way Ticket

This app tracks bugs, tasks and ideas with `magicoli/two-way-ticket`. File them through its API — never in a TODO file, and never only in the conversation.

A ticket has exactly the fields a GitHub issue has: title, description, status (`open`/`closed`), `state_reason`, labels, assignees, milestone, projects. Nothing more — no priority, no custom workflow states. Sync is bidirectional: an issue edited on GitHub updates the local ticket and vice versa, last write wins.

### API

`{base}` is `/api/` + `config('two-way-ticket.api.route_prefix')` (`api/tickets` by default), authenticated with `Authorization: Bearer <TWO_WAY_TICKET_API_TOKEN>`.

- `GET {base}?status=open` — list tickets (`closed` also valid; omit `status` for everything). Also accepts `label` and `per_page`. An unrecognised filter is silently ignored, so check the `meta.total` you get back is the number you expected. Paginated: follow `links.next` until it is null rather than stopping at the first page.
- `POST {base}` — create one. `title` is required. Optional: `description`, `steps` (array of strings — they become a "Steps to reproduce" list in the description), `labels`, `assignees`, `milestone`, `projects`, `role`, `page_url`, `app_version`. The description is composed once here from those fields and is an ordinary editable field afterwards.
- `GET {base}/{id}` — read one.
- `PATCH {base}/{id}` — update `title`, `description`, `labels`, `assignees`, `milestone`, `projects` or `status`.

Check `?status=open` periodically for newly reported items and handle them like a bug reported in conversation.

### Never write `#n` for a ticket

Ticket ids and GitHub issue numbers are **different numbers** — ticket 82 is not issue 82, and most tickets have no issue at all. GitHub turns any `#n` it finds in an issue body or comment into a link to *its* issue n, so a `#82` written here silently points at an unrelated issue the day the ticket is promoted.

Write **`ticket 82`**, never `#82`, in every title, description and comment. `#n` is reserved for real GitHub issue numbers — in commit messages, where `Fix #123` is meant to close issue 123.

### Status belongs to GitHub

Once a ticket is linked to an issue, its status **only** reflects that issue. Writing "FIXED" in the description changes nothing, and the next sync overwrites any local status that disagrees with GitHub. Closing one for real means closing the issue — a `Fix #N` keyword in a commit that reaches the default branch, or `gh issue close <n>`. `PATCH {base}/{id} {"status": "closed"}` is durable only for a ticket that was never pushed to GitHub.
