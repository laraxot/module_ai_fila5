<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Enum per stati AiActionProposal
 *
 * Lifecycle: pending -> confirmed -> executed
 *                      \-> cancelled
 *                      \-> failed
 */
enum AiActionProposalStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case PENDING = 'pending';
    case CANCELLED = 'cancelled';
    case CONFIRMED = 'confirmed';
    case EXECUTED = 'executed';
    case FAILED = 'failed';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isFinal(): bool
    {
        return $this !== self::PENDING;
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeCancelled(): bool
    {
        return $this === self::PENDING;
    }
}