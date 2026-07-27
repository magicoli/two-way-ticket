<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Url;
use Magicoli\TwoWayTicket\Actions\SyncGithubIssues;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Widgets\TicketStatsWidget;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    /** Open tickets are what you want on arrival; a closed one only matters when looked for. */
    private const DEFAULT_VIEW = 'open';

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
        return [TicketStatsWidget::class];
    }

    /**
     * Which slice of the backlog is on screen. Was a row of tabs, but the stats above say the
     * same thing and can be clicked — two rows of controls for one choice is one too many, so
     * the stats ARE the control now.
     *
     * Still a URL parameter, so a view stays linkable and survives a reload.
     */
    #[Url(as: 'view')]
    public ?string $scope = null;

    /**
     * Applied here rather than through Filament's tabs, since dropping the tab bar drops the
     * query with it. Deliberately NOT a table filter: this is a general condition on the whole
     * list, and it ANDs with whatever the filter row is doing.
     */
    protected function getTableQuery(): Builder|Relation|null
    {
        $query = parent::getTableQuery();

        if ($query === null) {
            return null;
        }

        return match ($this->scope ?? self::DEFAULT_VIEW) {
            'open' => $query->where('status', TicketStatus::Open->value),
            // A subset of open, not a state of its own: a ticket someone was assigned to and
            // which then got closed is finished, not in progress.
            'in_progress' => $query->where('status', TicketStatus::Open->value)->whereJsonLength('assignees', '>', 0),
            'closed' => $query->where('status', TicketStatus::Closed->value),
            default => $query,
        };
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
                        Notification::make()
                            ->danger()
                            ->title(__('two-way-ticket::two-way-ticket.actions.sync_failed'))
                            ->body($throwable->getMessage())
                            ->send();

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
