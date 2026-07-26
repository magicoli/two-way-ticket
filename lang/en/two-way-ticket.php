<?php

declare(strict_types=1);

return [
    'ticket' => [
        'label' => 'Ticket',
        'plural' => 'Tickets',
    ],

    'status' => [
        'open' => 'Open',
        'closed' => 'Closed',
    ],

    'state_reason' => [
        'completed' => 'Completed',
        'not_planned' => 'Not planned',
        'duplicate' => 'Duplicate',
        'reopened' => 'Reopened',
    ],

    'issue' => [
        'not_configured' => 'GitHub is not configured (missing token or repository).',
        'details' => 'Details',
        'steps' => 'Steps to reproduce',
        'reported_by' => 'Reported by',
        'app_version' => 'Version',
        'page_url' => 'Page',
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
        'labels' => 'Labels',
        'milestone' => 'Milestone',
        'assignees' => 'Assignees',
        'projects' => 'Projects',
        'page_url' => 'Page',
        'app_version' => 'Version',
        'role' => 'Role',
        'reported_by' => 'Reported by',
        'reported_at' => 'Reported at',
        'closed_at' => 'Closed at',
        'state_reason' => 'Reason',
        'github_issue' => 'GitHub issue',
        'github' => 'GitHub',
        'screenshots' => 'Screenshots',
        'details' => 'Details',
        'add_step' => 'Add step',
    ],

    'table' => [
        'empty' => 'No tickets',
    ],

    'filter' => [
        'status' => 'Status',
        'labels' => 'Label',
        'assignees' => 'Assignee',
        'projects' => 'Project',
        'milestone' => 'Milestone',
        'app_version' => 'Version',
        'user' => 'User',
    ],

    'tab' => [
        'open' => 'Open',
        'closed' => 'Closed',
        'all' => 'All',
    ],

    'actions' => [
        'save' => 'Save',
        'assign' => 'Assign',
        'replace_existing' => 'Replace existing values',
        'close' => 'Close',
        'done' => ':count ticket(s) updated.',
        'sync_with_github' => 'Sync with GitHub',
        'synced' => 'Synced',
        'sync_failed' => 'Sync failed',
        'sync_result' => ':updated updated, :imported imported from GitHub.',
        'push_to_github' => 'Push to GitHub',
        'pushed_to_github' => 'Pushed to GitHub',
        'could_not_push' => 'Could not push to GitHub',
    ],
];
