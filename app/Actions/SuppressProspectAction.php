<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\AI\Enums\AiSuppressionReasonEnum;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiSuppression;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Soppressione (do-not-contact) di una prospect per identità deterministica.
 *
 * Persistente: rispettata anche nei re-import, così un prospect soppresso
 * non riappare mai dopo una nuova importazione CSV/OSM con lo stesso public_id.
 *
 * reason: do_not_contact | unsubscribed | bounced | invalid_email | gdpr_request
 *
 * @see docs/adr/0004-phpstan-zero-errori.md — enum `AiSuppressionReasonEnum` tipizzato nei
 *      parametri invece di stringhe libere: il motivo e' sempre un caso noto
 */
class SuppressProspectAction
{
    use QueueableAction;

    public function execute(
        string $workspaceId,
        string $publicId,
        AiSuppressionReasonEnum $reason,
        ?string $note = null,
        ?string $suppressedByUserId = null,
        ?string $email = null,
    ): AiSuppression {
        Assert::notEmpty($workspaceId);
        Assert::notEmpty($publicId);

        $suppression = new AiSuppression();
        $suppression->fill([
            'public_id' => $publicId,
            'workspace_id' => $workspaceId,
            'email_hash' => $email !== null ? hash('sha256', mb_strtolower(trim($email))) : null,
            'reason' => $reason->value,
            'note' => $note,
            'suppressed_by_user_id' => $suppressedByUserId,
            'suppressed_at' => now(),
        ]);
        $suppression->save();

        // Aggiorna anche lo stato della prospect esistente, se presente
        AiProspect::query()
            ->where('public_id', $publicId)
            ->where('workspace_id', $workspaceId)
            ->update(['status' => AiProspectStatusEnum::COLD_LOST->value]);

        return $suppression;
    }

    public static function isSuppressed(string $workspaceId, string $publicId): bool
    {
        return AiSuppression::findForPublicId($publicId, $workspaceId) !== null;
    }
}
