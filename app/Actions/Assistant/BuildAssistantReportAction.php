<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Assistant;

use Carbon\CarbonInterface;
use Modules\AI\Datas\AssistantReportData;
use Modules\AI\Enums\AiMessageRoleEnum;
use Modules\AI\Models\AiMessage;
use Spatie\QueueableAction\QueueableAction;

/**
 * Report (es. settimanale) delle risposte dell'assistente per una `collection`.
 * Invio e pianificazione li decide il modulo del progetto (mail, notifica, widget...).
 */
final class BuildAssistantReportAction
{
    use QueueableAction;

    public function execute(string $collection, CarbonInterface $from, CarbonInterface $to): AssistantReportData
    {
        $payloads = AiMessage::query()
            ->where('role', AiMessageRoleEnum::ASSISTANT->value)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('payload')
            ->lazyById()
            ->map(static fn (AiMessage $message): mixed => $message->payload)
            ->filter(static fn (mixed $payload): bool => is_array($payload)
                && ($payload['kind'] ?? null) === AnswerQuestionAction::PAYLOAD_KIND
                && ($payload['collection'] ?? null) === $collection);

        /** @var iterable<array<string, mixed>> $payloads */
        return AssistantReportData::fromAnswerPayloads($payloads);
    }
}
