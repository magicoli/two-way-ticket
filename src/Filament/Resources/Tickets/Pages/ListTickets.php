<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Magicoli\TwoWayTicket\Actions\SyncGithubIssues;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStats;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    /**
     * Full width: this table carries a dozen columns and a filter row, and the panel's default
     * max-width left a wide empty margin on either side on desktop.
     */
    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * @return list<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [TicketStats::class];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'open' => Tab::make(__('two-way-ticket::two-way-ticket.tab.open'))
                ->query(fn (Builder $query): Builder => $query->where('status', TicketStatus::Open->value)),
            // A subset of open, not a state of its own: an assigned ticket that's been closed is
            // finished, not in progress. GitHub has no in-progress state, so "someone is on it"
            // is the honest stand-in — and a tab expresses it the same way Open/Closed do,
            // without a filter standing in for what is really a general condition.
            'in_progress' => Tab::make(__('two-way-ticket::two-way-ticket.tab.in_progress'))
                ->query(fn (Builder $query): Builder => $query
                    ->where('status', TicketStatus::Open->value)
                    ->whereJsonLength('assignees', '>', 0)),
            'closed' => Tab::make(__('two-way-ticket::two-way-ticket.tab.closed'))
                ->query(fn (Builder $query): Builder => $query->where('status', TicketStatus::Closed->value)),
            'all' => Tab::make(__('two-way-ticket::two-way-ticket.tab.all')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncGithub')
                ->label(__('two-way-ticket::two-way-ticket.actions.sync_with_github'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    try {
                        $result = resolve(SyncGithubIssues::class)->handle();
                    } catch (\Throwable $throwable) {
                        Notification::make()->danger()->title(__('two-way-ticket::two-way-ticket.actions.sync_failed'))->body($throwable->getMessage())->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title(__('two-way-ticket::two-way-ticket.actions.synced'))
                        ->body(__('two-way-ticket::two-way-ticket.actions.sync_result', $result))
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
