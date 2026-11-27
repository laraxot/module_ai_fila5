<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Offline analysis of supplied public HTML. No HTTP requests and no LLM guesses.
 * A missing widget is UNKNOWN, not a proven absence.
 */
final class ExtractProspectSignalsAction
{
    use QueueableAction;

    /**
     * @return list<array{code: string, status: string, evidence: string, source_url: string, confidence: float}>
     */
    public function execute(string $html, string $sourceUrl): array
    {
        if (strlen($html) > 524288 || trim($sourceUrl) === '') {
            throw ValidationException::withMessages(['html' => 'Pagina troppo grande o fonte mancante.']);
        }

        $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        $patterns = [
            'manual_booking_only' => '/(?:prenotazion[ei]|booking|reservations?)\s+(?:possibili?\s+)?solo\s+(?:per|al|via)\s+telefon[oa]/iu',
            'manual_order_only' => '/(?:ordinazion[ei]|ordini|orders?)\s+(?:possibili?\s+)?solo\s+(?:per|al|via)\s+telefon[oa]/iu',
        ];

        $signals = [];
        foreach ($patterns as $code => $pattern) {
            if (preg_match($pattern, $plain, $match, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            $signals[] = [
                'code' => $code,
                'status' => 'observed_hint',
                'evidence' => mb_substr($match[0][0], 0, 160),
                'source_url' => $sourceUrl,
                'confidence' => 0.8,
            ];
        }

        foreach ([
            'booking_link' => '/<a\b[^>]*>\s*(?:<[^>]+>\s*)*(?:prenota|book\s+now|reserve\s+(?:a\s+)?table)/iu',
            'catalogue_link' => '/<a\b[^>]*>\s*(?:<[^>]+>\s*)*(?:men[uù]|catalogo|our\s+menu)/iu',
            'ordering_link' => '/<a\b[^>]*>\s*(?:<[^>]+>\s*)*(?:ordina|order\s+online)/iu',
        ] as $code => $pattern) {
            if (preg_match($pattern, $html) !== 1) {
                continue;
            }

            $signals[] = [
                'code' => $code,
                'status' => 'observed_hint',
                'evidence' => 'Link con etichetta pertinente osservato nella pagina; verifica manuale necessaria.',
                'source_url' => $sourceUrl,
                'confidence' => 0.65,
            ];
        }

        return $signals;
    }
}
