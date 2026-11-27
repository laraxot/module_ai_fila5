<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Modules\AI\Exceptions\AiRequestException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Unica porta verso i servizi pubblici di OpenStreetMap (Nominatim, Overpass).
 *
 * Rispetta le loro regole d'uso: si possono spegnere (`ai.prospecting.enabled`), User-Agent che identifica
 * l'applicazione, al massimo una richiesta al secondo per servizio, risposte in cache (una citta' cercata due
 * volte non rifa' la richiesta). https://operations.osmfoundation.org/policies/nominatim/
 */
final class OsmRequestAction
{
    use QueueableAction;

    /**
     * @param  array<string, scalar>  $query
     * @return array<mixed>
     */
    public function execute(string $service, string $url, array $query, int $cacheSeconds, string $method = 'get'): array
    {
        if (! config()->boolean('ai.prospecting.enabled', false)) {
            throw new AiRequestException('Ricerca clienti spenta (ai.prospecting.enabled = false).');
        }

        $cacheKey = 'ai:prospecting:'.$service.':'.sha1($url.'|'.$method.'|'.serialize($query));
        /** @var array<mixed>|null $cached */
        $cached = Cache::get($cacheKey);
        if (\is_array($cached)) {
            return $cached;
        }

        $this->throttle($service);

        $userAgent = config('ai.prospecting.user_agent');
        $request = Http::timeout(90)
            ->withHeaders(['User-Agent' => \is_string($userAgent) && $userAgent !== '' ? $userAgent : 'LaraxotProspecting/1.0'])
            ->acceptJson();
        $response = $method === 'post' ? $request->asForm()->post($url, $query) : $request->get($url, $query);

        if (! $response->successful()) {
            throw new AiRequestException(\sprintf('%s ha risposto %d.', $service, $response->status()), $response->status());
        }

        $data = $response->json();
        if (! \is_array($data)) {
            throw new AiRequestException($service.': risposta non valida.');
        }

        Cache::put($cacheKey, $data, $cacheSeconds);

        return $data;
    }

    /** Al massimo una richiesta al secondo per servizio, anche con piu' processi. */
    private function throttle(string $service): void
    {
        Cache::lock('ai:prospecting:lock:'.$service, 10)->block(10, function () use ($service): void {
            $key = 'ai:prospecting:last:'.$service;
            $last = Cache::get($key);
            $wait = \is_float($last) || \is_int($last) ? 1.0 - (microtime(true) - (float) $last) : 0.0;
            if ($wait > 0) {
                Sleep::usleep((int) ceil($wait * 1_000_000));
            }
            Cache::put($key, microtime(true), 60);
        });
    }
}
