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
<<<<<<< .merge_file_mXfDtm
=======
 * Replay/race:
 * - Only PENDING proposals may be confirmed.
 * - When the model is persisted (`exists`), status is advanced with a
 *   compare-and-swap UPDATE (`WHERE status = pending`) so concurrent confirms
 *   cannot both execute the domain handler.
 *
 * @see docs/contracts/confirm-ai-action-proposal-action.md
 *
>>>>>>> .merge_file_Z50Og1
 * @contract docs/contracts/confirm-ai-action-proposal-action.md
 */
class ConfirmAiActionProposalAction
{
    use QueueableAction;

    public function __construct(private readonly AiActionHandlerRegistry $registry) {}

    public function execute(AiActionProposal $proposal, int|string $confirmedByUserId): AiActionProposal
    {
        $current = $this->statusOf($proposal);

        /**
         * Domain invariant: only PENDING proposals may transition to CONFIRMED.
         *
         * @see AiActionProposalStatusEnum::PENDING
         * @see CancelAiActionProposalAction::execute()
         */
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
                $proposalKey = $proposal->getKey();
                $key = is_int($proposalKey) || is_string($proposalKey) ? $proposalKey : 'unknown';
                throw new RuntimeException(
                    sprintf(
                        'AiActionProposal [%s] was not PENDING at confirm time (lost race or already advanced).',
                        $key,
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

    /**
     * `status` puo' arrivare come enum (cast del modello) o come stringa grezza
     * (righe non ancora normalizzate): `tryFrom` restituisce `?Enum`, mai un cast.
     *
     * @see docs/adr/0004-phpstan-zero-errori.md — narrowing `instanceof`/`is_string` sul tipo unione
     *      invece di `(AiActionProposalStatusEnum) $status`
     */
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
