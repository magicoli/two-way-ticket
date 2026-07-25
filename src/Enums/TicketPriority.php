<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum TicketPriority: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';

    case Medium = 'medium';

    case High = 'high';

    case Urgent = 'urgent';

    public function getLabel(): string
    {
        return (string) __('two-way-ticket::two-way-ticket.priority.'.$this->value);
    }

    /**
     * @return string|array<int, string>
     */
    public function getColor(): string|array
    {
        return match ($this) {
            self::Low => 'success',
            self::Medium => 'warning',
            self::High => 'danger',
            self::Urgent => Color::Pink,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Low => Heroicon::OutlinedArrowDown,
            self::Medium => Heroicon::OutlinedArrowRight,
            self::High => Heroicon::OutlinedArrowUp,
            self::Urgent => Heroicon::OutlinedFire,
        };
    }

    /** An unanswered priority resolves to the lowest, so it never blocks triage. */
    public static function fromInput(self|string|null $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return ($value === null ? null : self::tryFrom($value)) ?? self::Low;
    }

    /** Ranked least to most urgent — sorting by the column itself is alphabetical, meaningless. */
    public function rank(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }
}
