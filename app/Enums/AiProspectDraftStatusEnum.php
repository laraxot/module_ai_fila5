<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Statuses for a draft prospect brief.
 *
 * draft -> pending_review -> reviewed -> sent -> suppressed
 *
 * @property string $value
 */
enum AiProspectDraftStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case REVIEWED = 'reviewed';
    case SENT = 'sent';
    case SUPPRESSED = 'suppressed';

    public function isPending(): bool
    {
        return $this === self::DRAFT;
    }

    /** Stato terminale: la bozza non è più in attesa di decisione umana. */
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
