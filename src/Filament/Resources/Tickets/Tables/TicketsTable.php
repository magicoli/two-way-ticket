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
                TextColumn::make('status')
                    ->label(__('two-way-ticket::two-way-ticket.field.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('state_reason')
                    ->label(__('two-way-ticket::two-way-ticket.field.state_reason'))
                    ->toggleable(),
                TextColumn::make('labels')
                    ->label(__('two-way-ticket::two-way-ticket.field.labels'))
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->toggleable(),
                // Wraps onto extra lines instead of a fixed truncation — SPEC.md §4 wanted "plus
                // de place pour le sujet" but an unwrapped title overflowed every other column.
                TextColumn::make('title')
                    ->label(__('two-way-ticket::two-way-ticket.field.title'))
                    ->searchable()
                    ->weight('medium')
                    ->sortable()
                    ->wrap()
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
                    ->url(fn(Ticket $record): ?string => $record->page_url)
                    ->limit(30)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('github_issue_number')
                    ->label(__('two-way-ticket::two-way-ticket.field.github'))
                    ->formatStateUsing(fn(?int $state): ?string => $state === null ? null : '#' . $state)
                    ->url(fn(Ticket $record): ?string => $record->github_issue_url)
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
                    ->isoDateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters(
                [
                    // Open/Closed/All lives in the page's tabs (see ListTickets::getTabs()) as a
                    // general condition ANDed with these filters, not a replacement for this one
                    // — still useful on its own since GitHub's state has exactly these two values.
                    // A ticket can only have ONE status, so whereIn (SelectFilter's default query)
                    // would be enough — but SelectFilter can't do wrapOptionLabels(), which the
                    // other multi-value filters below need, so this stays a raw Filter+Select too
                    // for consistency between all the multi-value ones.
                    // Filter::make('status')
                    //     ->label(__('two-way-ticket::two-way-ticket.filter.status'))
                    //     ->schema([
                    //         Select::make('values')
                    //             ->label(__('two-way-ticket::two-way-ticket.filter.status'))
                    //             ->placeholder(__('two-way-ticket::two-way-ticket.filter.status'))
                    //             ->native(false)
                    //             ->options(TicketStatus::class)
                    //             ->multiple()
                    //             ->wrapOptionLabels(false),
                    //     ])
                    //     ->query(fn(Builder $query, array $data): Builder => $query->when(
                    //         filled($data['values'] ?? null),
                    //         fn(Builder $query): Builder => $query->whereIn('status', $data['values']),
                    //     )),
                    // labels/assignees/projects: a ticket can carry SEVERAL values at once, stored
                    // as a JSON array — SelectFilter's own whereIn() can't express "this array
                    // contains any of the selected values", so each needs its own whereJsonContains
                    // query, same shape as `status` above.
                    Filter::make('labels')
                        ->label(__('two-way-ticket::two-way-ticket.filter.labels'))
                        ->schema([
                            Select::make('values')
                                ->label(__('two-way-ticket::two-way-ticket.filter.labels'))
                                ->placeholder(__('two-way-ticket::two-way-ticket.filter.labels'))
                                ->native(false)
                                ->options(fn(): array => Ticket::distinctValues('labels'))
                                ->multiple()
                                ->wrapOptionLabels(false),
                        ])
                        ->query(fn(Builder $query, array $data): Builder => $query->when(
                            filled($data['values'] ?? null),
                            fn(Builder $query): Builder => $query->where(function (Builder $query) use ($data): void {
                                foreach ($data['values'] as $label) {
                                    $query->orWhereJsonContains('labels', $label);
                                }
                            }),
                        )),
                    SelectFilter::make('milestone')
                        ->label(__('two-way-ticket::two-way-ticket.field.milestone'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.milestone'))
                        ->native(false)
                        ->options(fn(): array => Ticket::distinctValues('milestone')),
                    Filter::make('projects')
                        ->label(__('two-way-ticket::two-way-ticket.filter.projects'))
                        ->schema([
                            Select::make('values')
                                ->label(__('two-way-ticket::two-way-ticket.filter.projects'))
                                ->placeholder(__('two-way-ticket::two-way-ticket.filter.projects'))
                                ->native(false)
                                ->options(fn(): array => Ticket::distinctValues('projects'))
                                ->multiple(),
                        ])
                        ->query(fn(Builder $query, array $data): Builder => $query->when(
                            filled($data['values'] ?? null),
                            fn(Builder $query): Builder => $query->where(function (Builder $query) use ($data): void {
                                foreach ($data['values'] as $project) {
                                    $query->orWhereJsonContains('projects', $project);
                                }
                            }),
                        )),
                    SelectFilter::make('app_version')
                        ->label(__('two-way-ticket::two-way-ticket.field.app_version'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.app_version'))
                        ->native(false)
                        ->options(
                            fn(): array => Ticket::query()
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
                    Filter::make('assignees')
                        ->label(__('two-way-ticket::two-way-ticket.filter.assignees'))
                        ->schema([
                            Select::make('values')
                                ->label(__('two-way-ticket::two-way-ticket.filter.assignees'))
                                ->placeholder(__('two-way-ticket::two-way-ticket.filter.assignees'))
                                ->native(false)
                                ->options(fn(): array => Ticket::distinctValues('assignees'))
                                ->multiple(),
                        ])
                        ->query(fn(Builder $query, array $data): Builder => $query->when(
                            filled($data['values'] ?? null),
                            fn(Builder $query): Builder => $query->where(function (Builder $query) use ($data): void {
                                foreach ($data['values'] as $assignee) {
                                    $query->orWhereJsonContains('assignees', $assignee);
                                }
                            }),
                        )),
                ],
                layout: FiltersLayout::AboveContent,
            )
            ->filtersFormColumns([
                'xs' => 2,
                'sm' => 4,
                'xl' => 6,
            ])
            ->recordActions([
                // Icon-only row actions, always — labels here are the quintessence of wasted space.
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
                Action::make('pushToGithub')
                    ->label(__('two-way-ticket::two-way-ticket.actions.push_to_github'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->iconButton()
                    ->color('gray')
                    ->visible(fn(Ticket $record): bool => !$record->isLinked())
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
}
