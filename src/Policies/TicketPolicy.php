<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * Who may report, and who may triage — two different rights, one policy.
 *
 * This exists because Filament resources delegate authorization to Laravel policies and treat a
 * MISSING policy as "allowed" (see Filament's own note atop
 * Resources/Resource/Concerns/HasAuthorization.php). Shipping no policy therefore handed the
 * backlog to everyone who could reach the panel — including, in a real app, its customers.
 *
 * Replace it the ordinary Laravel way, no API of ours involved:
 *
 *     Gate::policy(\Magicoli\TwoWayTicket\Models\Ticket::class, \App\Policies\TicketPolicy::class);
 *
 * The resource, its pages, the stats widget and the report button all follow, because they all
 * ask the policy.
 */
class TicketPolicy
{
    /**
     * Reporting an issue, through the "Report an issue" page and its minimal form. Any
     * authenticated user, which is what a helpdesk is for — an app that sells support to some
     * members only overrides this one ability.
     *
     * Deliberately NOT `create`: the resource's own create screen is the full triage form, with
     * status, labels, assignees and milestone on it. Someone allowed to report is not thereby
     * allowed to file straight into the backlog with those set.
     */
    public function report(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * The resource's create screen — a triage form, hence a triage right.
     */
    public function create(Authenticatable $user): bool
    {
        return $this->triages($user);
    }

    public function viewAny(Authenticatable $user): bool
    {
        return $this->triages($user);
    }

    public function view(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->triages($user);
    }

    public function update(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->triages($user);
    }

    public function delete(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->triages($user);
    }

    /**
     * Triaging: seeing the whole backlog and acting on it. Administrators by default.
     *
     * There is no framework-standard way to ask "is this user an administrator", so we read the
     * signals a user model usually already carries, most explicit first, and stop at the first
     * one that exists. A model that answers `false` is answering — we do not keep looking for a
     * signal that says yes.
     */
    protected function triages(Authenticatable $user): bool
    {
        if (method_exists($user, 'isAdmin')) {
            return (bool) $user->isAdmin();
        }

        if (method_exists($user, 'hasRole')) {
            return (bool) $user->hasRole('admin');
        }

        $isAdmin = data_get($user, 'is_admin');

        if ($isAdmin !== null) {
            return (bool) $isAdmin;
        }

        return (int) $user->getAuthIdentifier() === 1;
    }
}
