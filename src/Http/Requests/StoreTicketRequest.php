<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['string'],
            'milestone' => ['nullable', 'string', 'max:255'],
            'projects' => ['nullable', 'array'],
            'projects.*' => ['string'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'app_version' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
        ];
    }
}
