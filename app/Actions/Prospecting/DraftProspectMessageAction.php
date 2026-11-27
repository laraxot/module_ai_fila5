<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectSuppression;
use Spatie\QueueableAction\QueueableAction;

/**
 * Returns a human-review-only draft. No recipient resolution, email integration or sending.
 * An email published on a website is not proof of marketing consent in Italy.
 */
final class DraftProspectMessageAction
{
    use QueueableAction;

    /**
     * @return array{subject: string, body: string, status: string, requires_approval: bool, send_permitted: bool}
     */
    public function execute(string $workspace, int $id, string $senderCompany, string $offer): array
    {
        $prospect = AiProspect::query()->where('workspace', $workspace)->whereKey($id)->first();
        if (! $prospect instanceof AiProspect || $prospect->is_suppressed
            || AiProspectSuppression::query()->where('workspace', $workspace)->where('fingerprint', $prospect->fingerprint)->exists()) {
            throw ValidationException::withMessages(['id' => 'Prospect non disponibile per la bozza.']);
        }

        $senderCompany = trim($senderCompany);
        $offer = trim($offer);
        if ($senderCompany === '' || $offer === '' || mb_strlen($senderCompany) > 120 || mb_strlen($offer) > 300) {
            throw ValidationException::withMessages(['offer' => 'Fornire azienda e offerta veritiere.']);
        }

        // No statement that a business lacks a tool: missing evidence is not evidence of absence.
        $subject = 'Possibile confronto con '.mb_substr($prospect->name, 0, 80);
        $body = "Buongiorno,\n\nsono di ".$senderCompany.'. '
            .'Vorremmo capire se può essere utile confrontarci su: '.$offer.".\n\n"
            .'Prima di proporre soluzioni preferiamo capire come lavorate oggi e quali strumenti usate già.'
            ."\n\nCordiali saluti,\n".$senderCompany;

        return [
            'subject' => $subject,
            'body' => $body,
            'status' => 'draft_only',
            'requires_approval' => true,
            'send_permitted' => false,
        ];
    }
}
