{{--
    No styling of our own: renders each item through Filament's own navigation item component,
    the same one the host panel's own main nav uses — sidebar where the user menu itself lives in
    the sidebar, topbar otherwise. See NavigationItemsPlugin.
--}}
@php
    $inSidebar = filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Sidebar;
@endphp

@if ($inSidebar)
    {{-- fi-sidebar-group-items (gap-y-1), not fi-sidebar-nav-groups: that one carries gap-y-7,
    meant to separate whole groups in the main scrollable nav, and produces a visibly oversized
    gap in a compact strip like this one. This is the class real ungrouped items sit in already —
    see Filament's own sidebar/group.blade.php, its inner <ul>. --}}
    <ul class="fi-sidebar-group-items">
        @foreach ($items as $item)
            <x-filament-panels::sidebar.item
                :active="$item->isActive()"
                :icon="$item->getIcon()"
                :should-open-url-in-new-tab="$item->shouldOpenUrlInNewTab()"
                :url="$item->getUrl()"
            >
                {{ $item->getLabel() }}
            </x-filament-panels::sidebar.item>
        @endforeach
    </ul>
@else
    <ul class="fi-topbar-nav-groups">
        @foreach ($items as $item)
            <x-filament-panels::topbar.item
                :active="$item->isActive()"
                :icon="$item->getIcon()"
                :should-open-url-in-new-tab="$item->shouldOpenUrlInNewTab()"
                :url="$item->getUrl()"
            >
                {{ $item->getLabel() }}
            </x-filament-panels::topbar.item>
        @endforeach
    </ul>
@endif
