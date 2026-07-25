<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * The reporting end-user's own, deliberately minimal form — status/priority/labels/milestone are
 * triage fields for whoever manages the list (see TicketForm), not something a reporter chooses.
 */
class ReportIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label(__('two-way-ticket::two-way-ticket.field.title'))->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->label(__('two-way-ticket::two-way-ticket.field.description'))->autosize()->columnSpanFull(),
            Repeater::make('steps')
                ->label(__('two-way-ticket::two-way-ticket.field.steps'))
                ->simple(TextInput::make('step')->required())
                ->columnSpanFull()
                ->addActionLabel(__('two-way-ticket::two-way-ticket.field.add_step')),
            FileUpload::make('screenshot_paths')
                ->label(__('two-way-ticket::two-way-ticket.field.screenshots'))
                ->image()
                ->multiple()
                ->disk(fn () => config()->string('two-way-ticket.screenshots.disk', 'public'))
                ->directory(fn () => config()->string('two-way-ticket.screenshots.directory', 'two-way-ticket'))
                ->maxFiles(fn () => config()->integer('two-way-ticket.screenshots.max_count', 5))
                ->columnSpanFull(),
        ]);
    }
}
