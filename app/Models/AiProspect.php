<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\Xot\Models\XotBaseModel;

/**
 * Prospect B2B per la pipeline di ricerca e acquisizione nuovi clienti del modulo AI.
 *
 * Identita' deterministica: `public_id` deriva da `sha256(domain)`, cosi' ogni
 * azienda e' unica a prescindere dalla fonte di importazione (CSV, OSM, listing pubblico).
 * Isolamento per workspace: `workspace_id` separa i prospect di ogni cliente.
 * Nessun invio automatico: lo stato e' solo di consultazione, mai attiva bozze o email.
 *
 * @property int $id
 * @property string $public_id
 * @property string $workspace_id
 * @property string|null $name
 * @property string|null $domain
 * @property string|null $website
 * @property string|null $source
 * @property string|null $source_url
 * @property Carbon|null $source_at
 * @property string|null $city
 * @property string|null $region
 * @property string|null $country_code
 * @property string|null $industry
 * @property string|null $phone
 * @property array<string, mixed>|null $raw_data
 * @property string|null $summary
 * @property AiProspectStatusEnum $status
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
    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'workspace_id',
        'name',
        'domain',
        'website',
        'source',
        'source_url',
        'source_at',
        'city',
        'region',
        'country_code',
        'industry',
        'phone',
        'raw_data',
        'summary',
        'status',
        'last_reviewed_at',
        'last_contact_attempt_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'public_id' => 'string',
            'workspace_id' => 'string',
            'name' => 'string',
            'domain' => 'string',
            'website' => 'string',
            'source' => 'string',
            'source_url' => 'string',
            'source_at' => 'datetime',
            'city' => 'string',
            'region' => 'string',
            'country_code' => 'string',
            'industry' => 'string',
            'phone' => 'string',
            'raw_data' => 'array',
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
     */
    public static function deterministicId(string $domain): string
    {
        return hash('sha256', mb_strtolower(trim($domain)));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /** @return HasMany<AiProspectSignal, $this> */
    public function signals(): HasMany
    {
        return $this->hasMany(AiProspectSignal::class, 'ai_prospect_id');
    }
}
