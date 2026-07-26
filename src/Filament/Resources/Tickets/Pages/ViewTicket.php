<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    /** The record's own title, never prefixed with "View"/"Afficher" — the page context is obvious. */
    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
