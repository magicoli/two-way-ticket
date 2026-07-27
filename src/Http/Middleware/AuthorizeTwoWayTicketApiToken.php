<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A single static bearer token, not Sanctum/OAuth — see config('two-way-ticket.api') docblock.
 * Fails closed: an unconfigured token (empty .env) rejects every request rather than accepting
 * any.
 */
class AuthorizeTwoWayTicketApiToken
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('two-way-ticket.api.token', '');
        $given = (string) $request->bearerToken();

        if ($configured === '' || !hash_equals($configured, $given)) {
            abort(401, 'Invalid or missing Two-Way Ticket API token.');
        }

        return $next($request);
    }
}
