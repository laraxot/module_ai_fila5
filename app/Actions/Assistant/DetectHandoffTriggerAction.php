<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Assistant;

use Spatie\QueueableAction\QueueableAction;

/**
 * Guardrail deterministico, prima di chiamare il modello: se la domanda tocca un argomento
 * che deve gestire una persona (rimborsi, reclami, questioni legali o mediche...) restituisce
 * la parola che l'ha fatto scattare, altrimenti null.
 *
<<<<<<< .merge_file_cHhl4l
 * Le parole stanno in `config('ai.assistant.handoff_keywords')`: ogni progetto puo' estenderle.
=======
 * Le parole arrivano dal chiamante (es. per singola attivita'); se null si usa
 * `config('ai.assistant.handoff_keywords')`.
>>>>>>> .merge_file_BHYxaA
 */
final class DetectHandoffTriggerAction
{
    use QueueableAction;

    public function __construct(private readonly TokenizeTextAction $tokenize) {}

<<<<<<< .merge_file_cHhl4l
    public function execute(string $question): ?string
    {
        $tokens = $this->tokenize->execute($question);

        foreach ($this->keywords() as $keyword) {
=======
    /** @param  list<string>|null  $keywords  null = parole della config */
    public function execute(string $question, ?array $keywords = null): ?string
    {
        $tokens = $this->tokenize->execute($question);

        foreach ($this->normalize($keywords ?? $this->configuredKeywords()) as $keyword) {
>>>>>>> .merge_file_BHYxaA
            foreach ($tokens as $token) {
                if (str_starts_with($token, $keyword)) {
                    return $keyword;
                }
            }
        }

        return null;
    }

<<<<<<< .merge_file_cHhl4l
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
=======
    /** @return array<mixed> */
    private function configuredKeywords(): array
    {
        $configured = config('ai.assistant.handoff_keywords', []);

        return is_array($configured) ? $configured : [];
    }

    /**
     * @param  array<mixed>  $keywords
     * @return list<string> radici normalizzate (es. "rimbors" copre rimborso/rimborsi)
     */
    private function normalize(array $keywords): array
    {
        $normalized = [];
        foreach ($keywords as $keyword) {
            if (is_string($keyword)) {
                $normalized = [...$normalized, ...$this->tokenize->execute($keyword)];
            }
        }

        return $normalized;
>>>>>>> .merge_file_BHYxaA
    }
}
