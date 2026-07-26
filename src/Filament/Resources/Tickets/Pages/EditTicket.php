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
        // Destructive first, primary last: with Delete sitting on the right it was the button
        // the eye and the cursor went to, exactly the reflex the top/bottom split was meant to
        // avoid in the first place.
        //
        // Labels are explicit because Filament derives an action's label from its NAME, which
        // never goes through translations. Save uses the package's own string rather than
        // Filament's edit-record one, which reads "Save changes" / "Sauvegarder les
        // modifications" — needlessly long for a header button.
        return [
            DeleteAction::make(),
            Action::make('cancel')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
            ViewAction::make(),
            Action::make('save')
                ->label(__('two-way-ticket::two-way-ticket.actions.save'))
                ->action('save')
                ->keyBindings(['mod+s']),
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
