<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Magicoli\TwoWayTicket\Enums\TicketPriority;
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
            'steps' => ['sometimes', 'nullable', 'array'],
            'steps.*' => ['string'],
            'priority' => ['sometimes', 'nullable', Rule::enum(TicketPriority::class)],
            'labels' => ['sometimes', 'array'],
            'labels.*' => ['string'],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
        ];
    }
}
