<?php

declare(strict_types=1);

namespace Modules\AI\Data\Prospect;

/** Domain-agnostic B2B prospect (ICP defined by host app). */
final readonly class ProspectLeadData
{
    /**
     * @param list<string> $signalKeys
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $name,
        public ?string $website = null,
        public ?string $contactName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $city = null,
        public ?string $country = null,
        public string $businessType = 'business',
        public ?string $sourceUrl = null,
        public int $score = 0,
        public ?string $why = null,
        public array $signalKeys = [],
        public array $meta = [],
        public bool $doNotContact = false,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        $email = isset($row['email']) ? strtolower(trim((string) $row['email'])) : null;
        if ($email === '') {
            $email = null;
        }

        return new self(
            name: trim((string) ($row['name'] ?? $row['company'] ?? '')),
            website: isset($row['website']) ? trim((string) $row['website']) : null,
            contactName: isset($row['who']) ? (string) $row['who'] : (isset($row['contact_name']) ? (string) $row['contact_name'] : null),
            email: $email,
            phone: isset($row['phone']) ? (string) $row['phone'] : null,
            city: isset($row['city']) ? (string) $row['city'] : null,
            country: isset($row['country']) ? (string) $row['country'] : null,
            businessType: (string) ($row['type'] ?? $row['business_type'] ?? 'business'),
            sourceUrl: isset($row['source_url']) ? (string) $row['source_url'] : null,
            score: (int) ($row['score'] ?? 0),
            why: isset($row['why']) ? (string) $row['why'] : null,
            signalKeys: array_values(array_map('strval', $row['signals'] ?? $row['gaps'] ?? $row['signal_keys'] ?? [])),
            meta: is_array($row['meta'] ?? null) ? $row['meta'] : [],
            doNotContact: (bool) ($row['do_not_contact'] ?? false),
        );
    }

    public function identityKey(): string
    {
        $host = $this->website !== null ? (parse_url($this->normalizeWebsite($this->website) ?? '', PHP_URL_HOST) ?: '') : '';
        $base = strtolower(trim($this->name.'|'.$host.'|'.($this->email ?? '')));

        return hash('sha256', $base);
    }

    private function normalizeWebsite(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'identity' => $this->identityKey(),
            'name' => $this->name,
            'website' => $this->website,
            'contact_name' => $this->contactName,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'country' => $this->country,
            'business_type' => $this->businessType,
            'source_url' => $this->sourceUrl,
            'score' => $this->score,
            'why' => $this->why,
            'signal_keys' => $this->signalKeys,
            'meta' => $this->meta,
            'do_not_contact' => $this->doNotContact,
        ];
    }
}
