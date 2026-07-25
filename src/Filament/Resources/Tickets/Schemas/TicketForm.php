<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Magicoli\TwoWayTicket\Enums\TicketPriority;
use Magicoli\TwoWayTicket\Enums\TicketStatus;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Details'))
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->autosize()->columnSpanFull(),
                    Repeater::make('steps')
                        ->simple(TextInput::make('step')->required())
                        ->columnSpanFull()
                        ->addActionLabel(__('Add step')),
                    Select::make('status')->options(TicketStatus::class)->native(false)->required(),
                    Select::make('priority')->options(TicketPriority::class)->native(false),
                    TagsInput::make('labels')->columnSpanFull(),
                    TextInput::make('milestone'),
                ])
                ->columns(2),
            Section::make(__('Screenshots'))
                ->schema([
                    FileUpload::make('screenshot_paths')
                        ->image()
                        ->multiple()
                        ->disk(fn () => config()->string('two-way-ticket.screenshots.disk', 'public'))
                        ->directory(fn () => config()->string('two-way-ticket.screenshots.directory', 'two-way-ticket'))
                        ->maxFiles(fn () => config()->integer('two-way-ticket.screenshots.max_count', 5))
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
