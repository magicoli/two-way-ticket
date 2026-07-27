<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Tests\Fixtures;

/**
 * A user model exposing `isAdmin()`, the first signal the default policy looks for.
 *
 * The flag is a plain property, not an attribute: the point is the METHOD being there, which is
 * how the policy recognises apps that already answer this question themselves.
 */
class AdminAwareUser extends User
{
    protected $table = 'users';

    public bool $admin = false;

    public function isAdmin(): bool
    {
        return $this->admin;
    }
}
