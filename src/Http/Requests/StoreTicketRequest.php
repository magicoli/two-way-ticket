<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Magicoli\TwoWayTicket\Enums\TicketPriority;

class StoreTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'steps' => ['nullable', 'array'],
            'steps.*' => ['string'],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'app_version' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
        ];
    }
}
