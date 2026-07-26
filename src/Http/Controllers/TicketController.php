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
        $ticket = Ticket::create([
            'title' => $request->string('title')->toString(),
            'description' => $request->input('description'),
            'steps' => $request->input('steps'),
            'status' => TicketStatus::Open,
            'labels' => $request->input('labels', []),
            'assignees' => $request->input('assignees', []),
            'milestone' => $request->input('milestone'),
            'projects' => $request->input('projects', []),
            // Captured automatically — never accepted as free-form user input for what's meant
            // to record where the ticket was actually filed from (SPEC.md §3).
            'page_url' => $request->string('page_url')->toString() ?: $request->headers->get('referer'),
            'app_version' => $request->string('app_version')->toString() ?: config()->string('two-way-ticket.app_version', ''),
            'role' => $request->string('role')->toString(),
        ]);

        return new TicketJsonResource($ticket)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketJsonResource
    {
        return new TicketJsonResource($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketJsonResource
    {
        $ticket->fill($request->only(['title', 'description', 'steps', 'labels', 'assignees', 'milestone', 'projects']));

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
