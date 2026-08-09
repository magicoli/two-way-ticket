<?php

declare(strict_types=1);

use Magicoli\TwoWayTicket\NavigationItemsPlugin;
use Magicoli\TwoWayTicket\Tests\Fixtures\User;

/**
 * The report button used to be a bespoke `<x-filament::button>` (`Blade::render('<x-filament::
 * button tag="a" ...')`) — visually out of place next to real navigation entries. It now renders
 * through NavigationItemsPlugin::VIEW instead, the same one a host's own cross-panel shortcuts
 * would use — see ReportIssuePlugin::renderReportButton().
 */
it('renders the report entry through NavigationItemsPlugin, not a bespoke button', function (): void {
    $user = User::create(['name' => 'Reporter', 'email' => 'reporter@example.test']);

    $html = $this->actingAs($user)->get('/admin/tickets')->getContent();

    // Not a page-wide assertDontSee('fi-btn') — the tickets table has real buttons of its own
    // (Create, filters...) with nothing to do with this. What matters is the specific markup
    // around the report-issue link itself: a real topbar item, not a bare button. Topbar, not
    // sidebar: neither test panel calls ->topbar(false), and hasTopbar() defaults to true —
    // confirmed by dumping the actual response rather than assuming.
    $hrefPos = strpos($html, '/admin/report-issue');
    expect($hrefPos)->not->toBeFalse();

    $window = substr($html, max(0, $hrefPos - 400), 600);
    expect($window)
        ->toContain('fi-topbar-item')
        ->not->toContain('fi-btn');
});

it('drops the report entry entirely once canReport() says no', function (): void {
    $user = User::create(['name' => 'Blocked', 'email' => 'blocked@example.test']);

    \Magicoli\TwoWayTicket\ReportIssuePlugin::make()->visible(false);

    $html = $this->actingAs($user)->get('/admin/tickets')->getContent();

    expect($html)->not->toContain('/admin/report-issue');

    \Magicoli\TwoWayTicket\ReportIssuePlugin::make()->visible(null);
});

it('exposes the view constant ReportIssuePlugin depends on', function (): void {
    // A regression here means ReportIssuePlugin's own view(NavigationItemsPlugin::VIEW, ...)
    // call silently starts rendering a view that no longer exists — render(), not just view(),
    // since resolving a missing view only throws at render time, not at view() call time.
    expect(NavigationItemsPlugin::VIEW)->toBe('two-way-ticket::navigation-items');
    expect(view(NavigationItemsPlugin::VIEW, ['items' => []])->render())->toBeString();
});
