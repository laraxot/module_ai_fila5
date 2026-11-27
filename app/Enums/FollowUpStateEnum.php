<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

/**
 * Stato derivato (mai persistito) della cadenza di follow-up di un contatto.
 *
 * not_started: nessun invio ancora, il primo contatto lo decide una persona.
 * due:         il prossimo follow-up e' scaduto, va preparato.
 * waiting:     il prossimo follow-up non e' ancora scaduto.
 * stopped:     la cadenza e' interrotta (vedi `FollowUpDecisionData::$stopReason`).
 * completed:   tutti i follow-up della cadenza sono stati inviati.
 */
enum FollowUpStateEnum: string
{
    case NOT_STARTED = 'not_started';
    case DUE = 'due';
    case WAITING = 'waiting';
    case STOPPED = 'stopped';
    case COMPLETED = 'completed';
}
