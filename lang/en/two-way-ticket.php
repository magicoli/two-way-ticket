<?php

declare(strict_types=1);

return [
    'ticket' => [
        'label' => 'Ticket',
        'plural' => 'Tickets',
    ],

    'priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'status' => [
        'new' => 'New',
        'triaged' => 'Triaged',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
    ],

    'issue' => [
        'not_configured' => 'GitHub is not configured (missing token or repository).',
        'not_syncable' => 'This ticket has no syncable label — add one before pushing it to GitHub.',
        'steps' => 'Steps to reproduce',
        'no_steps' => 'No steps provided.',
        'reported_by' => 'Reported by',
        'app_version' => 'App version',
        'page_url' => 'Page',
        'unknown_reporter' => 'Unknown',
        'footer' => 'Automatically created from ticket #:id.',
    ],

    'report_issue' => [
        'report_button' => 'Report an issue',
        'title' => 'Report an issue',
        'submit' => 'Submit',
        'submitted' => 'Thanks — your report was submitted.',
    ],

    'field' => [
        'title' => 'Title',
        'description' => 'Description',
        'steps' => 'Steps to reproduce',
        'status' => 'Status',
        'priority' => 'Priority',
        'labels' => 'Labels',
        'milestone' => 'Milestone',
        'page_url' => 'Page',
        'app_version' => 'App version',
        'role' => 'Role',
        'reported_by' => 'Reported by',
        'reported_at' => 'Reported at',
        'resolved_at' => 'Resolved at',
        'github_issue' => 'GitHub issue',
        'github' => 'GitHub',
        'screenshots' => 'Screenshots',
        'details' => 'Details',
        'add_step' => 'Add step',
    ],

    'table' => [
        'empty' => 'No tickets',
    ],

    'actions' => [
        'sync_with_github' => 'Sync with GitHub',
        'synced' => 'Synced',
        'sync_failed' => 'Sync failed',
        'sync_result' => ':updated updated, :imported imported from GitHub.',
        'push_to_github' => 'Push to GitHub',
        'pushed_to_github' => 'Pushed to GitHub',
        'could_not_push' => 'Could not push to GitHub',
    ],
];
