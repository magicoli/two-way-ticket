<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * No title entry (the page title IS the ticket title, never repeated as a field) and no
 * sections — flat, the house rules.
 */
class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                TicketHeader::make(),
                Group::make([
                    TextEntry::make('description')
                        ->hiddenLabel()
                        ->label(__('two-way-ticket::two-way-ticket.field.description'))
                        ->markdown()
                        ->columnSpanFull(),
                    Group::make([
                        TextEntry::make('page_url')->label(__(
                            'two-way-ticket::two-way-ticket.field.page_url',
                        ))->url(fn(Ticket $record): ?string => $record->page_url),
                        TextEntry::make('app_version')->label(__('two-way-ticket::two-way-ticket.field.app_version')),
                    ])->inlineLabel(),
                    RepeatableEntry::make('screenshot_paths')
                        ->label(__('two-way-ticket::two-way-ticket.field.screenshots'))
                        ->columnSpanFull()
                        ->visible(fn(Ticket $record): bool => filled($record->screenshot_paths))
                        ->schema([
                            ImageEntry::make('path')
                                ->hiddenLabel()
                                ->disk(fn() => config()->string('two-way-ticket.screenshots.disk', 'public'))
                                ->height('12rem'),
                        ]),
                ])->columnSpan(3),
                Group::make([
                    TextEntry::make('assignees')->label(__('two-way-ticket::two-way-ticket.field.assignees'))->badge(),
                    TextEntry::make('labels')->label(__('two-way-ticket::two-way-ticket.field.labels'))->badge(),
                    TextEntry::make('projects')->label(__('two-way-ticket::two-way-ticket.field.projects'))->badge(),
                    TextEntry::make('milestone')->label(__('two-way-ticket::two-way-ticket.field.milestone')),
                ]),
            ]);
    }
}
