<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Magicoli\TwoWayTicket\Models\Ticket;

/**
 * Same read-only header as the view page ({@see TicketHeader}), then the editable fields.
 *
 * Inline labels, applied at schema level so it stays the default for anything added later.
 *
 * assignees/labels/projects/milestone are NOT free tag inputs: they're picked from fixed lists.
 * Until each gets its own controlled way to add an option (a label through its own procedure, an
 * assignee limited to local users allowed to manage tickets AND carrying a linked GitHub
 * account), the option list is simply the values already present across tickets.
 *
 * No steps field: steps are formatted into the description at report time (see ReportIssue), so
 * the description alone round-trips with GitHub without any special-casing.
 */
class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->inlineLabel(false) // Keep labels separate from inputs in this specific form
            ->components([
                TicketHeader::make(),
                Group::make([
                    TextInput::make('title')
                        ->label(__('two-way-ticket::two-way-ticket.field.title'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label(__('two-way-ticket::two-way-ticket.field.description'))
                        ->autosize(),
                    FileUpload::make('screenshot_paths')
                        ->label(__('two-way-ticket::two-way-ticket.field.screenshots'))
                        ->image()
                        ->multiple()
                        ->disk(fn() => config()->string('two-way-ticket.screenshots.disk', 'public'))
                        ->directory(fn() => config()->string('two-way-ticket.screenshots.directory', 'two-way-ticket'))
                        ->maxFiles(fn() => config()->integer('two-way-ticket.screenshots.max_count', 5)),
                ])->columnSpan(2),
                Group::make([
                    Select::make('assignees')
                        ->label(__('two-way-ticket::two-way-ticket.field.assignees'))
                        ->options(fn(): array => Ticket::distinctValues('assignees'))
                        ->multiple()
                        ->native(false),
                    // The full recognised catalogue, not just what's already in use — only the
                    // FILTERS are limited to values actually present in the table.
                    Select::make('labels')
                        ->label(__('two-way-ticket::two-way-ticket.field.labels'))
                        ->options(fn(): array => Ticket::labelOptions())
                        ->multiple()
                        ->native(false),
                    Select::make('projects')
                        ->label(__('two-way-ticket::two-way-ticket.field.projects'))
                        ->options(fn(): array => Ticket::distinctValues('projects'))
                        ->multiple()
                        ->native(false),
                    Select::make('milestone')
                        ->label(__('two-way-ticket::two-way-ticket.field.milestone'))
                        ->options(fn(): array => Ticket::distinctValues('milestone'))
                        ->native(false),
                    // Pre-filled with the running build, because that is what it is nine times out
                    // of ten, but editable and clearable: an admin filing a request that arrived
                    // by phone or e-mail has either no version or someone else's (Oli, 2026-07-27).
                    // Left empty it stays empty — the model only fills in what was never set.
                    TextInput::make('app_version')
                        ->label(__('two-way-ticket::two-way-ticket.field.app_version'))
                        ->default(fn(): string => Ticket::reportingAppVersion())
                        ->maxLength(255),
                ]),
            ]);
    }
}
