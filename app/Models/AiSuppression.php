<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Xot\Models\XotBaseModel;

/**
 * Lista di soppressione (do-not-contact) persistente per identità deterministiche.
 *
 * Rispettata anche nei re-import: la suppression sopravvive alle future
 * importazioni CSV/OSM per la stessa identità deterministica (public_id).
 *
 * @property int $id
 * @property string $public_id
 * @property string $workspace_id
 * @property string|null $email_hash
 * @property string $reason
 * @property string|null $note
 * @property string|null $suppressed_by_user_id
 * @property Carbon $suppressed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> newModelQuery()
 * @method static Builder<static> newQuery()
 * @method static Builder<static> query()
 */
class AiSuppression extends XotBaseModel
{
    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'workspace_id',
        'email_hash',
        'reason',
        'note',
        'suppressed_by_user_id',
        'suppressed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'public_id' => 'string',
            'workspace_id' => 'string',
            'email_hash' => 'string',
            'reason' => 'string',
            'note' => 'string',
            'suppressed_by_user_id' => 'string',
            'suppressed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Find suppression by deterministic public_id and workspace.
     */
    public static function findForPublicId(string $publicId, string $workspaceId): ?self
    {
        return static::query()
            ->where('public_id', $publicId)
            ->where('workspace_id', $workspaceId)
            ->first();
    }
}
