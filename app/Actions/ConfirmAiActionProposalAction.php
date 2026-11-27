<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Illuminate\Support\Carbon;
use Modules\AI\Adapters\AiActionHandlerRegistry;
use Modules\AI\Enums\AiActionProposalStatusEnum;
use Modules\AI\Models\AiActionProposal;
use RuntimeException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Confirms a pending `AiActionProposal` and immediately executes it via the
 * handler registered in {@see AiActionHandlerRegistry} for its `type`.
 *
 * On success the proposal is marked `executed`, on failure `failed` with the
 * error message stored for audit/debugging.
 *
 * Replay/race: only PENDING proposals may be confirmed. Non-PENDING input is
 * rejected so a second confirm cannot re-run the domain handler.
 *
 * @see docs/contracts/confirm-ai-action-proposal-action.md
 *
 * @contract docs/contracts/confirm-ai-action-proposal-action.md
 */
class ConfirmAiActionProposalAction
{
    use QueueableAction;

    public function __construct(private readonly AiActionHandlerRegistry $registry) {}

    public function execute(AiActionProposal $proposal, int|string $confirmedByUserId): AiActionProposal
    {
        /**
         * Domain invariant: only PENDING proposals may transition to CONFIRMED.
         *
         * @see AiActionProposalStatusEnum::PENDING
         * @see CancelAiActionProposalAction::execute()
         */
        $status = $proposal->status;
        if ($status instanceof AiActionProposalStatusEnum) {
            $current = $status;
        } elseif (is_string($status)) {
            $current = AiActionProposalStatusEnum::tryFrom($status);
        } else {
            $current = null;
        }

        if (! $current instanceof AiActionProposalStatusEnum || $current !== AiActionProposalStatusEnum::PENDING) {
            $proposalKey = $proposal->getKey();
            $key = is_int($proposalKey) || is_string($proposalKey) ? $proposalKey : 'unknown';

            throw new RuntimeException(
                sprintf(
                    'AiActionProposal [%s] cannot be confirmed from status [%s]; only PENDING is allowed.',
                    $key,
                    $current instanceof AiActionProposalStatusEnum ? $current->value : 'unknown',
                )
            );
        }

        $proposal->forceFill([
            'status' => AiActionProposalStatusEnum::CONFIRMED,
            'confirmed_by_user_id' => (string) $confirmedByUserId,
            'confirmed_at' => Carbon::now(),
        ])->save();

        $handler = $this->registry->get($proposal->type);

        if ($handler === null) {
            $proposal->forceFill([
                'status' => AiActionProposalStatusEnum::FAILED,
                'error' => "No AiActionHandler registered for type [{$proposal->type}].",
            ])->save();

            return $proposal;
        }

        try {
            $result = $handler->handle($proposal);

            $proposal->forceFill([
                'status' => AiActionProposalStatusEnum::EXECUTED,
                'executed_at' => Carbon::now(),
                'result' => $result,
            ])->save();
        } catch (\Throwable $throwable) {
            $proposal->forceFill([
                'status' => AiActionProposalStatusEnum::FAILED,
                'error' => $throwable->getMessage(),
            ])->save();
        }

        return $proposal;
    }
}
