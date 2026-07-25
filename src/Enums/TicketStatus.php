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
 * covers progress that has no GitHub label equivalent. For a ticket linked to GitHub,
 * Resolved/InProgress are kept in sync with the issue's real open/closed state (see
 * SyncGithubIssues) — the others (New, Triaged) are purely local.
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
