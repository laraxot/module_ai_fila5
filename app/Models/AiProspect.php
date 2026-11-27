<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\Xot\Models\XotBaseModel;

/**
 * A business-level prospect, not a personal contact. Never use this model to send messages.
 *
 * Identita' deterministica: `public_id` deriva da `sha256(domain)`, cosi' ogni
 * azienda e' unica a prescindere dalla fonte di importazione (CSV, OSM, listing pubblico).
 * Isolamento per workspace: `workspace_id` separa i prospect di ogni cliente.
 * Nessun invio automatico: lo stato e' solo di consultazione, mai attiva bozze o email.
 *
 * @see docs/prospecting.md  — politica di scoperta e uso dei prospect (opt-in, nessuna outreach automatica)
 * @see docs/wiki/entities/ai-prospect.md  — entita' e invarianti del dominio
 * @see tests/Unit/Models/AiProspectTest.php — contratto verificato per identificazione e isolamento workspace
 * @see docs/adr/0004-phpstan-zero-errori.md — generics `Builder<static>` nei `@method` e `@property`
 *      unione documento+casts allineati a `database/migrations/2026_11_27_000001_create_ai_prospects_table.php`
 *      (merge dello schema: ogni colonna ha un tipo PHP dichiarato, niente `mixed` implicito)
 *
 * @property int $id
 * @property string $workspace_id
 * @property string $workspace
 * @property string $public_id
 * @property string $fingerprint
 * @property string|null $name
 * @property string|null $domain
 * @property string|null $website
 * @property string|null $source
 * @property string|null $source_url
 * @property string|null $category
 * @property string|null $city
 * @property string|null $region
 * @property string|null $country_code
 * @property string|null $country
 * @property string|null $industry
 * @property string|null $phone
 * @property string|null $source_provider
 * @property array<string, mixed>|null $raw_data
 * @property array<int, array<string, mixed>>|null $signals
 * @property string|null $summary
 * @property AiProspectStatusEnum $status
 * @property int $score
 * @property string $stage
 * @property bool $is_suppressed
 * @property Carbon|null $source_at
 * @property Carbon|null $discovered_at
 * @property Carbon|null $researched_at
 * @property Carbon|null $last_reviewed_at
 * @property Carbon|null $last_contact_attempt_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> newModelQuery()
 * @method static Builder<static> newQuery()
 * @method static Builder<static> query()
 */
class AiProspect extends XotBaseModel
{
    protected $table = 'ai_prospects';

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'workspace', 'public_id', 'fingerprint', 'name', 'domain', 'website', 'category',
        'city', 'region', 'country_code', 'country', 'industry', 'phone', 'source_provider', 'source',
        'source_url', 'source_at', 'raw_data', 'signals', 'score', 'stage', 'is_suppressed', 'discovered_at',
        'researched_at', 'summary', 'status', 'last_reviewed_at', 'last_contact_attempt_at',
    ];

    /**
     * @return array<string, string>
     *
     * @see tests/Unit/Models/AiProspectTest.php — il test usa gli attributi già normalizzati dal modello
     */
    protected function casts(): array
    {
        return [
            'workspace_id' => 'string',
            'workspace' => 'string',
            'public_id' => 'string',
            'fingerprint' => 'string',
            'name' => 'string',
            'domain' => 'string',
            'website' => 'string',
            'source' => 'string',
            'source_url' => 'string',
            'source_at' => 'datetime',
            'city' => 'string',
            'region' => 'string',
            'country_code' => 'string',
            'country' => 'string',
            'industry' => 'string',
            'phone' => 'string',
            'source_provider' => 'string',
            'raw_data' => 'array',
            'signals' => 'array',
            'score' => 'integer',
            'stage' => 'string',
            'is_suppressed' => 'boolean',
            'discovered_at' => 'datetime',
            'researched_at' => 'datetime',
            'summary' => 'string',
            'status' => AiProspectStatusEnum::class,
            'last_reviewed_at' => 'datetime',
            'last_contact_attempt_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Compute deterministic public_id from domain.
     *
     * @see modules pipeline docs/wiki/concepts/ai-prospect-pipeline.md — perche' il public_id e' un hash del dominio
     */
    public static function deterministicId(string $domain): string
    {
        return hash('sha256', mb_strtolower(trim($domain)));
    }

    /**
     * @param  Builder<static>  $query
     * @param  string  $workspaceId
     * @return Builder<static>
     *
     * @see app/Actions/Prospect/BuildProspectBriefAction.php — filtra i prospect del workspace per generare il brief
     */
    public function scopeByWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /** @return HasMany<AiProspectSignal, $this>
     *
     * @see app/Models/AiProspectSignal.php — segnali calcolati dal sito pubblico (ranking e brief)
     */
    public function signals(): HasMany
    {
        return $this->hasMany(AiProspectSignal::class, 'ai_prospect_id');
    }
}
