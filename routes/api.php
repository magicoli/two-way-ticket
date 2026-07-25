<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Magicoli\TwoWayTicket\Http\Controllers\TicketController;
use Magicoli\TwoWayTicket\Http\Middleware\AuthorizeTwoWayTicketApiToken;

// Not wrapped in Laravel's 'api' middleware group (loaded via loadRoutesFrom, outside the host
// app's own bootstrap/app.php routing config) — stateless on purpose, no session/CSRF concerns,
// just the bearer token below. SubstituteBindings is added explicitly since it's normally part
// of that 'api' group — without it, {ticket} never resolves to the actual record.
Route::prefix('api/'.config('two-way-ticket.api.route_prefix'))
    ->middleware([AuthorizeTwoWayTicketApiToken::class, SubstituteBindings::class])
    ->group(function (): void {
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/{ticket}', [TicketController::class, 'show']);
        Route::patch('/{ticket}', [TicketController::class, 'update']);
    });
