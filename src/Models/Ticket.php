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
     * Only a ticket carrying at least one syncable label (config `github.syncable_labels`) can
     * be pushed to GitHub at all — see SPEC.md §1: a label like "billing" that's never in that
     * list stays strictly local, by design.
     */
    public function isSyncable(): bool
    {
        /** @var list<string> $syncableLabels */
        $syncableLabels = config()->array('two-way-ticket.github.syncable_labels', []);

        return array_intersect((array) $this->labels, $syncableLabels) !== [];
    }
}
