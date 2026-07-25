<?php

declare(strict_types=1);

return [
    'ticket' => [
        'label' => 'Ticket',
        'plural' => 'Tickets',
    ],

    'priority' => [
        'low' => 'Basse',
        'medium' => 'Moyenne',
        'high' => 'Haute',
        'urgent' => 'Urgente',
    ],

    'status' => [
        'new' => 'Nouveau',
        'triaged' => 'Trié',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu',
    ],

    'issue' => [
        'not_configured' => 'GitHub n\'est pas configuré (token ou repository manquant).',
        'not_syncable' => 'Ce ticket n\'a aucun label synchronisable — ajoutez-en un avant de l\'envoyer sur GitHub.',
        'steps' => 'Étapes pour reproduire',
        'no_steps' => 'Aucune étape fournie.',
        'reported_by' => 'Signalé par',
        'app_version' => 'Version de l\'app',
        'page_url' => 'Page',
        'unknown_reporter' => 'Inconnu',
        'footer' => 'Créé automatiquement depuis le ticket #:id.',
    ],

    'report_issue' => [
        'report_button' => 'Signaler un problème',
        'title' => 'Signaler un problème',
        'submit' => 'Envoyer',
        'submitted' => 'Merci — votre signalement a bien été envoyé.',
        'add_step' => 'Ajouter une étape',
        'screenshots' => 'Captures d\'écran',
    ],
];
