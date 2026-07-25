<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
            ->columns([
                // Wide, unwrapped title — SPEC.md §4: "plus de place pour le sujet".
                TextColumn::make('title')
                    ->searchable()
                    ->weight('medium')
                    ->grow(),
                TextColumn::make('labels')
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('priority')->badge()->sortable()->placeholder('—'),
                TextColumn::make('page_url')
                    ->label(__('Page'))
                    ->url(fn (Ticket $record): ?string => $record->page_url)
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('milestone')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('github_issue_number')
                    ->label(__('GitHub'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : '#'.$state)
                    ->url(fn (Ticket $record): ?string => $record->github_issue_url)
                    ->openUrlInNewTab()
                    ->badge()
                    ->color('success'),
                TextColumn::make('app_version')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')->label(__('Reported by'))->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label(__('Reported at'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(TicketStatus::class),
                SelectFilter::make('priority')->options(TicketPriority::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('pushToGithub')
                    ->label(__('Push to GitHub'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->visible(fn (Ticket $record): bool => ! $record->isLinked() && $record->isSyncable())
                    ->action(function (Ticket $record): void {
                        try {
                            resolve(CreateGithubIssue::class)->handle($record);
                        } catch (\Throwable $throwable) {
                            Notification::make()->danger()->title(__('Could not push to GitHub'))->body($throwable->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title(__('Pushed to GitHub'))->send();
                    }),
            ]);
    }
}
