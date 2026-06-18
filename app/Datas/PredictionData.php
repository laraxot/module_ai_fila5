<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Spatie\LaravelData\Data;

use function Safe\json_decode;

/**
 * Data Transfer Object for AI-generated prediction data.
 */
class PredictionData extends Data
{
    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $content,
        public string $excerpt,
        public string $category,
        public array $tags,
        public string $closedAt,
        public string $endsAt,
        public float $liquidityParameter,
        public int $stocksCount,
        public bool $isWagerable,
        public ?string $contentBlock = null,
        public ?string $sidebarBlock = null,
        public ?string $footerBlock = null,
    ) {}

    /**
     * Convert to array for Predict model.
     *
     * @return array<string, mixed>
     */
    public function toPredictArray(): array
    {
        return [
            'title' => ['it' => $this->title],
            'description' => $this->description,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'category_name' => $this->category,
            'tags' => $this->tags,
            'closed_at' => $this->closedAt,
            'ends_at' => $this->endsAt,
            'liquidity_parameter' => $this->liquidityParameter,
            'stocks_count' => $this->stocksCount,
            'is_wagerable' => $this->isWagerable,
            'status' => 'published',
            'published_at' => now(),
            'content_blocks' => $this->contentBlock ? (array) json_decode($this->contentBlock, true) : [],
            'sidebar_blocks' => $this->sidebarBlock ? (array) json_decode($this->sidebarBlock, true) : [],
            'footer_blocks' => $this->footerBlock ? (array) json_decode($this->footerBlock, true) : [],
        ];
    }

    /**
     * Create from OpenAI response.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromOpenAIResponse(array $data): self
    {
        $tags = $data['tags'] ?? [];
        /** @var array<int, string> $tagsArray */
        $tagsArray = is_array($tags)
            ? array_values(
                array_map(
                    static fn (mixed $v): string => is_scalar($v) ? (string) $v : '',
                    $tags
                )
            )
            : [];

        return new self(
            title: self::toStringValue($data, 'title'),
            description: self::toStringValue($data, 'description'),
            content: self::toStringValue($data, 'content'),
            excerpt: self::toStringValue($data, 'excerpt', self::toStringValue($data, 'description')),
            category: self::toStringValue($data, 'category', 'Generico'),
            tags: $tagsArray,
            closedAt: self::toStringValue($data, 'closed_at', now()->addDays(30)->format('Y-m-d')),
            endsAt: self::toStringValue($data, 'ends_at', now()->addDays(60)->format('Y-m-d')),
            liquidityParameter: self::toFloatValue($data, 'liquidity_parameter', 0.5),
            stocksCount: self::toIntValue($data, 'stocks_count', 1000),
            isWagerable: self::toBoolValue($data, 'is_wagerable', true),
            contentBlock: self::toNullableStringValue($data, 'content_block'),
            sidebarBlock: self::toNullableStringValue($data, 'sidebar_block'),
            footerBlock: self::toNullableStringValue($data, 'footer_block'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function toStringValue(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function toNullableStringValue(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        return is_scalar($data[$key]) ? (string) $data[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function toFloatValue(array $data, string $key, float $default): float
    {
        $value = $data[$key] ?? $default;

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function toIntValue(array $data, string $key, int $default): int
    {
        $value = $data[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function toBoolValue(array $data, string $key, bool $default): bool
    {
        $value = $data[$key] ?? $default;

        return is_bool($value) ? $value : $default;
    }
}
