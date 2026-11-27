<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Xot\Models\XotBaseModel;

/**
 * Durable do-not-contact tombstone. Re-import must never re-enable a suppressed prospect.
 *
 * @property int $id
 * @property string $workspace
 * @property string $fingerprint
 * @property string $reason
 * @method static Builder<static> query()
 */
class AiProspectSuppression extends XotBaseModel
{
    protected $table = 'ai_prospect_suppressions';

    /** @var list<string> */
    protected $fillable = ['workspace', 'fingerprint', 'reason'];
}
