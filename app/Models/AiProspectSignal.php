<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Xot\Models\XotBaseModel;

/**
 * Prova/segnale per una prospect.
 *
 * Ogni segnale ha: fonte (csv, osm, public_listing), timestamp raccolta,
 * qualità fonte e confidenza, breakdown dello score spiegabile.
 * Niente deduzioni da pagine non esaminate: ogni prova è puntuale.
 *
 * @property int $id
 * @property int $ai_prospect_id
 * @property string $source
 * @property string|null $source_url
 * @property Carbon|null $source_at
 * @property string $quality_source
 * @property int $confidence
 * @property int $score
 * @property array<string, mixed>|null $breakdown
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AiProspect $prospect
 *
 * @method static Builder<static> newModelQuery()
 * @method static Builder<static> newQuery()
 * @method static Builder<static> query()
 */
class AiProspectSignal extends XotBaseModel
{
    /** @var list<string> */
    protected $fillable = [
        'ai_prospect_id',
        'source',
        'source_url',
        'source_at',
        'quality_source',
        'confidence',
        'score',
        'breakdown',
        'note',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ai_prospect_id' => 'integer',
            'source' => 'string',
            'source_url' => 'string',
            'source_at' => 'datetime',
            'quality_source' => 'string',
            'confidence' => 'integer',
            'score' => 'integer',
            'breakdown' => 'array',
            'note' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AiProspect, $this> */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(AiProspect::class, 'ai_prospect_id');
    }
}
