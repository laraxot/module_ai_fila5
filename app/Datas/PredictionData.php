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
        public string $closed_at,
        public string $ends_at,
        public float $liquidity_parameter,
        public int $stocks_count,
        public bool $is_wagerable,
        public ?string $content_block = null,
        public ?string $sidebar_block = null,
        public ?string $footer_block = null,
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
            'closed_at' => $this->closed_at,
            'ends_at' => $this->ends_at,
            'liquidity_parameter' => $this->liquidity_parameter,
            'stocks_count' => $this->stocks_count,
            'is_wagerable' => $this->is_wagerable,
            'status' => 'published',
            'published_at' => now(),
            'content_blocks' => $this->content_block ? (array) json_decode($this->content_block, true) : [],
            'sidebar_blocks' => $this->sidebar_block ? (array) json_decode($this->sidebar_block, true) : [],
            'footer_blocks' => $this->footer_block ? (array) json_decode($this->footer_block, true) : [],
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
        $tagsArray = is_array($tags) ? array_values(array_map(static fn (mixed $v): string => (string) $v, $tags)) : [];

        return new self(
            title: (string) ($data['title'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            content: (string) ($data['content'] ?? ''),
            excerpt: (string) ($data['excerpt'] ?? $data['description'] ?? ''),
            category: (string) ($data['category'] ?? 'Generico'),
            tags: $tagsArray,
            closed_at: (string) ($data['closed_at'] ?? now()->addDays(30)->format('Y-m-d')),
            ends_at: (string) ($data['ends_at'] ?? now()->addDays(60)->format('Y-m-d')),
            liquidity_parameter: (float) ($data['liquidity_parameter'] ?? 0.5),
            stocks_count: (int) ($data['stocks_count'] ?? 1000),
            is_wagerable: (bool) ($data['is_wagerable'] ?? true),
            content_block: isset($data['content_block']) ? (string) $data['content_block'] : null,
            sidebar_block: isset($data['sidebar_block']) ? (string) $data['sidebar_block'] : null,
            footer_block: isset($data['footer_block']) ? (string) $data['footer_block'] : null,
        );
    }
}
