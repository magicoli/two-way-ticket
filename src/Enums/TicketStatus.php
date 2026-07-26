<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Oli, 2026-07-26: "Où est-ce que ça apparaît dans GitHub?" — GitHub's issue.state has exactly
 * two values, open and closed. Nothing else (no New/Triaged/InProgress/Resolved — those were
 * invented, not something GitHub tracks). A ticket not yet linked to GitHub still uses this same
 * open/closed vocabulary locally, so there's one concept, not two.
 */
enum TicketStatus: string implements HasColor, HasIcon, HasLabel
{
    case Open = 'open';

    case Closed = 'closed';

    public function getLabel(): string
    {
        return (string) __('two-way-ticket::two-way-ticket.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Closed => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-exclamation-circle',
            self::Closed => 'heroicon-o-check-circle',
        };
    }
}
