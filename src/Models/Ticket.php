<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Magicoli\TwoWayTicket\Database\Factories\TicketFactory;
use Magicoli\TwoWayTicket\Enums\TicketStatus;

/**
 * @property-read int $id
 * @property string $title
 * @property string|null $description
 * @property TicketStatus $status
 * @property array<int, string>|null $labels
 * @property string|null $milestone
 * @property array<int, string>|null $assignees
 * @property array<int, string>|null $projects
 * @property array<int, string>|null $screenshot_paths
 * @property string|null $page_url
 * @property string $app_version
 * @property string $role
 * @property string|null $github_issue_url
 * @property int|null $github_issue_number
 * @property string|null $state_reason
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property int|null $user_id
 */
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $table = 'tickets';

    /** @var list<string> */
    protected $fillable = [
        'title',
        'description',
        'status',
        'labels',
        'milestone',
        'assignees',
        'projects',
        'screenshot_paths',
        'page_url',
        'app_version',
        'role',
        'github_issue_url',
        'github_issue_number',
        'state_reason',
        'closed_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'assignees' => 'array',
            'projects' => 'array',
            'screenshot_paths' => 'array',
            'status' => TicketStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }

    /**
     * Stamps the build a ticket was reported on, wherever it is created from.
     *
     * Left to each call site, this got forgotten: the API set it and the GitHub import set it, but
     * the Filament create page did not, and creating a ticket from the UI died on a NOT NULL
     * violation. Filling it here means no future call site has to remember either.
     *
     * The test is whether the attribute was SET, not whether it is empty: the import deliberately
     * stores an empty string, because an issue opened on GitHub came from no build of ours and
     * must not be labelled with one.
     */
    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            if (!array_key_exists('app_version', $ticket->getAttributes())) {
                $ticket->app_version = static::reportingAppVersion();
            }
        });
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = config()->string('two-way-ticket.user_model', User::class);

        return $this->belongsTo($model, 'user_id');
    }

    /** Whether this ticket has a real GitHub issue behind it. */
    public function isLinked(): bool
    {
        return $this->github_issue_url !== null;
    }

    /**
     * Builds the description ONCE, at creation, out of the structured fields the reporting form
     * collects. From then on the description is an ordinary field: edited by hand, synced both
     * ways with GitHub, and never regenerated — which is what lets it round-trip verbatim.
     *
     * Written in English because it ends up on GitHub, and a section whose field is empty gets
     * no heading at all. The page is reduced to its PATH: the host may be a private or local
     * install, so a full URL tells a GitHub reader nothing and leaks an internal address.
     *
     * @param  list<string>  $steps
     */
    public static function composeDescription(
        ?string $description,
        array $steps = [],
        ?string $pageUrl = null,
        ?string $appVersion = null,
    ): ?string {
        $steps = collect($steps)->filter(fn(string $step): bool => trim($step) !== '')->values();
        $path = filled($pageUrl) ? parse_url($pageUrl, PHP_URL_PATH) : null;

        // A markdown LIST, not consecutive lines: markdown collapses single newlines, so plain
        // lines would render as one run-on paragraph wherever the description is displayed.
        $details = collect([
            filled($appVersion)
                ? '- **' . __('two-way-ticket::two-way-ticket.issue.app_version', [], 'en') . ':** ' . $appVersion
                : null,
            filled($path)
                ? '- **' . __('two-way-ticket::two-way-ticket.issue.page_url', [], 'en') . ':** `' . $path . '`'
                : null,
        ])->filter();

        // Only the description itself goes without a heading — it IS the body. Every other
        // section that has content announces itself.
        $composed = collect([
            filled($description) ? trim($description) : null,
            $steps->isNotEmpty()
                ? '## '
                . __('two-way-ticket::two-way-ticket.issue.steps', [], 'en')
                . "\n"
                . $steps->map(fn(string $step, int $index): string => ($index + 1) . '. ' . $step)->implode("\n")
                : null,
            $details->isNotEmpty()
                ? '## ' . __('two-way-ticket::two-way-ticket.issue.details', [], 'en') . "\n" . $details->implode("\n")
                : null,
        ])
            ->filter()
            ->implode("\n\n");

        return $composed !== '' ? $composed : null;
    }

    /**
     * The label catalogue offered when filing: GitHub's standard set (config `default_labels`)
     * merged with whatever is already in use here, so a brand-new install still offers real
     * choices and a label added later never disappears from the list.
     *
     * @return array<string, string>
     */
    public static function labelOptions(): array
    {
        /** @var list<string> $defaults */
        $defaults = config()->array('two-way-ticket.github.default_labels', []);

        return collect($defaults)
            ->merge(array_keys(static::distinctValues('labels')))
            ->unique()
            ->sort()
            ->mapWithKeys(fn(string $label): array => [$label => $label])
            ->all();
    }

    /**
     * The build to stamp on a ticket reported FROM this app. Falls back to the host app's own
     * `app.version` when the package isn't configured with one — the behaviour config/two-way-ticket.php
     * always documented but never actually implemented, which is why the field came out empty.
     *
     * Deliberately not applied to imported GitHub issues: those didn't come from any build here,
     * so their empty app_version is correct.
     */
    public static function reportingAppVersion(): string
    {
        $configured = config()->string('two-way-ticket.app_version', '');

        return $configured !== '' ? $configured : (string) config('app.version', '');
    }

    /**
     * Every distinct value present across all tickets for a column, whether it holds one value
     * (milestone) or several (labels/assignees/projects). Feeds both the table filters and the
     * edit form's select lists — until those become properly managed catalogues (a label has to
     * be created through its own controlled procedure, an assignee has to be a real local user
     * with a linked GitHub account), the values already in use ARE the option list.
     *
     * @return array<string, string>
     */
    public static function distinctValues(string $column): array
    {
        $values = static::query()->whereNotNull($column)->pluck($column);

        // Instantiated on its own line rather than chained off `new`: that chaining is 8.4-only
        // syntax, and it was one of two lines quietly holding the whole package at 8.4. Written
        // this way it cannot be undone by a formatter that thinks the parentheses are redundant.
        $model = new static();

        if ($model->hasCast($column, 'array')) {
            $values = $values->flatMap(fn(array $columnValues): array => $columnValues);
        }

        return $values
            ->filter(fn(?string $value): bool => filled($value))
            ->unique()
            ->sort()
            ->mapWithKeys(fn(string $value): array => [$value => $value])
            ->all();
    }
}
