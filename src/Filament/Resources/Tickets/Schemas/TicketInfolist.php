<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Magicoli\TwoWayTicket\Models\Ticket;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('two-way-ticket::two-way-ticket.field.details'))
                ->schema([
                    TextEntry::make('title')->label(__('two-way-ticket::two-way-ticket.field.title'))->columnSpanFull(),
                    TextEntry::make('description')->label(__('two-way-ticket::two-way-ticket.field.description'))->columnSpanFull()->placeholder('—'),
                    RepeatableEntry::make('steps')
                        ->label(__('two-way-ticket::two-way-ticket.field.steps'))
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('step')->hiddenLabel(),
                        ]),
                    TextEntry::make('status')->label(__('two-way-ticket::two-way-ticket.field.status'))->badge(),
                    TextEntry::make('priority')->label(__('two-way-ticket::two-way-ticket.field.priority'))->badge()->placeholder('—'),
                    TextEntry::make('labels')->label(__('two-way-ticket::two-way-ticket.field.labels'))->badge()->placeholder('—'),
                    TextEntry::make('milestone')->label(__('two-way-ticket::two-way-ticket.field.milestone'))->placeholder('—'),
                    TextEntry::make('page_url')->label(__('two-way-ticket::two-way-ticket.field.page_url'))->url(fn (Ticket $record): ?string => $record->page_url)->placeholder('—'),
                    TextEntry::make('app_version')->label(__('two-way-ticket::two-way-ticket.field.app_version')),
                    TextEntry::make('role')->label(__('two-way-ticket::two-way-ticket.field.role'))->placeholder('—'),
                    TextEntry::make('user.name')->label(__('two-way-ticket::two-way-ticket.field.reported_by'))->placeholder('—'),
                    TextEntry::make('created_at')->label(__('two-way-ticket::two-way-ticket.field.reported_at'))->dateTime(),
                    TextEntry::make('github_issue_url')
                        ->label(__('two-way-ticket::two-way-ticket.field.github_issue'))
                        ->url(fn (Ticket $record): ?string => $record->github_issue_url)
                        ->openUrlInNewTab()
                        ->formatStateUsing(fn (?int $state, Ticket $record): string => $record->github_issue_number !== null ? '#'.$record->github_issue_number : '—')
                        ->placeholder('—'),
                    TextEntry::make('resolved_at')->label(__('two-way-ticket::two-way-ticket.field.resolved_at'))->dateTime()->placeholder('—'),
                ])
                ->columns(2),
            Section::make(__('two-way-ticket::two-way-ticket.field.screenshots'))
                ->schema([
                    RepeatableEntry::make('screenshot_paths')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->schema([
                            ImageEntry::make('path')
                                ->hiddenLabel()
                                ->disk(fn () => config()->string('two-way-ticket.screenshots.disk', 'public'))
                                ->height('12rem'),
                        ]),
                ])
                ->visible(fn (Ticket $record): bool => filled($record->screenshot_paths)),
        ]);
    }
}
