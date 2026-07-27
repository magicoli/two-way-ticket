<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Tests\Fixtures;

/**
 * A user the app calls an administrator, for the tests that exercise triage screens.
 *
 * The plain {@see User} fixture says nothing about roles, so the default policy falls back to
 * "user id 1" — which depends on creation order and breaks the moment a factory creates a
 * reporter first. Tests about the ticket screens should not depend on that.
 */
class AdminUser extends User
{
    protected $table = 'users';

    public function isAdmin(): bool
    {
        return true;
    }
}
