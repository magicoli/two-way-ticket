# Two-Way Ticket — cahier des charges

Package Filament autonome (pas un fork de `cerealkiller97/filament-bug-reports`, pas une
dépendance dessus), pensé pour être réutilisable tel quel dans n'importe quel projet Filament —
pas seulement word-up. Remplace `cerealkiller97/filament-bug-reports` +
`magicoli/filament-bug-reports-api` par un seul package cohérent.

Principe directeur : **s'aligner sur GitHub, pas réinventer en parallèle.** Label, assignee,
milestone, projects — GitHub gère déjà ces quatre concepts ; le package les reflète plutôt que
d'inventer ses propres équivalents qui finiraient par entrer en collision.

Constat de départ (2026-07-25, sur word-up) : le plugin actuel n'a ni catégorisation ouverte, ni
vue complète, ni sync GitHub bidirectionnelle, ni commentaires.

## 1. Labels (pas une catégorie fermée)

Pas un enum fixe — des **labels**, ouverts, alignés sur les labels du repo GitHub configuré (ou,
si pas encore de repo, les labels standard GitHub : `bug`, `documentation`, `duplicate`,
`enhancement`, `question`, `help wanted`, `wontfix`, `invalid`, `good first issue`... —
`config('two-way-ticket.github.default_labels')`). Un ticket peut porter plusieurs labels, comme
sur GitHub.

`billing` est un exemple de label custom qu'on peut AJOUTER localement, puisqu'il n'existe pas
dans le set standard GitHub. Lier un ticket à GitHub est toujours une action manuelle et explicite
("Push to GitHub") — une fois lié, tout se synchronise, labels compris, sans filtrage séparé. Un
ticket qu'on ne veut jamais voir sur GitHub reste simplement... jamais lié.

## 2. Statut — la copie conforme de GitHub, rien de plus

Oli, 2026-07-26 : "Où est-ce que ça apparaît dans GitHub?" — GitHub n'a que deux valeurs pour
`issue.state` : `open` et `closed`. Pas de "New/Triaged/In progress/Resolved" — c'était une
invention du package précédent, pas quelque chose que GitHub track. Le statut local EST cette
même valeur, littéralement, pour un ticket lié comme pour un ticket purement local.

`issue.state_reason` (`completed`/`not_planned`/`reopened`/`null`) est mirroré tel quel dans
`github_state_reason`, jamais interprété en un statut local — la raison d'une fermeture (wontfix,
duplicate...) reste dans les labels, pas dans une catégorie parallèle.

## 3. Champs

- `title`
- `description` — description générale du problème/demande
- `steps` — étapes de reproduction, **séparées** de la description (les deux existent, pas fusionnées)
- `status` (point 2) — `open`/`closed`, rien d'autre
- `labels` (point 1) — tableau JSON, pas une seule colonne
- `assignees` — aligné sur GitHub Assignees (tableau de logins, qui prend en charge le ticket)
- `milestone` — aligné sur GitHub Milestone (point 5)
- `projects` — aligné sur GitHub Projects (point 6)
- `page_url` — l'URL de la page où le bug a été signalé, capturée automatiquement depuis la requête
  au moment du signalement (jamais saisie à la main)
- `app_version`, `role`, `reported_by`
- `screenshots` — pluriel, plusieurs captures possibles (le champ actuel est un unique champ
  d'upload, à remplacer)
- `github_issue_url`/`github_issue_number` (nullable — seulement si poussé)
- `created_at`/`updated_at`

## 4. Liste (table)

L'actuelle affiche déjà priorité/github/état/version/signalé par/signalé le — ce n'est PAS ce qui
manque (correction : ces champs manquent dans la VUE DÉTAIL, pas la liste). Ce qui manque
réellement dans la liste :

- Une colonne **label** séparée de la colonne **statut** (aujourd'hui les deux sont confondues dans
  une seule colonne)
- La **page d'origine** (`page_url`)
- Une action plus complète qu'un simple "envoyer sur GitHub" (à affiner — probablement une action
  groupée labels/assignee/milestone/projects, pas juste la création d'issue)
- Filtres : label, statut, assignee, milestone, project, page

## 5. Vue détail

Une vraie page de vue (pas seulement le formulaire d'édition) :

- Tous les champs affichés, sans troncature (priorité/github/état/version/signalé
  par/signalé le manquent ICI aujourd'hui, contrairement à la liste)
- Les captures d'écran affichées comme une vraie galerie (lightbox/grid), pas un champ d'upload
- Fil de commentaires en dessous

## 6. Commentaires

- Ajout/lecture de commentaires locaux sur n'importe quel ticket
- Pour un ticket lié à GitHub : synchronisation bidirectionnelle — un commentaire ajouté ici part
  aussi sur l'issue GitHub, et un commentaire ajouté sur GitHub apparaît ici

**Dépend de ça (Oli, 2026-07-26) : indiquer le commit qui corrige, à la clôture.** Le modal de
clôture proposerait un champ hash pré-rempli avec le dernier commit, publié en commentaire
(« Fixed in `a1b2c3d` ») comme le ferait un humain. Volontairement PAS une colonne locale : ce
n'est pas un concept GitHub, et un champ qui ne part jamais sur l'issue romprait l'alignement.
Reporté tant que les commentaires n'existent pas.

## 7. GitHub Projects — router un ticket vers le bon repo/projet

GitHub Projects (le tableau, pas "un projet logiciel") permet de regrouper des issues à travers
PLUSIEURS repos. Cas d'usage réel : un bug signalé depuis word-up concerne en fait
`pest-failure-summary`, ou ce nouveau package lui-même — le ticket doit pouvoir être rattaché au
bon Project GitHub (et donc, indirectement, router vers le bon repo cible), pas forcément rester
lié au repo configuré par défaut pour l'app hôte.

## 8. Sync GitHub — vraiment bidirectionnel, avec webhook

État actuel (à corriger) : `SyncBugReportGithubIssues` ne fait que RAFRAÎCHIR le statut des
tickets DÉJÀ liés (`whereNotNull('github_issue_number')`) — une issue créée directement sur
GitHub, sans ticket local, n'apparaît jamais ici.

Cible :
- **Local → GitHub** : créer l'issue, la fermer/rouvrir selon le statut local, pousser labels/
  assignee/milestone/project, pousser les commentaires
- **GitHub → Local** : importer toute issue du repo/projet configuré qui n'a pas encore de ticket
  local correspondant — plus de ticket qui n'existe QUE sur GitHub. Sync des labels/assignee/
  milestone/project/commentaires dans les deux sens.
- **Webhook GitHub** en plus du sync périodique existant — temps réel plutôt que polling, "autant
  faire les choses bien"

## 9. Portée / réutilisabilité

- Package indépendant, publiable (comme les deux actuels), pensé pour d'autres projets/personnes
  dès le départ — pas de couplage avec quoi que ce soit de spécifique à word-up
- Pas de tenancy intégrée par défaut (le ticket #17/#23 de word-up a montré les dégâts d'une
  tenant-scoping mal maîtrisée) — si un hôte a besoin de scoper par tenant, c'est à lui de le
  faire, le package reste agnostique là-dessus par défaut

**Deux plugins Filament distincts, pas un seul comme cerealkiller** (Oli, 2026-07-25) :
- `TicketsPlugin` — enregistre la ressource (liste/gestion complète), à attacher uniquement aux
  panels qui doivent réellement trier les tickets (typiquement 'admin' seul)
- `ReportIssuePlugin` — ajoute le bouton "Signaler un problème" + une page de signalement
  autonome, à attacher à TOUS les panels. Emplacement par défaut : `USER_MENU_BEFORE` (dans le
  menu utilisateur, avant l'en-tête de profil) — configurable via `->renderHook()`. Reste un cas à
  gérer côté hôte pour les pages anonymes (pas de menu utilisateur à préfixer) — même situation
  que cerealkiller avec son propre `GLOBAL_SEARCH_AFTER`, pas résolu génériquement par le package

Cette séparation évite structurellement la classe de bug #17/#23 : un panel qui n'a que
`ReportIssuePlugin` n'a JAMAIS `TicketResource` enregistrée dessus, donc ne peut jamais subir de
tenant-scoping involontaire.

## Questions ouvertes (à trancher avant de coder)

1. Labels : synchronisés en LECTURE depuis les labels existants du repo (pas de création de label
   GitHub depuis l'app), ou peut-on aussi créer un nouveau label GitHub depuis l'app hôte ?
2. Webhook : payload/signature GitHub, endpoint à exposer par le package — écouter quels events
   exactement (issues, issue_comment, au minimum) ?
3. Screenshots : combien au maximum, quel stockage (disk Laravel configurable, comme aujourd'hui) ?

## Nom

**Two-Way Ticket** (confirmé, 2026-07-25) — clin d'œil à *One Way Ticket* (Eruption, 1979 — repris
de Neil Sedaka, 1959), et le nom
décrit littéralement la fonctionnalité centrale : la sync bidirectionnelle avec GitHub.
Repo : `magicoli/two-way-ticket`.
