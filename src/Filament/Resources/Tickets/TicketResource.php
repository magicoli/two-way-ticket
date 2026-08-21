<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Resources\Tickets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\CreateTicket;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\EditTicket;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ListTickets;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Pages\ViewTicket;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas\TicketForm;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas\TicketInfolist;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Tables\TicketsTable;
use Magicoli\TwoWayTicket\Models\Ticket;
use Magicoli\TwoWayTicket\Policies\TicketPolicy;
use Magicoli\TwoWayTicket\TicketsPlugin;
use UnitEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $recordTitleAttribute = 'title';

    // Tickets track the app itself, not any tenant's data — Ticket has no relationship to a host
    // app's tenant model and never should. Without this, Filament tries to auto-scope the
    // resource by tenant on any panel that has tenancy enabled and errors looking for a
    // relationship that doesn't exist (and conceptually shouldn't).
    protected static bool $isScopedToTenant = false;

    /**
     * A condition set with `TicketsPlugin::make()->visible(...)` wins; without one, the policy
     * decides ({@see TicketPolicy}).
     */
    public static function canAccess(): bool
    {
        return TicketsPlugin::isVisible() ?? parent::canAccess();
    }

    public static function canViewAny(): bool
    {
        return TicketsPlugin::isVisible() ?? parent::canViewAny();
    }

    /**
     * The navigation group is the consuming panel's choice, set with
     * `TicketsPlugin::make()->group(...)`; without one, the resource stays ungrouped.
     */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return TicketsPlugin::navigationGroup() ?? parent::getNavigationGroup();
    }

    /**
     * Likewise for the ordering within that group, set with `TicketsPlugin::make()->sort(...)`.
     */
    public static function getNavigationSort(): ?int
    {
        return TicketsPlugin::navigationSort() ?? parent::getNavigationSort();
    }

    public static function getModelLabel(): string
    {
        return __('two-way-ticket::two-way-ticket.ticket.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('two-way-ticket::two-way-ticket.ticket.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
