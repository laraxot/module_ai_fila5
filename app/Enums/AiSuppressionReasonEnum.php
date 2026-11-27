<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Motivi di soppressione (do-not-contact) persistenti.
 *
 * Vengono rispettati anche nei re-import: la suppression sopravvive
 * alle future importazioni CSV/OSM per la stessa identità deterministica.
 *
 * @property string $value
 */
enum AiSuppressionReasonEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case DO_NOT_CONTACT = 'do_not_contact';
    case UNSUBSCRIBED = 'unsubscribed';
    case BOUNCED = 'bounced';
    case INVALID_EMAIL = 'invalid_email';
    case GDPR_REQUEST = 'gdpr_request';

    public function isHardBounce(): bool
    {
        return $this === self::BOUNCED;
    }

    public function isUserRequest(): bool
    {
        return in_array($this, [self::DO_NOT_CONTACT, self::UNSUBSCRIBED, self::GDPR_REQUEST], true);
    }
}
