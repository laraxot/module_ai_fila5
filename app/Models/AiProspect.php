<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Xot\Models\XotBaseModel;

/**
 * A business-level prospect, not a personal contact. Never use this model to send messages.
 *
 * @property int $id
 * @property string $workspace
 * @property string $fingerprint
 * @property string $name
 * @property string|null $website
 * @property string|null $domain
 * @property string|null $category
 * @property string|null $city
 * @property string|null $country
 * @property string $source_provider
 * @property string $source_url
 * @property array<int, array<string, mixed>>|null $signals
 * @property int $score
 * @property string $stage
 * @property bool $is_suppressed
 * @property Carbon|null $discovered_at
 * @property Carbon|null $researched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static> query()
 */
class AiProspect extends XotBaseModel
{
    protected $table = 'ai_prospects';

    /** @var list<string> */
    protected $fillable = [
        'workspace', 'fingerprint', 'name', 'website', 'domain', 'category',
        'city', 'country', 'source_provider', 'source_url', 'signals', 'score',
        'stage', 'is_suppressed', 'discovered_at', 'researched_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'signals' => 'array',
            'score' => 'integer',
            'is_suppressed' => 'boolean',
            'discovered_at' => 'datetime',
            'researched_at' => 'datetime',
        ];
    }
}
