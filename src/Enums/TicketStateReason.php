<?php

declare(strict_types=1);

namespace Magicoli\TwoWayTicket\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * GitHub's own `issue.state_reason` values, verbatim. Deliberately NOT cast on the model: the
 * column mirrors whatever GitHub sends, and a value we don't know about yet must survive a sync
 * rather than blow up on an enum cast. This enum is here to offer the closing choices and to put
 * a readable label on a known value — {@see self::labelFor()} falls back to the raw string.
 */
enum TicketStateReason: string implements HasColor, HasLabel
{
    case Completed = 'completed';

    case NotPlanned = 'not_planned';

    case Duplicate = 'duplicate';

    case Reopened = 'reopened';

    /**
     * The reasons GitHub offers when CLOSING an issue — `reopened` isn't one of them.
     *
     * @return array<string, string>
     */
    public static function closingOptions(): array
    {
        return collect([self::Completed, self::NotPlanned, self::Duplicate])->mapWithKeys(fn(self $reason): array => [
            $reason->value => $reason->getLabel(),
        ])->all();
    }

    /** Readable label for a stored value, or the value itself when GitHub sends something new. */
    public static function labelFor(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value)?->getLabel() ?? $value;
    }

    public function getLabel(): string
    {
        return (string) __('two-way-ticket::two-way-ticket.state_reason.' . $this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::NotPlanned, self::Duplicate => 'gray',
            self::Reopened => 'warning',
        };
    }
}
