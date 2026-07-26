<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Magicoli\TwoWayTicket\Actions\CreateGithubIssue;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Models\Ticket;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('two-way-ticket::two-way-ticket.table.empty'))
            ->columns([
                // Wide, unwrapped title — SPEC.md §4: "plus de place pour le sujet".
                TextColumn::make('status')
                    ->label(__('two-way-ticket::two-way-ticket.field.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('labels')
                    ->label(__('two-way-ticket::two-way-ticket.field.labels'))
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('two-way-ticket::two-way-ticket.field.title'))
                    ->searchable()
                    ->weight('medium')
                    ->sortable()
                    ->grow(),
                TextColumn::make('assignees')
                    ->label(__('two-way-ticket::two-way-ticket.field.assignees'))
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('milestone')
                    ->label(__('two-way-ticket::two-way-ticket.field.milestone'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('projects')
                    ->label(__('two-way-ticket::two-way-ticket.field.projects'))
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('page_url')
                    ->label(__('two-way-ticket::two-way-ticket.field.page_url'))
                    ->url(fn (Ticket $record): ?string => $record->page_url)
                    ->limit(30)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('github_issue_number')
                    ->label(__('two-way-ticket::two-way-ticket.field.github'))
                    ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : '#'.$state)
                    ->url(fn (Ticket $record): ?string => $record->github_issue_url)
                    ->openUrlInNewTab()
                    ->badge()
                    ->sortable()
                    ->color('success'),
                TextColumn::make('app_version')
                    ->label(__('two-way-ticket::two-way-ticket.field.app_version'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label(__('two-way-ticket::two-way-ticket.field.reported_by'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('two-way-ticket::two-way-ticket.field.reported_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters(
                [
                    // Open/Closed/All lives in the page's tabs (see ListTickets::getTabs()) as a
                    // general condition ANDed with these filters, not a replacement for this one
                    // — still useful on its own since GitHub's state has exactly these two
                    // values. Custom Filter+Select (not SelectFilter) so wrapOptionLabels(false)
                    // keeps a multi-selection on one line.
                    Filter::make('status')
                        ->label(__('two-way-ticket::two-way-ticket.filter.status'))
                        ->schema([
                            Select::make('values')
                                ->label(__('two-way-ticket::two-way-ticket.filter.status'))
                                ->placeholder(__('two-way-ticket::two-way-ticket.filter.status'))
                                ->native(false)
                                ->options(TicketStatus::class)
                                ->multiple()
                                ->wrapOptionLabels(false),
                        ])
                        ->query(fn (Builder $query, array $data): Builder => $query->when(
                            filled($data['values'] ?? null),
                            fn (Builder $query): Builder => $query->whereIn('status', $data['values']),
                        )),
                    self::jsonColumnFilter('labels', fn (): array => self::distinctJsonOptions('labels', config()->array('two-way-ticket.github.default_labels', []))),
                    self::jsonColumnFilter('assignees', fn (): array => self::distinctJsonOptions('assignees')),
                    self::jsonColumnFilter('projects', fn (): array => self::distinctJsonOptions('projects')),
                    SelectFilter::make('milestone')
                        ->label(__('two-way-ticket::two-way-ticket.field.milestone'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.milestone'))
                        ->native(false)
                        ->options(
                            fn (): array => Ticket::query()
                                ->whereNotNull('milestone')
                                ->distinct()
                                ->orderBy('milestone')
                                ->pluck('milestone', 'milestone')
                                ->all(),
                        ),
                    SelectFilter::make('app_version')
                        ->label(__('two-way-ticket::two-way-ticket.field.app_version'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.app_version'))
                        ->native(false)
                        ->options(
                            fn (): array => Ticket::query()
                                ->whereNotNull('app_version')
                                ->where('app_version', '!=', '')
                                ->distinct()
                                ->orderBy('app_version')
                                ->pluck('app_version', 'app_version')
                                ->all(),
                        ),
                    SelectFilter::make('user')
                        ->label(__('two-way-ticket::two-way-ticket.field.reported_by'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.user'))
                        ->native(false)
                        ->relationship('user', 'name'),
                ],
                layout: FiltersLayout::AboveContent,
            )
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
                'xl' => 6,
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('pushToGithub')
                    ->label(__('two-way-ticket::two-way-ticket.actions.push_to_github'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->visible(fn (Ticket $record): bool => ! $record->isLinked())
                    ->action(function (Ticket $record): void {
                        try {
                            resolve(CreateGithubIssue::class)->handle($record);
                        } catch (\Throwable $throwable) {
                            Notification::make()
                                ->danger()
                                ->title(__('two-way-ticket::two-way-ticket.actions.could_not_push'))
                                ->body($throwable->getMessage())
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title(__('two-way-ticket::two-way-ticket.actions.pushed_to_github'))
                            ->send();
                    }),
            ]);
    }

    /**
     * A JSON-array column (labels/assignees/projects) filtered by "carries ANY of the selected
     * values" — same shape for all three, factored out to avoid repeating the query closure.
     */
    private static function jsonColumnFilter(string $column, \Closure $options): Filter
    {
        return Filter::make($column)
            ->label(__('two-way-ticket::two-way-ticket.filter.'.$column))
            ->schema([
                Select::make('values')
                    ->label(__('two-way-ticket::two-way-ticket.filter.'.$column))
                    ->placeholder(__('two-way-ticket::two-way-ticket.filter.'.$column))
                    ->native(false)
                    ->options($options)
                    ->multiple()
                    ->wrapOptionLabels(false),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query->when(
                filled($data['values'] ?? null),
                fn (Builder $query): Builder => $query->where(function (Builder $query) use ($column, $data): void {
                    foreach ($data['values'] as $value) {
                        $query->orWhereJsonContains($column, $value);
                    }
                }),
            ));
    }

    /**
     * @param  list<string>  $seed
     * @return array<string, string>
     */
    private static function distinctJsonOptions(string $column, array $seed = []): array
    {
        return Ticket::query()
            ->whereNotNull($column)
            ->pluck($column)
            ->flatMap(fn (array $values): array => $values)
            ->concat($seed)
            ->unique()
            ->sort()
            ->mapWithKeys(fn (string $value): array => [$value => $value])
            ->all();
    }
}
