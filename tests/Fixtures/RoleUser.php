<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Tests\Fixtures;

/**
 * A user model exposing `hasRole()`, as spatie/laravel-permission does — the second signal the
 * default policy looks for, and the reason the package depends on no role package at all.
 */
class RoleUser extends User
{
    protected $table = 'users';

    /** @var array<int, string> */
    public array $roles = [];

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
}
