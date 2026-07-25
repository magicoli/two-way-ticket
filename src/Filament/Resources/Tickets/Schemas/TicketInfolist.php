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
            Section::make(__('Details'))
                ->schema([
                    TextEntry::make('title')->columnSpanFull(),
                    TextEntry::make('description')->columnSpanFull()->placeholder('—'),
                    RepeatableEntry::make('steps')
                        ->label(__('Steps to reproduce'))
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('step')->hiddenLabel(),
                        ]),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('priority')->badge()->placeholder('—'),
                    TextEntry::make('labels')->badge()->placeholder('—'),
                    TextEntry::make('milestone')->placeholder('—'),
                    TextEntry::make('page_url')->label(__('Page'))->url(fn (Ticket $record): ?string => $record->page_url)->placeholder('—'),
                    TextEntry::make('app_version'),
                    TextEntry::make('role')->placeholder('—'),
                    TextEntry::make('user.name')->label(__('Reported by'))->placeholder('—'),
                    TextEntry::make('created_at')->label(__('Reported at'))->dateTime(),
                    TextEntry::make('github_issue_url')
                        ->label(__('GitHub issue'))
                        ->url(fn (Ticket $record): ?string => $record->github_issue_url)
                        ->openUrlInNewTab()
                        ->formatStateUsing(fn (?int $state, Ticket $record): string => $record->github_issue_number !== null ? '#'.$record->github_issue_number : '—')
                        ->placeholder('—'),
                    TextEntry::make('resolved_at')->dateTime()->placeholder('—'),
                ])
                ->columns(2),
            Section::make(__('Screenshots'))
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
