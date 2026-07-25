<?php

declare(strict_types=1);

return [
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
        'add_step' => 'Add step',
        'screenshots' => 'Screenshots',
    ],
];
