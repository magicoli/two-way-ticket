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
        'add_step' => 'Stap toevoegen',
        'screenshots' => 'Schermafbeeldingen',
    ],
];
