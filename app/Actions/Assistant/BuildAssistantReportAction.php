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
        // Filtro nel database (percorsi JSON: MySQL, MariaDB, PostgreSQL, SQLite), non in PHP:
        // il report di una attivita' non deve leggere i messaggi di tutte le altre.
<<<<<<< HEAD
        $payloads = AiMessage::query()
=======
        /** @var \Illuminate\Support\LazyCollection<int, AiMessage> $messages */
        $messages = AiMessage::query()
>>>>>>> laraxot/dev
            ->where('role', AiMessageRoleEnum::ASSISTANT->value)
            ->whereBetween('created_at', [$from, $to])
            ->where('payload->kind', AnswerQuestionAction::PAYLOAD_KIND)
            ->where('payload->collection', $collection)
<<<<<<< HEAD
            ->lazyById()
            ->map(static fn (AiMessage $message): mixed => $message->payload)
            ->filter(static fn (mixed $payload): bool => is_array($payload));

        /** @var iterable<array<string, mixed>> $payloads */
=======
            ->lazyById();

        /** @var list<array<string, mixed>> $payloads */
        $payloads = $messages
            ->map(static fn (AiMessage $message): mixed => $message->payload)
            ->filter(static fn (mixed $payload): bool => is_array($payload))
            ->values()
            ->all();

>>>>>>> laraxot/dev
        return AssistantReportData::fromAnswerPayloads($payloads);
    }
}
