<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\AI\Actions\Prompt\BuildTicketClassificationPromptAction;
use Modules\AI\Actions\Support\MakeAIRequestAction;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

class ClassifyTicketAction
{
    use QueueableAction;

    public function __construct(
        private readonly ?string $title = null,
        private readonly ?string $description = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(?string $title = null, ?string $description = null): array
    {
        $title = $title ?? $this->title ?? '';
        $description = $description ?? $this->description ?? '';
        $cacheKey = 'ai:classification:'.md5($title.$description);

        $result = Cache::remember($cacheKey, 3600, function () use ($title, $description): string {
            $prompt = app(BuildTicketClassificationPromptAction::class)->execute($title, $description);

            return app(MakeAIRequestAction::class, [
                'prompt' => $prompt,
                'type' => 'classification',
            ])->execute();
        });

        Assert::string($result, 'Classification result must be a JSON string');

        return app(AiJsonResponseDecoderAction::class)->execute($result);
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return $this->execute();
    }
}
