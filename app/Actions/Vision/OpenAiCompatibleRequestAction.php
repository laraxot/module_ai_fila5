<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Vision;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\AI\Exceptions\AiRequestException;

/**
 * POST JSON verso un'API compatibile OpenAI (OpenRouter di default) con la chiave da `.env`.
 * Le intestazioni HTTP-Referer e X-Title identificano l'app nelle statistiche di OpenRouter.
 */
class OpenAiCompatibleRequestAction
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws AiRequestException
     */
    public function execute(string $url, array $payload, int $timeout): array
    {
        $key = config('services.openrouter.key');
        if (! is_string($key) || $key === '') {
            throw new AiRequestException('Chiave API mancante: imposta OPENROUTER_API_KEY (services.openrouter.key).');
        }

        try {
            $response = Http::timeout($timeout)
                ->withToken($key)
                ->withHeaders([
                    'HTTP-Referer' => $this->configString('app.url'),
                    'X-Title' => $this->configString('app.name'),
                ])
                ->acceptJson()
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new AiRequestException('Servizio AI non raggiungibile.', previous: $e);
        }

        if (! $response->successful()) {
            $message = data_get($response->json(), 'error.message');
            throw new AiRequestException('Servizio AI: errore HTTP '.$response->status().(is_string($message) ? ' ('.$message.')' : ''));
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new AiRequestException('Servizio AI: risposta non JSON.');
        }

        /** @var array<string, mixed> $json */
        return $json;
    }

    private function configString(string $key): string
    {
        $value = config($key);

        return is_string($value) ? $value : '';
    }
}
