<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Magicoli\TwoWayTicket\Enums\TicketStatus;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Token middleware already gates the whole route group.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'labels' => ['sometimes', 'array'],
            'labels.*' => ['string'],
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => ['string'],
            'milestone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'projects' => ['sometimes', 'array'],
            'projects.*' => ['string'],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
        ];
    }
}
