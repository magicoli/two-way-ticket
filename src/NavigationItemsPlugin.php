<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\View\View;

/**
 * A list of NavigationItem — the same arguments $panel->navigationItems() takes — rendered at a
 * render hook instead of inside the panel's own navigation, so it can reach every page of every
 * panel rather than just one panel's own destinations.
 *
 * {@see ReportIssuePlugin} is this package's own user: its "Report an issue" entry used to be a
 * bespoke `<x-filament::button>`, visually out of place next to real navigation. It now renders
 * through this same class's view (see VIEW) instead — a single item, not a whole second Plugin
 * registration on the panel (see ReportIssuePlugin::renderReportButton()).
 *
 * Deliberately not called "secondary navigation": Filament already documents that term for
 * navigation *inside* a page, which this is not.
 *
 * VIEW renders through Filament's own <x-filament-panels::sidebar.item> or ::topbar.item> —
 * whichever the current UserMenuPosition matches, the same components the main nav uses — rather
 * than bespoke markup: that is what gets these items sidebar-collapse tooltips, active-state
 * highlighting and the exact visual language of the host panel's own navigation for free, with
 * no CSS of this package's own to keep in sync with a host's theme. A host wanting a completely
 * different rendering (a footer credit line, say — plain text/icon/link, not part of any panel's
 * collapsible navigation chrome) can supply its own via view().
 *
 * No URL-required filtering, unlike NavigationManager's own pipeline for a panel's own
 * navigationItems() (`filled($item->getChildItems()) || filled($item->getUrl())`, see
 * FilamentManager::getNavigation()): that rule exists so the panel's own clickable navigation
 * never links nowhere — it is not a property every NavigationItem must have, and a host may
 * legitimately want a label-only entry through a custom view().
 */
class NavigationItemsPlugin implements Plugin
{
    /** The view VIEW renders by default — sidebar/topbar item chrome, nothing else. */
    public const VIEW = 'two-way-ticket::navigation-items';

    /** @var array<int, NavigationItem> */
    protected array $items = [];

    protected string $renderHookName = PanelsRenderHook::USER_MENU_BEFORE;

    protected string $view = self::VIEW;

    public static function make(): static
    {
        return app(static::class);
    }

    /** @param  array<int, NavigationItem>  $items */
    public function items(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /** Override the default placement. */
    public function renderHook(string $hookName): static
    {
        $this->renderHookName = $hookName;

        return $this;
    }

    /** Override the default sidebar/topbar-item rendering with a host's own view. */
    public function view(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Keyed by the hook it targets, not a fixed string: a host may attach more than one instance
     * of this plugin to the same panel (its own cross-panel shortcuts alongside a footer, say),
     * and Filament stores plugins by getId() — see Panel::plugin(), `$this->plugins[$plugin->
     * getId()] = $plugin`. A fixed id would let the second registration silently replace the
     * first in that array, and only the survivor's boot() — hence its render hook — would ever
     * run.
     */
    public function getId(): string
    {
        return 'navigation-items-plugin:'.$this->renderHookName;
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            $this->renderHookName,
            fn (): ?View => ($items = $this->visibleItems())
                ? view($this->view, ['items' => $items])
                : null,
        );
    }

    /**
     * Resolved per render, not once at registration: every item's label, URL and visibility is a
     * closure reading the current user and tenant, and this plugin is configured once for a
     * panel that then serves every request. Filtering by isVisible() mirrors what
     * NavigationManager itself does before a NavigationGroup/item component ever sees an item —
     * see FilamentManager::getNavigation().
     *
     * @return array<int, NavigationItem>
     */
    protected function visibleItems(): array
    {
        return collect($this->items)
            ->filter(fn (NavigationItem $item): bool => $item->isVisible())
            ->values()
            ->all();
    }
}
