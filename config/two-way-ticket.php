<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The model a ticket belongs to (the reporter). Defaults to the framework's
    | base authenticatable user.
    |
    */

    'user_model' => \Illuminate\Foundation\Auth\User::class,

    /*
    |--------------------------------------------------------------------------
    | JSON API
    |--------------------------------------------------------------------------
    |
    | A single static bearer token, not per-user — this API has one caller (a
    | scripted agent), not end users. Fails closed: an unconfigured (empty)
    | token rejects every request.
    |
    */

    'api' => [
        'token' => env('TWO_WAY_TICKET_API_TOKEN', ''),
        'route_prefix' => env('TWO_WAY_TICKET_API_ROUTE_PREFIX', 'tickets'),
    ],

    /*
    |--------------------------------------------------------------------------
    | App version
    |--------------------------------------------------------------------------
    |
    | Stamped onto every ticket so you know which build it was hit on. When
    | empty, the host app's `app.version` is used.
    |
    | Note the '' fallback: these values are read with `config()->string()`,
    | which throws on null. "Not configured" is an empty string here, never
    | null.
    |
    */

    'app_version' => env('TWO_WAY_TICKET_APP_VERSION', ''),

    /*
    |--------------------------------------------------------------------------
    | Screenshot uploads
    |--------------------------------------------------------------------------
    */

    'screenshots' => [
        'disk' => env('TWO_WAY_TICKET_SCREENSHOT_DISK', 'public'),
        'directory' => env('TWO_WAY_TICKET_SCREENSHOT_DIRECTORY', 'two-way-ticket'),
        'max_size' => 5120, // KB, per screenshot
        'max_count' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub integration
    |--------------------------------------------------------------------------
    |
    | Where tickets are pushed as issues, and where Sync pulls untracked
    | issues back from. A token with `repo` (or `issues:write`+`issues:read`)
    | scope and a target "owner/repo" are required.
    |
    | Empty (never null) when unset — an empty token or repository is what
    | raises the "GitHub is not configured" error.
    |
    | `default_labels`: GitHub's own standard label catalogue (what a fresh
    | repo ships with) — offered as seed options in the label picker before
    | any real repo-specific labels are known, customizable per project.
    |
    | Linking a ticket to GitHub is always a manual, explicit action ("Push to
    | GitHub") — once linked, everything on it syncs, labels included, with
    | no separate gating on top.
    |
    */

    'github' => [
        'token' => env('TWO_WAY_TICKET_GITHUB_TOKEN', env('GITHUB_TOKEN', '')),
        'repository' => env('TWO_WAY_TICKET_GITHUB_REPOSITORY', ''),
        /*
         | Icon for the "push to GitHub" action. Heroicons has no GitHub mark, and Blade Icons
         | throws outright on an unknown set, so the default stays neutral — point this at
         | 'ri-github-fill' or 'bi-github' if your app ships that icon set.
         */
        'icon' => env('TWO_WAY_TICKET_GITHUB_ICON', 'heroicon-o-arrow-up-tray'),
        'default_labels' => [
            'bug', 'documentation', 'duplicate', 'enhancement', 'good first issue',
            'help wanted', 'invalid', 'question', 'wontfix',
        ],
        'title_prefix' => '',
    ],

];
