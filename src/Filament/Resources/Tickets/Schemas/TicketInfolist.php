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
                    // page_url and app_version live in the header now (badge + link, no labels):
                    // they were showing twice, once here and once in the description's own
                    // detail list, stacked one under the other.
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
                    // Neutral badges: colour here would highlight without distinguishing (see
                    // the same note on the labels column in TicketsTable).
                    TextEntry::make('assignees')->label(__('two-way-ticket::two-way-ticket.field.assignees'))->badge()->color('gray'),
                    TextEntry::make('labels')->label(__('two-way-ticket::two-way-ticket.field.labels'))->badge()->color('gray'),
                    TextEntry::make('projects')->label(__('two-way-ticket::two-way-ticket.field.projects'))->badge()->color('gray'),
                    TextEntry::make('milestone')->label(__('two-way-ticket::two-way-ticket.field.milestone')),
                ]),
            ]);
    }
}
