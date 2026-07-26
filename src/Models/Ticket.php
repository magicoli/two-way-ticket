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
 * @property array<int, string>|null $steps
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
 * @property string|null $github_state_reason
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
        'steps',
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
        'github_state_reason',
        'closed_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
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
}
