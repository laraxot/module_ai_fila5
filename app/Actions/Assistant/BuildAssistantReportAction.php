<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Assistant;

use Carbon\CarbonInterface;
use Illuminate\Support\LazyCollection;
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
        // Filtro nel database (percorsi JSON: MySQL, MariaDB, PostgreSQL, SQLite), non in PHP:
        // il report di una attivita' non deve leggere i messaggi di tutte le altre.
        // I payload restano in streaming (`lazyById`): non si materializza l'intero periodo in memoria.
        /** @var LazyCollection<int, array<string, mixed>> $payloads */
        $payloads = AiMessage::query()
            ->where('role', AiMessageRoleEnum::ASSISTANT->value)
            ->whereBetween('created_at', [$from, $to])
            ->where('payload->kind', AnswerQuestionAction::PAYLOAD_KIND)
            ->where('payload->collection', $collection)
            ->lazyById()
            ->map(static fn (AiMessage $message): ?array => $message->payload)
            ->filter(static fn (?array $payload): bool => $payload !== null);

        return AssistantReportData::fromAnswerPayloads($payloads);
    }
}
