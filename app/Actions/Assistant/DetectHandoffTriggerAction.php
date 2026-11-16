<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Assistant;

use Spatie\QueueableAction\QueueableAction;

/**
 * Guardrail deterministico, prima di chiamare il modello: se la domanda tocca un argomento
 * che deve gestire una persona (rimborsi, reclami, questioni legali o mediche...) restituisce
 * la parola che l'ha fatto scattare, altrimenti null.
 *
 * Le parole stanno in `config('ai.assistant.handoff_keywords')`: ogni progetto puo' estenderle.
 */
final class DetectHandoffTriggerAction
{
    use QueueableAction;

    public function __construct(private readonly TokenizeTextAction $tokenize) {}

    public function execute(string $question): ?string
    {
        $tokens = $this->tokenize->execute($question);

        foreach ($this->keywords() as $keyword) {
            foreach ($tokens as $token) {
                if (str_starts_with($token, $keyword)) {
                    return $keyword;
                }
            }
        }

        return null;
    }

    /** @return list<string> radici gia' normalizzate (es. "rimbors" copre rimborso/rimborsi) */
    private function keywords(): array
    {
        $configured = config('ai.assistant.handoff_keywords', []);
        if (! is_array($configured)) {
            return [];
        }

        $keywords = [];
        foreach ($configured as $keyword) {
            if (is_string($keyword)) {
                $keywords = [...$keywords, ...$this->tokenize->execute($keyword)];
            }
        }

        return $keywords;
    }
}
