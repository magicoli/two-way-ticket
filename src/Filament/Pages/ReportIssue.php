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

        $this->form->fill();
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

        // Steps are a reporting convenience only, not a stored field — formatted into the
        // description right here so it round-trips with GitHub with no special-casing at all.
        $steps = collect($data['steps'] ?? [])
            ->filter(fn (string $step): bool => $step !== '')
            ->values();
        unset($data['steps']);

        if ($steps->isNotEmpty()) {
            $data['description'] = trim(
                ($data['description'] ?? '')
                ."\n\n## ".__('two-way-ticket::two-way-ticket.issue.steps', [], 'en')."\n"
                .$steps->map(fn (string $step, int $index): string => ($index + 1).'. '.$step)->implode("\n"),
            );
        }

        Ticket::create([
            ...$data,
            'status' => TicketStatus::Open,
            'app_version' => Ticket::reportingAppVersion(),
            'page_url' => $this->reportedFromUrl,
            'user_id' => Filament::auth()->id(),
        ]);

        Notification::make()->success()->title(__('two-way-ticket::two-way-ticket.report_issue.submitted'))->send();

        $this->redirect(url()->previous(Filament::getUrl()));
    }
}
