<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

/**
 * Fornitori con API compatibile OpenAI (`/chat/completions`) usati dalle azioni sulle immagini.
 *
 * La chiave di ciascuno sta in `.env` e si legge da `config/services.php`; qui ci sono solo le
 * differenze di protocollo che il codice deve conoscere.
 */
enum AiProviderEnum: string
{
    /** Instradatore verso molti modelli (Gemini, GPT, Claude, Qwen...): unico che modifica immagini. */
    case OpenRouter = 'openrouter';

    /** Inferenza veloce su modelli aperti; per le immagini un solo modello, in preview. */
    case Groq = 'groq';

    public function baseUrl(): string
    {
        return match ($this) {
            self::OpenRouter => 'https://openrouter.ai/api/v1',
            self::Groq => 'https://api.groq.com/openai/v1',
        };
    }

    /** Chiave di `config/services.php` che contiene la chiave API. */
    public function keyConfig(): string
    {
        return match ($this) {
            self::OpenRouter => 'services.openrouter.key',
            self::Groq => 'services.groq.key',
        };
    }

    /** Variabile `.env` da indicare nei messaggi di errore. */
    public function envName(): string
    {
        return match ($this) {
            self::OpenRouter => 'OPENROUTER_API_KEY',
            self::Groq => 'GROQ_API_KEY',
        };
    }

    /** Immagini massime in una richiesta (Groq: 3 per il modello qwen); null = nessun limite noto. */
    public function maxImages(): ?int
    {
        return match ($this) {
            self::OpenRouter => null,
            self::Groq => 3,
        };
    }

    /**
     * Campi aggiuntivi del payload: `provider.require_parameters` esiste solo su OpenRouter
     * (instrada solo verso chi rispetta lo schema); a Groq non va mandato.
     *
     * @return array<string, mixed>
     */
    public function extraPayload(): array
    {
        return match ($this) {
            self::OpenRouter => ['provider' => ['require_parameters' => true]],
            self::Groq => [],
        };
    }

    /** Intestazioni aggiuntive: OpenRouter riconosce l'app da HTTP-Referer e X-Title. */
    public function sendsAppHeaders(): bool
    {
        return $this === self::OpenRouter;
    }
}
