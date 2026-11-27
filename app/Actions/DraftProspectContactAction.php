<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Modules\AI\Enums\AiProspectDraftStatusEnum;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectDraft;
use Modules\AI\Models\AiProspectSignal;
use Spatie\QueueableAction\QueueableAction;

/**
 * Genera bozza di primo contatto per una prospect qualificata.
 *
 * RISCHI: non inviare mai automaticamente. Questo action crea solo la bozza,
 * che attende revisione umana. Non invia email senza OK esplicito.
 *
 * - lawful_basis e consenso rimangono obbligatori
 * - merge tag riempiti da dati prospect solo dopo approvazione umana
 */
class DraftProspectContactAction
{
    use QueueableAction;

    /**
     * @param  array<string, mixed>  $userNotes
     *
     * @throws \Exception se non c'è abbastanza prova (confidence < 30)
     */
    public function execute(AiProspect $prospect, array $userNotes = []): AiProspectDraft
    {
        $latestSignal = $prospect->signals()->latest('source_at')->first();

        if ($latestSignal === null || $latestSignal->confidence < 30) {
            throw new \Exception('Insufficient signal confidence to draft contact: '.($latestSignal instanceof AiProspectSignal ? $latestSignal->confidence : 0));
        }

        $subject = 'Introduzione a '.($prospect->name ?? $prospect->domain);

        $body = $this->buildDraftBody($prospect, $latestSignal);

        $draft = new AiProspectDraft();
        $draft->fill([
            'ai_prospect_id' => $prospect->getKey(),
            'subject' => $subject,
            'body' => $body,
            'channel' => 'email',
            'status' => AiProspectDraftStatusEnum::DRAFT,
            'lawful_basis' => [
                'basis' => 'legitimate_interest',
                'evidence' => 'Prospect found via public business data, no private scraping',
            ],
            'personalization' => [
                'icebreaker' => $userNotes['icebreaker'] ?? null,
                'hooks' => $userNotes['hooks'] ?? null,
                'company_data' => $userNotes['company_data'] ?? null,
            ],
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'sent_at' => null,
        ]);
        $draft->save();

        return $draft;
    }

    private function buildDraftBody(AiProspect $prospect, AiProspectSignal $signal): string
    {
        $name = $prospect->name ?? $prospect->domain;
        $company = $prospect->website ?? '';
        $source = $signal->source;
        $confidence = $signal->confidence;

        $lines = [
            "Ciao $name,",
            '',
            "Ho visto la vostra attività in [$company] ($company) e volevo propormi come potenziale fornitore di servizi per [your specific offering].",
            '',
            'Ciò che rende interessante il vostro caso:',
            "- Confidenza dei dati: $confidence/100",
            "- Fonte: $source",
            '- Dati disponibili: territorio, settore (se disponibile)',
            '',
            'Se siete interessati a una breve call (15 min) per capire se possiamo collaborare, fatemi pure sapere un orario che vi aggrata.',
            '',
            'Cordiali saluti,',
            'Team AI Module',
        ];

        return implode("\n", $lines);
    }
}
