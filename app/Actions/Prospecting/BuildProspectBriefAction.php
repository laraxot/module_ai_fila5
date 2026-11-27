<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Validation\ValidationException;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiProspectSuppression;
use Spatie\QueueableAction\QueueableAction;

/**
 * Evidence-first research brief. All observations are explicitly labelled as hints.
 */
final class BuildProspectBriefAction
{
    use QueueableAction;

    public function execute(string $workspace, int $id): string
    {
        $prospect = AiProspect::query()->where('workspace', $workspace)->whereKey($id)->first();
        if (! $prospect instanceof AiProspect || $prospect->is_suppressed
            || AiProspectSuppression::query()->where('workspace', $workspace)->where('fingerprint', $prospect->fingerprint)->exists()) {
            throw ValidationException::withMessages(['id' => 'Prospect non trovato nel workspace.']);
        }

        $lines = [
            '# Scheda azienda — '.$this->plain($prospect->name),
            '',
            '- Settore: '.$this->plain($prospect->category ?? 'non noto'),
            '- Località: '.$this->plain($prospect->city ?? 'non nota'),
            '- Sito: '.($prospect->website ?? 'non disponibile'),
            '- Fonte originale: '.$prospect->source_url,
            '- Punteggio indicativo: '.$prospect->score.'/100 (non probabilità di acquisto)',
            '- Stato: '.$prospect->stage,
            '',
            '## Segnali osservati, da verificare',
        ];

        $signals = $prospect->signals ?? [];
        if ($signals === []) {
            $lines[] = '- Nessun segnale verificato: non inferire che una funzione manchi.';
        }
        foreach ($signals as $signal) {
            if (! is_array($signal)) {
                continue;
            }
            $code = $this->plain(is_scalar($signal['code'] ?? null) ? (string) $signal['code'] : 'sconosciuto');
            $evidence = $this->plain(is_scalar($signal['evidence'] ?? null) ? (string) $signal['evidence'] : '');
            $url = $this->plain(is_scalar($signal['source_url'] ?? null) ? (string) $signal['source_url'] : $prospect->source_url);
            $lines[] = '- **'.$code.'**: '.$evidence.' — fonte: '.$url;
        }

        $lines[] = '';
        $lines[] = '## Domande per il colloquio';
        $lines[] = '- Come gestite oggi richieste, prenotazioni o ordini fuori orario?';
        $lines[] = '- Qual è il principale collo di bottiglia nella vostra operatività?';
        $lines[] = '- Quali strumenti avete già adottato?';
        $lines[] = '';
        $lines[] = '> Fonte pubblica, non prova di bisogno o consenso al marketing. Verificare a mano prima di ogni uso.';

        return implode("\n", $lines)."\n";
    }

    private function plain(string $value): string
    {
        return trim(str_replace(["\r", "\n", '|', '<', '>'], ' ', mb_substr($value, 0, 500)));
    }
}
