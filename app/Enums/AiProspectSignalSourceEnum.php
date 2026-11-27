<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Fonti delle prove e segnali per una prospect.
 *
 * source_at: timestamp della raccolta dati.
 * source_url: URL del business/OSM pagina esaminata.
 * quality: fonte (csv, osm, pubblica) x confidenza (high, medium, low).
 * score: spiegabile (0-100) con breakdown per ogni campo.
 *
 * @property string $value
 */
enum AiProspectSignalSourceEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case CSV = 'csv';
    case OSM = 'osm';
    case PUBLIC_LISTING = 'public_listing';
    case WEBSITE_SIGNALS = 'website_signals';
    case EMAIL_VERIFICATION = 'email_verification';

    /**
     * Which columns does this signal map to on AiProspect?
     *
     * @return array<string, string>
     */
    public static function columnMap(): array
    {
        return [
            'source' => 'string',
            'source_url' => 'string',
            'source_at' => 'datetime',
            'quality_source' => 'string',
            'confidence' => 'integer',
            'score' => 'integer',
        ];
    }
}
