<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

use Modules\AI\Actions\Outreach\ClassifyReplyIntentAction;

/**
 * Intento di una risposta a un messaggio di outreach.
 *
 * Enum volutamente "puro": niente label/colori Filament (servirebbero file lang),
 * il layer UI li aggiunge quando serve.
 *
 * Politica per intento:
 *
 * | intento         | sopprime | ferma sequenza | serve persona | bozza di risposta |
 * |-----------------|----------|----------------|---------------|-------------------|
 * | unsubscribe     | si'      | si'            | no            | no                |
 * | escalate        | no       | si'            | si'           | no                |
 * | not_interested  | no       | si'            | no            | no                |
 * | out_of_office   | no       | no             | no            | no                |
 * | wrong_person    | no       | si'            | no            | si'               |
 * | interested      | no       | si'            | no            | si'               |
 * | objection       | no       | si'            | no            | si'               |
 * | question        | no       | si'            | no            | si'               |
 * | unclear         | no       | si'            | si'           | no                |
 *
 * Scelte non ovvie:
 *  - Solo l'auto-risposta (out_of_office) lascia proseguire la sequenza: non e' un
 *    "turno" della persona. `unclear` invece la ferma: qualcuno ha scritto qualcosa
 *    che non sappiamo leggere e un follow-up automatico sopra una risposta umana
 *    non letta e' il danno peggiore (fail-closed).
 *  - `unsubscribe` NON richiede una persona: e' una soppressione automatica e
 *    immediata. Se la stessa risposta contiene anche una minaccia, l'enum non puo'
 *    portare due valori: il segnale vive in
 *    {@see ClassifyReplyIntentAction::hasEscalationSignal()}.
 *  - `unsubscribe` ed `escalate` sono "bloccati da regola": un modello (futuro) puo'
 *    alzare la cautela, mai declassarli.
 */
enum ReplyIntentEnum: string
{
    case UNSUBSCRIBE = 'unsubscribe';
    case ESCALATE = 'escalate';
    case NOT_INTERESTED = 'not_interested';
    case OUT_OF_OFFICE = 'out_of_office';
    case WRONG_PERSON = 'wrong_person';
    case INTERESTED = 'interested';
    case OBJECTION = 'objection';
    case QUESTION = 'question';
    case UNCLEAR = 'unclear';

    /** Va aggiunto alla lista di soppressione (opt-out immediato e permanente). */
    public function requiresSuppression(): bool
    {
        return $this === self::UNSUBSCRIBE;
    }

    /** I follow-up automatici in coda vanno annullati. */
    public function stopsSequence(): bool
    {
        return $this !== self::OUT_OF_OFFICE;
    }

    /** Nessuna azione automatica: lo deve leggere una persona. */
    public function requiresHuman(): bool
    {
        return $this === self::ESCALATE || $this === self::UNCLEAR;
    }

    /** Si puo' preparare una bozza di risposta (che resta soggetta ad approvazione). */
    public function allowsDraftReply(): bool
    {
        return match ($this) {
            self::INTERESTED, self::OBJECTION, self::QUESTION, self::WRONG_PERSON => true,
            default => false,
        };
    }
}
