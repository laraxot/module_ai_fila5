<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\AI\Actions\Support\MakeAIRequestAction;
use Modules\AI\Services\AIServicePromptBuilder;
use Spatie\QueueableAction\QueueableAction;

use function Safe\json_decode;

class ClassifyTicketAction
{
    use QueueableAction;

    public function __construct(
        private readonly string $title,
        private readonly string $description,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $cacheKey = 'ai:classification:'.md5($this->title.$this->description);

        $result = Cache::remember($cacheKey, 3600, function (): string {
            $prompt = AIServicePromptBuilder::classification($this->title, $this->description);

            return app(MakeAIRequestAction::class, [
                'prompt' => $prompt,
                'type' => 'classification',
            ])->handle();
        });

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($result, true);

        return $decoded;
    }
}
