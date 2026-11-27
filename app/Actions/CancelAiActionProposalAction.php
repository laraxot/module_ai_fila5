<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Modules\AI\Enums\AiActionProposalStatusEnum;
use Modules\AI\Models\AiActionProposal;
use RuntimeException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Cancels a pending `AiActionProposal` without executing it.
 *
 * @see docs/contracts/cancel-ai-action-proposal-action.md
 *
 * @contract docs/contracts/cancel-ai-action-proposal-action.md
 */
class CancelAiActionProposalAction
{
    use QueueableAction;

    public function execute(AiActionProposal $proposal): AiActionProposal
    {
        // #42: presentation-layer visibility does not enforce the domain state machine.
        // Non-PENDING states must not be reverted by a late or repeated cancel.
        $status = $proposal->status;
        $current = $status instanceof AiActionProposalStatusEnum
            ? $status
            : (is_string($status) ? AiActionProposalStatusEnum::tryFrom($status) : null);

        if ($current !== AiActionProposalStatusEnum::PENDING) {
            throw new RuntimeException(sprintf(
                'AiActionProposal [%s] cannot be cancelled from status [%s]; only PENDING is allowed.',
                (string) $proposal->getKey(),
                $current?->value ?? 'unknown',
            ));
        }

        $proposal->forceFill([
            'status' => AiActionProposalStatusEnum::CANCELLED,
        ])->save();

        return $proposal;
    }
}
