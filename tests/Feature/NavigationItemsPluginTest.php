<?php

declare(strict_types=1);

use Filament\Navigation\NavigationItem;
use Filament\View\PanelsRenderHook;
use Magicoli\TwoWayTicket\NavigationItemsPlugin;

it('keys getId() by the render hook, so two instances at different hooks never collide', function (): void {
    // Filament stores plugins by getId() — $panel->plugins[$plugin->getId()] = $plugin (see
    // Panel::plugin()). A host attaching this plugin twice on one panel (its own shortcuts
    // alongside a footer, say) needs both to survive registration, not have the second overwrite
    // the first.
    $shortcuts = NavigationItemsPlugin::make();
    $footer = NavigationItemsPlugin::make()->renderHook(PanelsRenderHook::FOOTER);

    expect($shortcuts->getId())->not->toBe($footer->getId());
});

it('defaults to USER_MENU_BEFORE and renders through its own view', function (): void {
    $plugin = NavigationItemsPlugin::make();

    expect($plugin->getId())->toBe('navigation-items-plugin:'.PanelsRenderHook::USER_MENU_BEFORE);
});

it('lets a host override the view, for a rendering NavigationItemsPlugin::VIEW does not cover', function (): void {
    $rendered = view('two-way-ticket::navigation-items', [
        'items' => [NavigationItem::make()->label('Docs')->url('https://example.test')],
    ])->render();

    expect($rendered)
        ->toContain('Docs')
        ->toContain('https://example.test');
});

it('filters items by isVisible() before boot() ever hands them to the view', function (): void {
    $plugin = NavigationItemsPlugin::make()->items([
        NavigationItem::make()->label('Shown')->url('https://example.test/shown'),
        NavigationItem::make()->label('Hidden')->url('https://example.test/hidden')->visible(false),
    ]);

    $visible = (new ReflectionMethod($plugin, 'visibleItems'))->invoke($plugin);

    expect($visible)->toHaveCount(1);
    expect($visible[0]->getLabel())->toBe('Shown');
});
