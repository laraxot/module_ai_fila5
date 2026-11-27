<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Outreach;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Modules\AI\Datas\FollowUpDecisionData;
use Modules\AI\Enums\FollowUpStateEnum;
use Spatie\QueueableAction\QueueableAction;

/**
 * Decide cosa fare del prossimo follow-up di un contatto. Pura: niente DB, niente invii, niente orologio
 * implicito (passare `$now` nei test).
 *
 * Regole:
 *  - Stop espliciti (suppressed > replied > meeting_booked) valgono sempre, anche senza invii.
 *  - Nessun invio => not_started (il primo contatto lo decide una persona).
 *  - Follow-up inviati = invii - 1 (il primo invio e' l'apertura). Piu' follow-up della cadenza => stop
 *    max_attempts; esattamente quanti la cadenza => completed (anche oltre `$maxAgeDays`).
 *  - `$now` oltre `$maxAgeDays` giorni dalla PRIMA email con un follow-up ancora da fare => stop max_age
 *    (il limite e' escluso: a +30 giorni esatti si e' ancora in tempo).
 *  - Altrimenti scadenza = ultimo invio reale + `$cadenceDays[follow-up gia' inviati]` giorni di calendario nel
 *    fuso `$timezone` (stessa ora a muro: l'ora legale non sposta la scadenza), spostata al lunedi' se cade
 *    di sabato o domenica. `$now >= dueAt` => due, altrimenti waiting.
 *
 * Input:
 *  - `$sentAt`: DateTimeInterface o stringhe; le stringhe senza fuso si leggono in `$timezone`; una stringa
 *    non valida lancia InvalidArgumentException. Ordine libero, duplicati dello stesso istante ignorati.
 *  - Un invio nel futuro rispetto a `$now` conta come gia' inviato (clock skew o invio programmato): il
 *    risultato e' waiting con scadenza calcolata da quell'invio, mai un errore.
 *  - Gap della cadenza <= 0 valgono 1 giorno; le chiavi dell'array sono ignorate; `$maxAgeDays` < 0 vale 0.
 *
 * @see docs/adr/0004-phpstan-zero-errori.md — generics `list<>`/`array-shape` nei `@param`/`@return` e
 *      narrowing `instanceof DateTimeInterface` in `normalize()` per gestire il tipo unione senza cast
 */
final class NextFollowUpAction
{
    use QueueableAction;

    /**
     * @param  array<array-key, DateTimeInterface|string>  $sentAt  invii gia' fatti (apertura + follow-up)
     * @param  array<array-key, int>  $cadenceDays  giorni di attesa prima di ciascun follow-up
     */
    public function execute(
        array $sentAt,
        ?CarbonImmutable $now = null,
        bool $replied = false,
        bool $suppressed = false,
        bool $meetingBooked = false,
        array $cadenceDays = [2, 2, 4],
        int $maxAgeDays = 30,
        string $timezone = 'Europe/Rome',
    ): FollowUpDecisionData {
        $now ??= CarbonImmutable::now();

        $explicitStop = $this->explicitStop($suppressed, $replied, $meetingBooked);
        if ($explicitStop !== null) {
            return FollowUpDecisionData::stopped($explicitStop);
        }

        $sends = $this->normalize($sentAt, $timezone);
        if ($sends === []) {
            return new FollowUpDecisionData(FollowUpStateEnum::NOT_STARTED);
        }

        $gaps = array_values($cadenceDays);
        $followUpsSent = count($sends) - 1;

        if ($followUpsSent > count($gaps)) {
            return FollowUpDecisionData::stopped(FollowUpDecisionData::STOP_MAX_ATTEMPTS);
        }

        if ($followUpsSent === count($gaps)) {
            return new FollowUpDecisionData(FollowUpStateEnum::COMPLETED);
        }

        if ($now > $sends[0]->addDays(max(0, $maxAgeDays))) {
            return FollowUpDecisionData::stopped(FollowUpDecisionData::STOP_MAX_AGE);
        }

        $dueAt = $this->dueAt($sends[$followUpsSent], $gaps[$followUpsSent]);

        return new FollowUpDecisionData(
            state: $now >= $dueAt ? FollowUpStateEnum::DUE : FollowUpStateEnum::WAITING,
            step: $followUpsSent + 1,
            dueAt: $dueAt,
        );
    }

    private function explicitStop(bool $suppressed, bool $replied, bool $meetingBooked): ?string
    {
        return match (true) {
            $suppressed => FollowUpDecisionData::STOP_SUPPRESSED,
            $replied => FollowUpDecisionData::STOP_REPLIED,
            $meetingBooked => FollowUpDecisionData::STOP_MEETING_BOOKED,
            default => null,
        };
    }

    /**
     * Invii nel fuso richiesto, dal piu' vecchio, senza duplicati dello stesso istante.
     *
     * @param  array<array-key, DateTimeInterface|string>  $sentAt
     * @return list<CarbonImmutable>
     */
    private function normalize(array $sentAt, string $timezone): array
    {
        $byInstant = [];

        foreach ($sentAt as $value) {
            $moment = $value instanceof DateTimeInterface
                ? CarbonImmutable::instance($value)
                : CarbonImmutable::parse($value, $timezone);

            $byInstant[$moment->format('U.u')] = $moment->setTimezone($timezone);
        }

        $sends = array_values($byInstant);
        usort($sends, static fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        return $sends;
    }

    /** Ultimo invio + gap giorni di calendario (ora a muro), poi fuori dal weekend. */
    private function dueAt(CarbonImmutable $lastSent, int $gapDays): CarbonImmutable
    {
        $due = $lastSent->addDays(max(1, $gapDays));

        return match (true) {
            $due->isSaturday() => $due->addDays(2),
            $due->isSunday() => $due->addDay(),
            default => $due,
        };
    }
}
