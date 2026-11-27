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
 * Replay/race:
 * - Only PENDING proposals may be confirmed.
 * - When the model is persisted (`exists`), status is advanced with a
 *   compare-and-swap UPDATE (`WHERE status = pending`) so concurrent confirms
 *   cannot both execute the domain handler.
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
        $current = $this->statusOf($proposal);

        if ($current !== AiActionProposalStatusEnum::PENDING) {
            throw new RuntimeException(
                sprintf(
                    'AiActionProposal [%s] cannot be confirmed from status [%s]; only PENDING is allowed.',
                    (string) $proposal->getKey(),
                    $current?->value ?? 'unknown',
                )
            );
        }

        $confirmedAt = Carbon::now();
        $confirmedBy = (string) $confirmedByUserId;

        if ($proposal->exists) {
            $affected = AiActionProposal::query()
                ->whereKey($proposal->getKey())
                ->where('status', AiActionProposalStatusEnum::PENDING)
                ->update([
                    'status' => AiActionProposalStatusEnum::CONFIRMED,
                    'confirmed_by_user_id' => $confirmedBy,
                    'confirmed_at' => $confirmedAt,
                ]);

            if ($affected !== 1) {
                throw new RuntimeException(
                    sprintf(
                        'AiActionProposal [%s] was not PENDING at confirm time (lost race or already advanced).',
                        (string) $proposal->getKey(),
                    )
                );
            }

            $proposal->refresh();
        } else {
            // In-memory / unit-test path (saving may be blocked by event).
            $proposal->forceFill([
                'status' => AiActionProposalStatusEnum::CONFIRMED,
                'confirmed_by_user_id' => $confirmedBy,
                'confirmed_at' => $confirmedAt,
            ])->save();
        }

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

    private function statusOf(AiActionProposal $proposal): ?AiActionProposalStatusEnum
    {
        $status = $proposal->status;

        if ($status instanceof AiActionProposalStatusEnum) {
            return $status;
        }

        if (is_string($status)) {
            return AiActionProposalStatusEnum::tryFrom($status);
        }

        return null;
    }
}
