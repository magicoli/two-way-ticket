<?php

declare(strict_types=1);

return [
    'ticket' => [
        'label' => 'Ticket',
        'plural' => 'Tickets',
    ],

    'status' => [
        'open' => 'Ouvert',
        'closed' => 'Fermé',
    ],

    'issue' => [
        'not_configured' => 'GitHub n\'est pas configuré (token ou repository manquant).',
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
    ],

    'field' => [
        'title' => 'Titre',
        'description' => 'Description',
        'steps' => 'Étapes pour reproduire',
        'status' => 'Statut',
        'labels' => 'Étiquettes',
        'milestone' => 'Jalon',
        'assignees' => 'Assignés',
        'projects' => 'Projets',
        'page_url' => 'Page',
        'app_version' => 'Version de l\'app',
        'role' => 'Rôle',
        'reported_by' => 'Signalé par',
        'reported_at' => 'Signalé le',
        'closed_at' => 'Fermé le',
        'state_reason' => 'Motif',
        'github_issue' => 'Issue GitHub',
        'github' => 'GitHub',
        'screenshots' => 'Captures d\'écran',
        'details' => 'Détails',
        'add_step' => 'Ajouter une étape',
    ],

    'table' => [
        'empty' => 'Aucun ticket',
    ],

    'filter' => [
        'status' => 'Statut',
        'labels' => 'Etiquette',
        'assignees' => 'Assigné',
        'projects' => 'Projet',
        'milestone' => 'Jalon',
        'app_version' => 'Version',
        'user' => 'Utilisateur',
    ],

    'tab' => [
        'open' => 'Ouverts',
        'closed' => 'Fermés',
        'all' => 'Tous',
    ],

    'actions' => [
        'sync_with_github' => 'Synchroniser avec GitHub',
        'synced' => 'Synchronisé',
        'sync_failed' => 'Échec de la synchronisation',
        'sync_result' => ':updated mis à jour, :imported importés depuis GitHub.',
        'push_to_github' => 'Envoyer sur GitHub',
        'pushed_to_github' => 'Envoyé sur GitHub',
        'could_not_push' => 'Impossible d\'envoyer sur GitHub',
    ],
];
