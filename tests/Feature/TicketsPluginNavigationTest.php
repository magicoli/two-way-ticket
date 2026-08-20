<?php

declare(strict_types=1);

use Magicoli\TwoWayTicket\Filament\Resources\Tickets\TicketResource;
use Magicoli\TwoWayTicket\TicketsPlugin;

/**
 * Which navigation group the ticket resource sits in — and its order within that group — is the
 * consuming panel's decision, not the package's, so it is set on the plugin and read back by the
 * resource (the same class-static handshake visible() already uses). Without this, a host could
 * not fold the backlog into its own "Admin" group.
 */
it('leaves the resource ungrouped and unsorted when the panel sets nothing', function (): void {
    expect(TicketResource::getNavigationGroup())->toBeNull()->and(TicketResource::getNavigationSort())->toBeNull();
});

it('takes the group and sort the consuming panel configures on the plugin', function (): void {
    TicketsPlugin::make()->group('Admin')->sort(90);

    expect(TicketResource::getNavigationGroup())->toBe('Admin')->and(TicketResource::getNavigationSort())->toBe(90);
});

it('resolves a closure group per request, so it can be translated', function (): void {
    TicketsPlugin::make()->group(fn(): string => __('Administration'));

    expect(TicketResource::getNavigationGroup())->toBe('Administration');
});
