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
            Section::make(__('two-way-ticket::two-way-ticket.field.details'))
                ->schema([
                    TextInput::make('title')->label(__('two-way-ticket::two-way-ticket.field.title'))->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->label(__('two-way-ticket::two-way-ticket.field.description'))->autosize()->columnSpanFull(),
                    Repeater::make('steps')
                        ->label(__('two-way-ticket::two-way-ticket.field.steps'))
                        ->simple(TextInput::make('step')->required())
                        ->columnSpanFull()
                        ->addActionLabel(__('two-way-ticket::two-way-ticket.field.add_step')),
                    Select::make('status')->label(__('two-way-ticket::two-way-ticket.field.status'))->options(TicketStatus::class)->native(false)->required(),
                    Select::make('priority')->label(__('two-way-ticket::two-way-ticket.field.priority'))->options(TicketPriority::class)->native(false),
                    TagsInput::make('labels')->label(__('two-way-ticket::two-way-ticket.field.labels'))->columnSpanFull(),
                    TextInput::make('milestone')->label(__('two-way-ticket::two-way-ticket.field.milestone')),
                ])
                ->columns(2),
            Section::make(__('two-way-ticket::two-way-ticket.field.screenshots'))
                ->schema([
                    FileUpload::make('screenshot_paths')
                        ->hiddenLabel()
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
