{{-- Two Way Ticket guidelines for AI code assistants --}}
{{-- Source: https://github.com/magicoli/two-way-ticket --}}
{{-- License: AGPL-3.0-or-later | (c) Magiiic --}}

## Two Way Ticket

- `magicoli/two-way-ticket` is a ticketing system aligned on GitHub's own issue model. A ticket has exactly the fields a GitHub issue has — title, description, status (`open`/`closed`), `state_reason`, labels, assignees, milestone, projects — and nothing more. Do not invent extra states, priorities or workflow fields: if GitHub doesn't track it, neither do we.
- Sync is bidirectional. An issue edited on GitHub updates the local ticket, and vice versa; last write wins.

### Filing a ticket

When asked to note a bug, a task or an idea for later ("note this in the todos"), create a ticket through the API rather than editing a TODO file.

- `GET {base}?state=open` — list tickets (`closed` also valid, omit `state` for everything). The response is paginated: follow `links.next` until it is null, don't stop at the first page.
- `POST {base}` — create one. `title` is required; `description`, `labels` (array), `assignees` (array) are optional.
- `GET {base}/{id}` — read one.
- `PATCH {base}/{id}` — update `title`, `description`, `labels`, `assignees`, `status`.

`{base}` is `/api/` + `config('two-way-ticket.api.route_prefix')` (`api/tickets` by default), authenticated with `Authorization: Bearer <TWO_WAY_TICKET_API_TOKEN>`.

### Status is GitHub's, not ours

- A linked ticket's status **only** reflects its real GitHub issue. Writing "FIXED" in the description changes nothing, and the next sync overwrites any local status that disagrees with GitHub.
- Closing a ticket for real means closing the issue: a `Fix #N` / `Fixes #N` keyword in a commit that lands on the default branch, or `gh issue close <n>`. `PATCH .../{id} {"status": "closed"}` is durable only for a ticket that was never pushed to GitHub.
- Therefore: **push a ticket to GitHub before starting the fix, not after.** With an issue number in hand, the fix commit carries its own closing reference and no separate step is needed.
