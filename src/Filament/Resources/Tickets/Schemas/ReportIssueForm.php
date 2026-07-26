<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * The reporting end-user's own, deliberately minimal form — status/assignees/milestone/projects
 * are triage fields for whoever manages the list (see TicketForm), not something a reporter
 * chooses. Labels are the exception: the reporter is best placed to say whether this is a bug,
 * a question or an enhancement.
 */
class ReportIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // First field on purpose: the page the reporter came from, pre-filled but editable —
            // and clearable, since plenty of reports are general rather than tied to the page
            // the button happened to be clicked from.
            TextInput::make('page_url')
                ->label(__('two-way-ticket::two-way-ticket.field.page_url'))
                ->url()
                ->maxLength(2048)
                ->columnSpanFull(),
            TextInput::make('title')
                ->label(__('two-way-ticket::two-way-ticket.field.title'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('description')
                ->label(__('two-way-ticket::two-way-ticket.field.description'))
                ->autosize()
                ->columnSpanFull(),
            Repeater::make('steps')
                ->label(__('two-way-ticket::two-way-ticket.field.steps'))
                ->simple(TextInput::make('step')->required())
                ->columnSpanFull()
                ->addActionLabel(__('two-way-ticket::two-way-ticket.field.add_step')),
            // The full catalogue, not just what's already in use: a reporter filing the very
            // first "question" still needs that option to exist.
            Select::make('labels')
                ->label(__('two-way-ticket::two-way-ticket.field.labels'))
                ->options(fn (): array => Ticket::labelOptions())
                ->multiple()
                ->native(false)
                ->columnSpanFull(),
            FileUpload::make('screenshot_paths')
                ->label(__('two-way-ticket::two-way-ticket.field.screenshots'))
                ->image()
                ->multiple()
                ->disk(fn() => config()->string('two-way-ticket.screenshots.disk', 'public'))
                ->directory(fn() => config()->string('two-way-ticket.screenshots.directory', 'two-way-ticket'))
                ->maxFiles(fn() => config()->integer('two-way-ticket.screenshots.max_count', 5))
                ->columnSpanFull(),
        ])->inlineLabel();
    }
}
