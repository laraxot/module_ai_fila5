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
        /**
         * Domain invariant: only PENDING proposals may transition to CANCELLED.
         *
         * @see AiActionProposalStatusEnum::PENDING
         * @see ConfirmAiActionProposalAction::execute()
         */
        $status = $proposal->status;
        $current = $status instanceof AiActionProposalStatusEnum
            ? $status
            : (is_string($status) ? AiActionProposalStatusEnum::tryFrom($status) : null);

        if (!$current instanceof AiActionProposalStatusEnum || $current !== AiActionProposalStatusEnum::PENDING) {
            $proposalKey = $proposal->getKey();
            $key = is_int($proposalKey) || is_string($proposalKey) ? $proposalKey : 'unknown';

            throw new RuntimeException(sprintf(
                'AiActionProposal [%s] cannot be cancelled from status [%s]; only PENDING is allowed.',
                $key,
                $current instanceof AiActionProposalStatusEnum ? $current->value : 'unknown',
            ));
        }

        $proposal->forceFill([
            'status' => AiActionProposalStatusEnum::CANCELLED,
        ])->save();

        return $proposal;
    }
}
