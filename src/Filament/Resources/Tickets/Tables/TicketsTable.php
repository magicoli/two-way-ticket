<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Magicoli\TwoWayTicket\Actions\CloseTicket;
use Magicoli\TwoWayTicket\Actions\CreateGithubIssue;
use Magicoli\TwoWayTicket\Enums\TicketStateReason;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Models\Ticket;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('two-way-ticket::two-way-ticket.table.empty'))
            ->columns([
                // One column, still TWO badges: status (the important one, coloured) and its
                // reason (secondary, gray), wrapping so the reason drops underneath when the
                // column is tight rather than widening it. Width is the scarce resource here.
                //
                // An array state is what makes Filament render one badge per value; the raw
                // enum values double as the discriminator, since no status value can collide
                // with a reason one.
                TextColumn::make('status')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.status'))
                    ->badge()
                    ->sortable()
                    ->wrap()
                    ->state(fn (Ticket $record): array => array_values(array_filter([
                        $record->status->value,
                        $record->state_reason,
                    ])))
                    ->formatStateUsing(fn (string $state): ?string => TicketStatus::tryFrom($state)?->getLabel()
                        ?? TicketStateReason::labelFor($state))
                    ->color(fn (string $state): string => TicketStatus::tryFrom($state)?->getColor() ?? 'gray'),
                // Gray on purpose: a coloured badge here would highlight labels without actually
                // distinguishing them. Colouring them properly means mirroring each label's own
                // GitHub colour — a lot of work for little gain, so: neutral.
                TextColumn::make('labels')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.labels'))
                    ->badge()
                    ->color('gray')
                    ->separator(',')
                    ->sortable()
                    ->wrap()
                    ->toggleable(),
                // Title, wrapped rather than truncated (SPEC.md §4 wanted "plus de place pour le
                // sujet"), with the page path UNDER it — as its own column the URL ate width for
                // what is usually a secondary detail.
                //
                // Same trick as status/reason above: an array state renders one line per value
                // while the column keeps its own sortable header. A top-level Stack would lay it
                // out too, but it switches the whole table into Filament's header-less card mode,
                // costing EVERY column its header — and with it, sorting.
                TextColumn::make('title')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.title'))
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->grow()
                    ->state(fn (Ticket $record): array => array_values(array_filter([
                        $record->title,
                        filled($record->page_url)
                            ? (parse_url($record->page_url, PHP_URL_PATH) ?: $record->page_url)
                            : null,
                    ])))
                    // One per line — the default joins array values with ", ", which read as a
                    // stray comma between the title and the path.
                    ->listWithLineBreaks()
                    // Deliberately NOT a link: giving the path its own URL turns the cell into a
                    // link and kills the row click, and being able to click anywhere on the row
                    // to open the ticket matters more than reaching the page from here.
                    ->color(fn (string $state, Ticket $record): string => $state === $record->title
                        ? ''
                        : 'gray')
                    ->weight(fn (string $state, Ticket $record): ?string => $state === $record->title
                        ? 'medium'
                        : null),
                TextColumn::make('assignees')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.assignees'))
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('milestone')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.milestone'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('projects')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.projects'))
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('github_issue_number')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.github'))
                    ->formatStateUsing(fn(?int $state): ?string => $state === null ? null : '#' . $state)
                    ->url(fn(Ticket $record): ?string => $record->github_issue_url)
                    ->openUrlInNewTab()
                    ->badge()
                    ->sortable()
                    ->color('success'),
                TextColumn::make('app_version')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.app_version'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.reported_by'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label(__('two-way-ticket::two-way-ticket.field.reported_at'))
                    ->isoDateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters(
                [
                    // Open/Closed/All lives in the page's tabs (see ListTickets::getTabs()) as a
                    // general condition ANDed with these filters, not a replacement for this one
                    // — still useful on its own since GitHub's state has exactly these two values.
                    // A ticket can only have ONE status, so whereIn (SelectFilter's default query)
                    // would be enough — but SelectFilter can't do wrapOptionLabels(), which the
                    // other multi-value filters below need, so this stays a raw Filter+Select too
                    // for consistency between all the multi-value ones.
                    // Filter::make('status')
                    //     ->label(__('two-way-ticket::two-way-ticket.filter.status'))
                    //     ->schema([
                    //         Select::make('values')
                    //             ->label(__('two-way-ticket::two-way-ticket.filter.status'))
                    //             ->placeholder(__('two-way-ticket::two-way-ticket.filter.status'))
                    //             ->native(false)
                    //             ->options(TicketStatus::class)
                    //             ->multiple()
                    //             ->wrapOptionLabels(false),
                    //     ])
                    //     ->query(fn(Builder $query, array $data): Builder => $query->when(
                    //         filled($data['values'] ?? null),
                    //         fn(Builder $query): Builder => $query->whereIn('status', $data['values']),
                    //     )),
                    // labels/assignees/projects: a ticket can carry SEVERAL values at once, stored
                    // as a JSON array — SelectFilter's own whereIn() can't express "this array
                    // contains any of the selected values", so each needs its own whereJsonContains
                    // query, same shape as `status` above.
                    Filter::make('labels')
                        ->label(__('two-way-ticket::two-way-ticket.filter.labels'))
                        ->schema([
                            Select::make('values')
                                ->label(__('two-way-ticket::two-way-ticket.filter.labels'))
                                ->placeholder(__('two-way-ticket::two-way-ticket.filter.labels'))
                                ->native(false)
                                ->options(fn(): array => Ticket::distinctValues('labels'))
                                ->multiple()
                                ->wrapOptionLabels(false),
                        ])
                        ->query(fn(Builder $query, array $data): Builder => $query->when(
                            filled($data['values'] ?? null),
                            fn(Builder $query): Builder => $query->where(function (Builder $query) use ($data): void {
                                foreach ($data['values'] as $label) {
                                    $query->orWhereJsonContains('labels', $label);
                                }
                            }),
                        )),
                    SelectFilter::make('milestone')
                        ->label(__('two-way-ticket::two-way-ticket.field.milestone'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.milestone'))
                        ->native(false)
                        ->options(fn(): array => Ticket::distinctValues('milestone')),
                    Filter::make('projects')
                        ->label(__('two-way-ticket::two-way-ticket.filter.projects'))
                        ->schema([
                            Select::make('values')
                                ->label(__('two-way-ticket::two-way-ticket.filter.projects'))
                                ->placeholder(__('two-way-ticket::two-way-ticket.filter.projects'))
                                ->native(false)
                                ->options(fn(): array => Ticket::distinctValues('projects'))
                                ->multiple(),
                        ])
                        ->query(fn(Builder $query, array $data): Builder => $query->when(
                            filled($data['values'] ?? null),
                            fn(Builder $query): Builder => $query->where(function (Builder $query) use ($data): void {
                                foreach ($data['values'] as $project) {
                                    $query->orWhereJsonContains('projects', $project);
                                }
                            }),
                        )),
                    SelectFilter::make('app_version')
                        ->label(__('two-way-ticket::two-way-ticket.field.app_version'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.app_version'))
                        ->native(false)
                        ->options(
                            fn(): array => Ticket::query()
                                ->whereNotNull('app_version')
                                ->where('app_version', '!=', '')
                                ->distinct()
                                ->orderBy('app_version')
                                ->pluck('app_version', 'app_version')
                                ->all(),
                        ),
                    SelectFilter::make('user')
                        ->label(__('two-way-ticket::two-way-ticket.field.reported_by'))
                        ->placeholder(__('two-way-ticket::two-way-ticket.filter.user'))
                        ->native(false)
                        ->relationship('user', 'name'),
                    Filter::make('assignees')
                        ->label(__('two-way-ticket::two-way-ticket.filter.assignees'))
                        ->schema([
                            Select::make('values')
                                ->label(__('two-way-ticket::two-way-ticket.filter.assignees'))
                                ->placeholder(__('two-way-ticket::two-way-ticket.filter.assignees'))
                                ->native(false)
                                ->options(fn(): array => Ticket::distinctValues('assignees'))
                                ->multiple(),
                        ])
                        ->query(fn(Builder $query, array $data): Builder => $query->when(
                            filled($data['values'] ?? null),
                            fn(Builder $query): Builder => $query->where(function (Builder $query) use ($data): void {
                                foreach ($data['values'] as $assignee) {
                                    $query->orWhereJsonContains('assignees', $assignee);
                                }
                            }),
                        )),
                ],
                layout: FiltersLayout::AboveContent,
            )
            ->filtersFormColumns([
                'xs' => 2,
                'sm' => 4,
                'xl' => 6,
            ])
            ->recordActions([
                // Icon-only row actions, always — labels here are the quintessence of wasted space.
                self::pushToGithubAction()->iconButton(),
                self::closeAction()->iconButton(),
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::pushToGithubAction(bulk: true),
                    self::closeAction(bulk: true),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Same action, offered per row and in bulk — built once so the two can't drift apart.
     * Confirmation required either way: pushing creates a real, public issue.
     */
    private static function pushToGithubAction(bool $bulk = false): Action|BulkAction
    {
        $push = function (Ticket $record): bool {
            if ($record->isLinked()) {
                return false;
            }

            resolve(CreateGithubIssue::class)->handle($record);

            return true;
        };

        if ($bulk) {
            return BulkAction::make('pushToGithub')
                ->label(__('two-way-ticket::two-way-ticket.actions.push_to_github'))
                ->icon(self::githubIcon())
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn (Collection $records) => self::runOverRecords($records, $push))
                ->deselectRecordsAfterCompletion();
        }

        return Action::make('pushToGithub')
            ->label(__('two-way-ticket::two-way-ticket.actions.push_to_github'))
            ->icon(self::githubIcon())
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (Ticket $record): bool => ! $record->isLinked())
            ->action(fn (Ticket $record) => self::runOverRecords(collect([$record]), $push));
    }

    /**
     * Closing asks for a reason the way GitHub does, and lets the labels be adjusted in the same
     * breath — closing as "duplicate" usually means labelling it as such too.
     */
    private static function closeAction(bool $bulk = false): Action|BulkAction
    {
        $schema = [
            Select::make('state_reason')
                ->label(__('two-way-ticket::two-way-ticket.field.state_reason'))
                ->options(TicketStateReason::closingOptions())
                ->default(TicketStateReason::Completed->value)
                ->native(false)
                ->required(),
            Select::make('labels')
                ->label(__('two-way-ticket::two-way-ticket.field.labels'))
                ->options(fn (): array => Ticket::labelOptions())
                ->multiple()
                ->native(false),
        ];

        $close = fn (array $data): callable => function (Ticket $record) use ($data): bool {
            if ($record->status === TicketStatus::Closed) {
                return false;
            }

            resolve(CloseTicket::class)->handle(
                $record,
                $data['state_reason'],
                // Untouched when left empty: an empty picker means "don't bother", not "strip
                // every label this ticket has".
                filled($data['labels'] ?? null) ? $data['labels'] : null,
            );

            return true;
        };

        if ($bulk) {
            return BulkAction::make('close')
                ->label(__('two-way-ticket::two-way-ticket.actions.close'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('gray')
                ->schema($schema)
                ->action(fn (Collection $records, array $data) => self::runOverRecords($records, $close($data)))
                ->deselectRecordsAfterCompletion();
        }

        return Action::make('close')
            ->label(__('two-way-ticket::two-way-ticket.actions.close'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('gray')
            ->schema($schema)
            ->visible(fn (Ticket $record): bool => $record->status !== TicketStatus::Closed)
            ->action(fn (Ticket $record, array $data) => self::runOverRecords(collect([$record]), $close($data)));
    }

    /**
     * Applies an operation to every record, reporting once at the end. One failure doesn't abort
     * the rest — with a bulk selection, stopping halfway would leave the user guessing which
     * records went through.
     *
     * @param  Collection<int, Ticket>  $records
     * @param  callable(Ticket): bool  $operation  Returns false when the record needed nothing.
     */
    private static function runOverRecords(Collection $records, callable $operation): void
    {
        $done = 0;
        $failures = [];

        foreach ($records as $record) {
            try {
                if ($operation($record)) {
                    $done++;
                }
            } catch (\Throwable $throwable) {
                $failures[] = '#'.$record->getKey().': '.$throwable->getMessage();
            }
        }

        if ($failures !== []) {
            Notification::make()
                ->danger()
                ->persistent()
                ->title(__('two-way-ticket::two-way-ticket.actions.could_not_push'))
                ->body(implode("\n", $failures))
                ->send();
        }

        if ($done > 0) {
            Notification::make()
                ->success()
                ->title(__('two-way-ticket::two-way-ticket.actions.done', ['count' => $done]))
                ->send();
        }
    }

    /**
     * The GitHub mark, when the host app ships an icon set that has one — it's not in Heroicons.
     * Configurable rather than hardcoded so the package never references an icon set it doesn't
     * depend on (Blade Icons throws outright on an unknown set).
     */
    private static function githubIcon(): string
    {
        return config()->string('two-way-ticket.github.icon', 'heroicon-o-arrow-up-tray');
    }
}
