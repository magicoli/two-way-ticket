<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Magicoli\TwoWayTicket\Database\Factories\TicketFactory;
use Magicoli\TwoWayTicket\Enums\TicketPriority;
use Magicoli\TwoWayTicket\Enums\TicketStatus;

/**
 * @property-read int $id
 * @property string $title
 * @property string|null $description
 * @property array<int, string>|null $steps
 * @property TicketStatus $status
 * @property TicketPriority|null $priority
 * @property array<int, string>|null $labels
 * @property string|null $milestone
 * @property array<int, string>|null $screenshot_paths
 * @property string|null $page_url
 * @property string $app_version
 * @property string $role
 * @property string|null $github_issue_url
 * @property int|null $github_issue_number
 * @property string|null $github_state_reason
 * @property \Illuminate\Support\Carbon|null $resolved_at
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
        'steps',
        'status',
        'priority',
        'labels',
        'milestone',
        'screenshot_paths',
        'page_url',
        'app_version',
        'role',
        'github_issue_url',
        'github_issue_number',
        'github_state_reason',
        'resolved_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'labels' => 'array',
            'screenshot_paths' => 'array',
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
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
     * Oli, 2026-07-26: "nos labels customs peuvent tout à fait se synchroniser vers github [...]
     * la seule chose particulière c'est de pouvoir en garder qui sont privés" — custom labels
     * sync freely by default; `github.private_labels` is a DENY-list, not an allow-list. A
     * ticket is only blocked from pushing at all when EVERY label it carries is private (no
     * labels at all is not "only private", so it's still syncable).
     */
    public function isSyncable(): bool
    {
        $labels = (array) $this->labels;

        if ($labels === []) {
            return true;
        }

        /** @var list<string> $privateLabels */
        $privateLabels = config()->array('two-way-ticket.github.private_labels', []);

        return array_diff($labels, $privateLabels) !== [];
    }

    /**
     * @return list<string>
     */
    public function syncableLabels(): array
    {
        /** @var list<string> $privateLabels */
        $privateLabels = config()->array('two-way-ticket.github.private_labels', []);

        return array_values(array_diff((array) $this->labels, $privateLabels));
    }
}
