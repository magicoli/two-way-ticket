<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Magicoli\TwoWayTicket\Enums\TicketStatus;
use Magicoli\TwoWayTicket\Filament\Resources\Tickets\Schemas\ReportIssueForm;
use Magicoli\TwoWayTicket\Models\Ticket;
use Livewire\Attributes\Locked;

/**
 * A plain, tenant-agnostic page — deliberately NOT the TicketResource's own CreateRecord page
 * (which only exists on whichever panel {@see \Magicoli\TwoWayTicket\TicketsPlugin} is attached
 * to, typically 'admin' only). Creates directly against the Ticket model, with no Resource/tenant
 * machinery involved at all — see {@see \Magicoli\TwoWayTicket\ReportIssuePlugin} for why that
 * separation matters. Uses its OWN minimal form ({@see ReportIssueForm}), not TicketForm — a
 * reporter doesn't choose status/labels/assignees/milestone/projects, those are triage fields.
 */
class ReportIssue extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Captured once at mount() from the `from` query parameter — the render hook button
     * (see ReportIssuePlugin::renderReportButton()) links here with the CURRENT page's own URL
     * attached, since this page's own URL would otherwise always just say ".../report-issue".
     */
    #[Locked]
    public ?string $reportedFromUrl = null;

    public function mount(): void
    {
        $from = request()->string('from')->toString();
        $this->reportedFromUrl = $from !== '' ? $from : null;

        // Pre-filled as an ordinary editable field rather than captured silently: the reporter
        // can correct it, or clear it when the report isn't about this page at all.
        $this->form->fill(['page_url' => $this->reportedFromUrl]);
    }

    public function form(Schema $schema): Schema
    {
        return ReportIssueForm::configure($schema);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function getTitle(): string
    {
        return __('two-way-ticket::two-way-ticket.report_issue.title');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('submit')
                ->footer([
                    Actions::make([
                        Action::make('submit')->label(__('two-way-ticket::two-way-ticket.report_issue.submit'))->submit('submit'),
                    ]),
                ]),
        ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // page_url comes from the form, so a cleared field really means "not about a specific
        // page" instead of being silently overwritten with the referring URL.
        $pageUrl = filled($data['page_url'] ?? null) ? $data['page_url'] : null;
        $appVersion = Ticket::reportingAppVersion();
        // Steps are a reporting convenience, not a stored field: they're folded into the
        // description here, once, and it's an ordinary field from then on.
        $steps = $data['steps'] ?? [];
        unset($data['steps']);

        Ticket::create([
            ...$data,
            'description' => Ticket::composeDescription($data['description'] ?? null, $steps, $pageUrl, $appVersion),
            'status' => TicketStatus::Open,
            'app_version' => $appVersion,
            'page_url' => $pageUrl,
            'user_id' => Filament::auth()->id(),
        ]);

        Notification::make()->success()->title(__('two-way-ticket::two-way-ticket.report_issue.submitted'))->send();

        // Back to wherever the reporter was when they hit the button. url()->previous() can't do
        // that here: by the time this runs, the "previous" page IS the report form, so it just
        // bounced back onto itself. The `from` captured at mount() is the real origin.
        $this->redirect($this->reportedFromUrl ?? Filament::getUrl());
    }
}
