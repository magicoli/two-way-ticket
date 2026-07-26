<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Magicoli\TwoWayTicket\Actions\UpdateGithubIssue;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Models\Ticket;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    /** The record's own title, never prefixed with "Edit"/"Modifier" — the page context is obvious. */
    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

    /** All buttons grouped together at the top — never the Laravel-default top/bottom split. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->action('save')->keyBindings(['mod+s']),
            ViewAction::make(),
            Action::make('cancel')->color('gray')->url(static::getResource()::getUrl('index')),
            DeleteAction::make(),
        ];
    }

    public function getFormActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->getRecord();

        if (!$ticket->isLinked()) {
            return;
        }

        try {
            resolve(UpdateGithubIssue::class)->handle($ticket);
        } catch (\Throwable $throwable) {
            Notification::make()
                ->danger()
                ->persistent()
                ->title(__('two-way-ticket::two-way-ticket.actions.could_not_push'))
                ->body($throwable->getMessage())
                ->send();
        }
    }
}
