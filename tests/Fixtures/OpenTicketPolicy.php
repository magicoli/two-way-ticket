<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * What a host app writes when its own rules differ from ours — here, the simplest possible one:
 * everybody triages, nobody reports.
 */
class OpenTicketPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function report(Authenticatable $user): bool
    {
        return false;
    }
}
