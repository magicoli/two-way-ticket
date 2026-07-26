<?php

declare(strict_types=1);

return [
    'ticket' => [
        'label' => 'Ticket',
        'plural' => 'Tickets',
    ],

    'status' => [
        'open' => 'Open',
        'closed' => 'Gesloten',
    ],

    'state_reason' => [
        'completed' => 'Voltooid',
        'not_planned' => 'Niet gepland',
        'duplicate' => 'Duplicaat',
        'reopened' => 'Heropend',
    ],

    'issue' => [
        'not_configured' => 'GitHub is niet geconfigureerd (token of repository ontbreekt).',
        'details' => 'Details',
        'steps' => 'Stappen om te reproduceren',
        'reported_by' => 'Gemeld door',
        'app_version' => 'Versie',
        'page_url' => 'Pagina',
    ],

    'report_issue' => [
        'report_button' => 'Een probleem melden',
        'title' => 'Een probleem melden',
        'submit' => 'Versturen',
        'submitted' => 'Bedankt — uw melding is verzonden.',
    ],

    'field' => [
        'title' => 'Titel',
        'description' => 'Omschrijving',
        'steps' => 'Stappen om te reproduceren',
        'status' => 'Status',
        'labels' => 'Labels',
        'milestone' => 'Mijlpaal',
        'assignees' => 'Toegewezenen',
        'projects' => 'Projecten',
        'page_url' => 'Pagina',
        'app_version' => 'Versie',
        'role' => 'Rol',
        'reported_by' => 'Gemeld door',
        'reported_at' => 'Gemeld op',
        'closed_at' => 'Gesloten op',
        'state_reason' => 'Reden',
        'github_issue' => 'GitHub-issue',
        'github' => 'GitHub',
        'screenshots' => 'Schermafbeeldingen',
        'details' => 'Details',
        'add_step' => 'Stap toevoegen',
    ],

    'table' => [
        'empty' => 'Geen tickets',
    ],

    'filter' => [
        'status' => 'Status',
        'labels' => 'Label',
        'assignees' => 'Toegewezene',
        'projects' => 'Project',
        'milestone' => 'Mijlpaal',
        'app_version' => 'Versie',
        'user' => 'Gebruiker',
    ],

    'tab' => [
        'open' => 'Open',
        'closed' => 'Gesloten',
        'all' => 'Alle',
    ],

    'actions' => [
        'close' => 'Sluiten',
        'done' => ':count ticket(s) bijgewerkt.',
        'sync_with_github' => 'Synchroniseren met GitHub',
        'synced' => 'Gesynchroniseerd',
        'sync_failed' => 'Synchronisatie mislukt',
        'sync_result' => ':updated bijgewerkt, :imported geïmporteerd vanaf GitHub.',
        'push_to_github' => 'Naar GitHub sturen',
        'pushed_to_github' => 'Naar GitHub gestuurd',
        'could_not_push' => 'Kon niet naar GitHub sturen',
    ],
];
