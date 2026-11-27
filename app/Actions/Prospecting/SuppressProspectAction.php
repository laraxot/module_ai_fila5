<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectSuppression;
use Spatie\QueueableAction\QueueableAction;

/**
 * Durable suppression with workspace isolation. Does not implement automatic unsuppression.
 */
final class SuppressProspectAction
{
    use QueueableAction;

    public function execute(string $workspace, int $id, string $reason): AiProspect
    {
        $prospect = AiProspect::query()->where('workspace', $workspace)->whereKey($id)->first();
        if (! $prospect instanceof AiProspect || trim($reason) === '' || mb_strlen($reason) > 255) {
            throw ValidationException::withMessages(['id' => 'Prospect o motivazione non validi per il workspace.']);
        }

        AiProspectSuppression::query()->updateOrCreate(
            ['workspace' => $workspace, 'fingerprint' => $prospect->fingerprint],
            ['reason' => trim($reason)],
        );

        $prospect->fill(['is_suppressed' => true, 'stage' => 'rejected']);
        $prospect->save();

        return $prospect;
    }
}
