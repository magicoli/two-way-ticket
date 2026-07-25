<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Blade;
use Magicoli\TwoWayTicket\Filament\Pages\ReportIssue;

/**
 * Adds the "Report an issue" entry point — attach this to EVERY panel that should let its users
 * report something (typically all of them), regardless of whether {@see TicketsPlugin} (the
 * browsable list) is also attached there. Deliberately separate plugins: see TicketsPlugin's own
 * docblock for why.
 *
 * Default placement is USER_MENU_BEFORE — inside the user-menu dropdown, before the profile
 * header. A host still needs its OWN handling for anonymous/guest pages (there is no user menu to
 * render before) — same edge case cerealkiller97/filament-bug-reports already left to the host via
 * GLOBAL_SEARCH_AFTER; not something this plugin can solve generically.
 */
class ReportIssuePlugin implements Plugin
{
    /** @var (Closure(Authenticatable): bool)|null */
    protected ?Closure $authorizeReportingUsing = null;

    protected string $renderHookName = PanelsRenderHook::USER_MENU_BEFORE;

    public function getId(): string
    {
        return 'two-way-ticket-report';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            ReportIssue::class,
        ]);

        $panel->renderHook(
            $this->renderHookName,
            fn (): string => $this->renderReportButton(),
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /** Override the default USER_MENU_BEFORE placement (e.g. GLOBAL_SEARCH_AFTER, matching cerealkiller's own spot). */
    public function renderHook(string $hookName): static
    {
        $this->renderHookName = $hookName;

        return $this;
    }

    /**
     * Who may report an issue. Default: any authenticated user.
     *
     * @param  Closure(Authenticatable): bool  $callback
     */
    public function authorizeReportingUsing(Closure $callback): static
    {
        $this->authorizeReportingUsing = $callback;

        return $this;
    }

    public function canReport(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->authorizeReportingUsing !== null
            ? (bool) ($this->authorizeReportingUsing)($user)
            : true;
    }

    protected function renderReportButton(): string
    {
        if (! $this->canReport(auth()->user())) {
            return '';
        }

        return Blade::render(
            '<x-filament::button tag="a" :href="$href" icon="heroicon-o-bug-ant" color="gray" size="sm">{{ $label }}</x-filament::button>',
            [
                // The CURRENT page's own URL, so ReportIssue::mount() can record where the report
                // actually came from — its own URL would otherwise always just say
                // ".../report-issue".
                'href' => ReportIssue::getUrl(['from' => url()->current()]),
                'label' => __('two-way-ticket::two-way-ticket.report_issue.report_button'),
            ],
        );
    }
}
