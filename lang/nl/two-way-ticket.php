<?php

declare(strict_types=1);

return [
    'ticket' => [
        'label' => 'Ticket',
        'plural' => 'Tickets',
    ],

    'priority' => [
        'low' => 'Laag',
        'medium' => 'Gemiddeld',
        'high' => 'Hoog',
        'urgent' => 'Urgent',
    ],

    'status' => [
        'new' => 'Nieuw',
        'triaged' => 'Beoordeeld',
        'in_progress' => 'In behandeling',
        'resolved' => 'Opgelost',
    ],

    'issue' => [
        'not_configured' => 'GitHub is niet geconfigureerd (token of repository ontbreekt).',
        'not_syncable' => 'Dit ticket heeft geen synchroniseerbaar label — voeg er een toe voordat u het naar GitHub stuurt.',
        'steps' => 'Stappen om te reproduceren',
        'no_steps' => 'Geen stappen opgegeven.',
        'reported_by' => 'Gemeld door',
        'app_version' => 'App-versie',
        'page_url' => 'Pagina',
        'unknown_reporter' => 'Onbekend',
        'footer' => 'Automatisch aangemaakt vanuit ticket #:id.',
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
        'priority' => 'Prioriteit',
        'labels' => 'Labels',
        'milestone' => 'Mijlpaal',
        'page_url' => 'Pagina',
        'app_version' => 'App-versie',
        'role' => 'Rol',
        'reported_by' => 'Gemeld door',
        'reported_at' => 'Gemeld op',
        'resolved_at' => 'Opgelost op',
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
        'priority' => 'Prioriteit',
        'labels' => 'Label',
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
        'sync_with_github' => 'Synchroniseren met GitHub',
        'synced' => 'Gesynchroniseerd',
        'sync_failed' => 'Synchronisatie mislukt',
        'sync_result' => ':updated bijgewerkt, :imported geïmporteerd vanaf GitHub.',
        'push_to_github' => 'Naar GitHub sturen',
        'pushed_to_github' => 'Naar GitHub gestuurd',
        'could_not_push' => 'Kon niet naar GitHub sturen',
    ],
];
