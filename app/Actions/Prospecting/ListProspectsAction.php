<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectSuppression;
use Spatie\QueueableAction\QueueableAction;

/**
 * Workspace-scoped read primitive for an authorized host. Not a public endpoint.
 */
final class ListProspectsAction
{
    use QueueableAction;

    /** @return list<AiProspect> */
    public function execute(string $workspace, int $limit = 50): array
    {
        return array_values(AiProspect::query()
            ->where('workspace', $workspace)
            ->where('is_suppressed', false)
            ->whereNotIn('fingerprint', AiProspectSuppression::query()
                ->select('fingerprint')->where('workspace', $workspace))
            ->orderByDesc('score')
            ->orderBy('name')
            ->limit(max(1, min(250, $limit)))
            ->get()
            ->all());
    }
}
