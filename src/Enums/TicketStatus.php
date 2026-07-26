<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Deliberately just 4 values — see SPEC.md §2: anything GitHub already expresses via a LABEL
 * (wontfix, duplicate, invalid...) is a label here too, not a parallel status. This enum only
 * covers progress that has no GitHub label equivalent.
 *
 * Oli, 2026-07-26: "on s'aligne à 100% sur le système de GitHub" — for a ticket linked to GitHub,
 * sync only ever touches the one thing GitHub actually tracks: open vs closed. Closed => Resolved,
 * full stop, regardless of WHY it was closed (completed, wontfix, duplicate...) — that reason
 * already lives in the labels, never approximated into a second local status. Open never forces
 * anything (New/Triaged/InProgress are purely local progress, GitHub has no equivalent to sync
 * from) — the sole exception is a ticket reopened after being Resolved, which has to move
 * somewhere since it can no longer claim Resolved while open (see SyncGithubIssues).
 */
enum TicketStatus: string implements HasColor, HasIcon, HasLabel
{
    case New = 'new';

    case Triaged = 'triaged';

    case InProgress = 'in_progress';

    case Resolved = 'resolved';

    public function getLabel(): string
    {
        return (string) __('two-way-ticket::two-way-ticket.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Triaged => 'info',
            self::InProgress => 'warning',
            self::Resolved => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::New => Heroicon::OutlinedInbox,
            self::Triaged => Heroicon::OutlinedMagnifyingGlass,
            self::InProgress => Heroicon::OutlinedWrench,
            self::Resolved => Heroicon::OutlinedCheckCircle,
        };
    }
}
