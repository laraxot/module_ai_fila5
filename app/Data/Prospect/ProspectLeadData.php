<?php

declare(strict_types=1);

namespace Modules\AI\Data\Prospect;

use function Safe\parse_url;
use function Safe\preg_match;

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
        $email = self::stringValue($row['email'] ?? null);
        $email = $email !== null ? strtolower(trim($email)) : null;
        if ($email === '') {
            $email = null;
        }

        return new self(
            name: trim(self::stringValue($row['name'] ?? $row['company'] ?? '') ?? ''),
            website: self::stringValue($row['website'] ?? null),
            contactName: self::stringValue($row['who'] ?? $row['contact_name'] ?? null),
            email: $email,
            phone: self::stringValue($row['phone'] ?? null),
            city: self::stringValue($row['city'] ?? null),
            country: self::stringValue($row['country'] ?? null),
            businessType: self::stringValue($row['type'] ?? $row['business_type'] ?? 'business') ?? 'business',
            sourceUrl: self::stringValue($row['source_url'] ?? null),
            score: self::intValue($row['score'] ?? 0),
            why: self::stringValue($row['why'] ?? null),
            signalKeys: self::stringList($row['signals'] ?? $row['gaps'] ?? $row['signal_keys'] ?? []),
            meta: self::metaOrEmpty($row['meta'] ?? null),
            doNotContact: self::boolValue($row['do_not_contact'] ?? false),
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

        return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
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
    private static function stringValue(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private static function intValue(mixed $value): int
    {
        return is_int($value) ? $value : 0;
    }

    private static function boolValue(mixed $value): bool
    {
        return is_bool($value) ? $value : false;
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $result[] = is_scalar($item) ? (string) $item : '';
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private static function metaOrEmpty(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            $result[(string) $key] = $item;
        }

        return $result;
    }
}
