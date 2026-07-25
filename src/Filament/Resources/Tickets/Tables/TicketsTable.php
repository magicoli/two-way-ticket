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
use Magicoli\TwoWayTicket\Enums\TicketPriority;
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
                TextColumn::make('priority')
                    ->label(__('two-way-ticket::two-way-ticket.field.priority'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('page_url')
                    ->label(__('two-way-ticket::two-way-ticket.field.page_url'))
                    ->url(fn(Ticket $record): ?string => $record->page_url)
                    ->limit(30)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('milestone')
                    ->label(__('two-way-ticket::two-way-ticket.field.milestone'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
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
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters(
                [
                    // Only open tickets by default — a closed one only matters when specifically
                    // looked for, and adding "resolved" back to the selection is one click away
                    // right here (Oli, 2026-07-26).
                    SelectFilter::make('status')
                        ->label(__('two-way-ticket::two-way-ticket.field.status'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.status'))
                        ->options(TicketStatus::class)
                        ->multiple()
                        ->default([
                            TicketStatus::New->value,
                            TicketStatus::Triaged->value,
                            TicketStatus::InProgress->value,
                        ]),
                    SelectFilter::make('priority')
                        ->label(__('two-way-ticket::two-way-ticket.field.priority'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.priority'))
                        ->options(TicketPriority::class),
                    Filter::make('labels')
                        ->label(__('two-way-ticket::two-way-ticket.field.labels'))
                        ->schema([
                            Select::make('value')
                                ->label(__('two-way-ticket::two-way-ticket.field.labels'))
                                ->placeholder(__('two-way-ticket::two-way-ticket.filter.labels'))
                                ->options(fn(): array => self::distinctLabelOptions())
                                ->native(false),
                        ])
                        ->query(fn(Builder $query, array $data): Builder => $query->when(
                            filled($data['value'] ?? null),
                            fn(Builder $query): Builder => $query->whereJsonContains('labels', $data['value']),
                        )),
                    SelectFilter::make('milestone')
                        ->label(__('two-way-ticket::two-way-ticket.field.milestone'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.milestone'))
                        ->options(
                            fn(): array => Ticket::query()
                                ->whereNotNull('milestone')
                                ->distinct()
                                ->orderBy('milestone')
                                ->pluck('milestone', 'milestone')
                                ->all(),
                        ),
                    SelectFilter::make('app_version')
                        ->label(__('two-way-ticket::two-way-ticket.field.app_version'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.app_version'))
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
                    ->visible(fn(Ticket $record): bool => !$record->isLinked() && $record->isSyncable())
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
     * @return array<string, string>
     */
    private static function distinctLabelOptions(): array
    {
        return Ticket::query()
            ->whereNotNull('labels')
            ->pluck('labels')
            ->flatMap(fn(array $labels): array => $labels)
            ->unique()
            ->sort()
            ->mapWithKeys(fn(string $label): array => [$label => $label])
            ->all();
    }
}
