<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * @mixin Ticket
 */
class TicketJsonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'steps' => $this->steps,
            'status' => $this->status->value,
            'labels' => $this->labels ?? [],
            'milestone' => $this->milestone,
            'assignees' => $this->assignees ?? [],
            'projects' => $this->projects ?? [],
            'page_url' => $this->page_url,
            'app_version' => $this->app_version,
            'role' => $this->role,
            'reported_by' => $this->user?->name,
            'github_issue_url' => $this->github_issue_url,
            'github_issue_number' => $this->github_issue_number,
            'github_state_reason' => $this->github_state_reason,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
