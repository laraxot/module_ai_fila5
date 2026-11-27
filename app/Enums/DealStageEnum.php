<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

/**
 * Stadi del deal nel ciclo di vita dell'outreach B2B.
 *
 * Imbuto: lead -> contacted -> replied -> call_booked -> proposal -> contract_sent -> won.
 * `lost` e' l'altro stadio terminale (fuori dall'imbuto).
 *
 * ponytail: niente HasLabel/HasColor/HasIcon (servirebbero chiavi lang), aggiungili quando una UI mostra gli stadi.
 */
enum DealStageEnum: string
{
    case LEAD = 'lead';
    case CONTACTED = 'contacted';
    case REPLIED = 'replied';
    case CALL_BOOKED = 'call_booked';
    case PROPOSAL = 'proposal';
    case CONTRACT_SENT = 'contract_sent';
    case WON = 'won';
    case LOST = 'lost';

    /** Probabilita' di chiusura a vittoria, calibrata su un win rate storico del 25%. */
    public function probability(): float
    {
        return match ($this) {
            self::LEAD => 0.05,
            self::CONTACTED => 0.10,
            self::REPLIED => 0.20,
            self::CALL_BOOKED => 0.30,
            self::PROPOSAL => 0.50,
            self::CONTRACT_SENT => 0.80,
            self::WON => 1.0,
            self::LOST => 0.0,
        };
    }

    /** Aperto = ancora in trattativa (tutto tranne won e lost). */
    public function isOpen(): bool
    {
        return $this !== self::WON && $this !== self::LOST;
    }

    /** Stadi che solo una persona puo' impostare: l'automazione non li raggiunge mai. */
    public function isHumanOnly(): bool
    {
        return $this === self::PROPOSAL || $this === self::CONTRACT_SENT || $this === self::WON;
    }

    /**
     * Ordine di avanzamento, 0 (lead) .. 7 (lost).
     * `lost` e' l'ultimo solo come marcatore terminale: non e' "piu' avanti" di won in senso commerciale.
     */
    public function rank(): int
    {
        return match ($this) {
            self::LEAD => 0,
            self::CONTACTED => 1,
            self::REPLIED => 2,
            self::CALL_BOOKED => 3,
            self::PROPOSAL => 4,
            self::CONTRACT_SENT => 5,
            self::WON => 6,
            self::LOST => 7,
        };
    }
}
