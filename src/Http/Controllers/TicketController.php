<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Http\Requests\StoreTicketRequest;
use Magicoli\TwoWayTicket\Http\Requests\UpdateTicketRequest;
use Magicoli\TwoWayTicket\Http\Resources\TicketJsonResource;
use Magicoli\TwoWayTicket\Models\Ticket;

class TicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ticket::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('label')) {
            $query->whereJsonContains('labels', $request->string('label')->toString());
        }

        return TicketJsonResource::collection($query->paginate($request->integer('per_page', 25)));
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        // Captured automatically — never accepted as free-form user input for what's meant to
        // record where the ticket was actually filed from (DEVELOPERS.md §3).
        $pageUrl = $request->string('page_url')->toString() ?: $request->headers->get('referer');
        $appVersion = $request->string('app_version')->toString() ?: Ticket::reportingAppVersion();

        $ticket = Ticket::create([
            'title' => $request->string('title')->toString(),
            // Composed once here, from the structured fields; an ordinary field from then on.
            'description' => Ticket::composeDescription(
                $request->input('description'),
                $request->input('steps', []),
                $pageUrl,
                $appVersion,
            ),
            'status' => TicketStatus::Open,
            'labels' => $request->input('labels', []),
            'assignees' => $request->input('assignees', []),
            'milestone' => $request->input('milestone'),
            'projects' => $request->input('projects', []),
            'page_url' => $pageUrl,
            'app_version' => $appVersion,
            'role' => $request->string('role')->toString(),
        ]);

        // Same reason as Ticket::distinctValues(): chaining off `new` is 8.4-only syntax, and a
        // formatter will strip the parentheses that would otherwise keep it 8.3-compatible.
        $resource = new TicketJsonResource($ticket);

        return $resource->response()->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketJsonResource
    {
        return new TicketJsonResource($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketJsonResource
    {
        $ticket->fill($request->only(['title', 'description', 'labels', 'assignees', 'milestone', 'projects']));

        if ($request->has('status')) {
            $ticket->status = TicketStatus::from($request->string('status')->toString());
            $ticket->closed_at = $ticket->status === TicketStatus::Closed
                ? ($ticket->closed_at ?? now())
                : null;
        }

        $ticket->save();

        return new TicketJsonResource($ticket);
    }
}
