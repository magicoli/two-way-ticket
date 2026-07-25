<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Magicoli\TwoWayTicket\Actions\SyncGithubIssues;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncGithub')
                ->label(__('Sync with GitHub'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    try {
                        $result = resolve(SyncGithubIssues::class)->handle();
                    } catch (\Throwable $throwable) {
                        Notification::make()->danger()->title(__('Sync failed'))->body($throwable->getMessage())->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title(__('Synced'))
                        ->body(__(':updated updated, :imported imported from GitHub.', $result))
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
