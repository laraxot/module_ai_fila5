<?php

declare(strict_types=1);

namespace Modules\AI\Data\Prospect;

final readonly class WebsiteAuditResultData
{
    /** @param list<WebsiteSignalData> $signals */
    public function __construct(
        public string $url,
        public bool $ok,
        public array $signals,
        public ?string $title = null,
        public ?float $seconds = null,
        public ?string $error = null,
        public ?string $fetchedAt = null,
    ) {}

    public function totalScore(): int
    {
        $sum = 0;
        foreach ($this->signals as $signal) {
            $sum += $signal->score;
        }

        return $sum;
    }

    /** @return list<string> */
    public function signalKeys(): array
    {
        return array_map(static fn (WebsiteSignalData $s): string => $s->key, $this->signals);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'ok' => $this->ok,
            'title' => $this->title,
            'seconds' => $this->seconds,
            'error' => $this->error,
            'fetched_at' => $this->fetchedAt,
            'score' => $this->totalScore(),
            'signals' => array_map(static fn (WebsiteSignalData $s): array => $s->toArray(), $this->signals),
        ];
    }
}
