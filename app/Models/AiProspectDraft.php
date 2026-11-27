<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\AI\Enums\AiProspectDraftStatusEnum;
use Modules\Xot\Models\XotBaseModel;

/**
 * Draft e brief per primo contatto con una prospect.
 *
 * MAI auto-send: sent_at popolato solo dopo revisione umana esplicita.
 *
 * @property int $id
 * @property int $ai_prospect_id
 * @property string $subject
 * @property string $body
 * @property string $channel
 * @property AiProspectDraftStatusEnum $status
 * @property array<string, mixed> $lawful_basis
 * @property array<string, mixed>|null $personalization
 * @property string|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> newModelQuery()
 * @method static Builder<static> newQuery()
 * @method static Builder<static> query()
 */
class AiProspectDraft extends XotBaseModel
{
    /** @var list<string> */
    protected $fillable = [
        'ai_prospect_id',
        'subject',
        'body',
        'channel',
        'status',
        'lawful_basis',
        'personalization',
        'reviewed_by_user_id',
        'reviewed_at',
        'sent_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ai_prospect_id' => 'integer',
            'subject' => 'string',
            'body' => 'string',
            'channel' => 'string',
            'status' => AiProspectDraftStatusEnum::class,
            'lawful_basis' => 'array',
            'personalization' => 'array',
            'reviewed_by_user_id' => 'string',
            'reviewed_at' => 'datetime',
            'sent_at' => 'datetime',
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
