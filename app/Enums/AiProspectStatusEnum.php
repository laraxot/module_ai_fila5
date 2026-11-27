<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Ciclo di vita di una AiProspect.
 *
 * pending -> qualified -> contacted -> cold/lost -> won (futuro).
 *
 * @property string $value
 */
enum AiProspectStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case NEW = 'new';
    case QUALIFIED = 'qualified';
    case CONTACTED = 'contacted';
    case COLD_LOST = 'cold_lost';
    case WON = 'won';

    public function isPending(): bool
    {
        return $this === self::NEW;
    }

    /** Stato terminale: la proposta non è più in attesa di decisione umana. */
    public function isFinal(): bool
    {
        return ! $this->isPending();
    }

    public function canBeConfirmed(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }
}
