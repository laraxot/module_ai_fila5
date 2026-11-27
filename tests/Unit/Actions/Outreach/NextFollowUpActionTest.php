<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions\Outreach;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Modules\AI\Actions\Outreach\NextFollowUpAction;
use Modules\AI\Datas\FollowUpDecisionData;
use Modules\AI\Enums\FollowUpStateEnum;
use Modules\AI\Tests\TestCase;
use Safe\DateTimeImmutable;

uses(TestCase::class);

const ROME_FORMAT = 'Y-m-d H:i T';

/** Istante nel fuso Europe/Rome (2026-11-27 e' un venerdi'). */
function rome(string $at): CarbonImmutable
{
    return CarbonImmutable::parse($at, 'Europe/Rome');
}

/**
 * @param  array<array-key, DateTimeInterface|string>  $sentAt
 * @param  array<array-key, int>  $cadenceDays
 */
function decide(
    array $sentAt,
    ?CarbonImmutable $now = null,
    bool $replied = false,
    bool $suppressed = false,
    bool $meetingBooked = false,
    array $cadenceDays = [2, 2, 4],
    int $maxAgeDays = 30,
    string $timezone = 'Europe/Rome',
): FollowUpDecisionData {
    return app(NextFollowUpAction::class)->execute(
        sentAt: $sentAt,
        now: $now,
        replied: $replied,
        suppressed: $suppressed,
        meetingBooked: $meetingBooked,
        cadenceDays: $cadenceDays,
        maxAgeDays: $maxAgeDays,
        timezone: $timezone,
    );
}

// ---------------------------------------------------------------------------
// Contratto pubblico
// ---------------------------------------------------------------------------

it('exposes stable state codes', function (): void {
    expect(array_map(static fn (FollowUpStateEnum $s): string => $s->value, FollowUpStateEnum::cases()))
        ->toBe(['not_started', 'due', 'waiting', 'stopped', 'completed']);
});

// ---------------------------------------------------------------------------
// Regola 1: lista vuota
// ---------------------------------------------------------------------------

it('returns not_started when nothing was sent yet', function (): void {
    $decision = decide([], now: rome('2026-11-27 10:00'));

    expect($decision->state)->toBe(FollowUpStateEnum::NOT_STARTED)
        ->and($decision->step)->toBeNull()
        ->and($decision->dueAt)->toBeNull()
        ->and($decision->stopReason)->toBeNull();
});

it('keeps a suppressed contact stopped even if nothing was sent yet', function (): void {
    $decision = decide([], now: rome('2026-11-27 10:00'), suppressed: true);

    expect($decision->state)->toBe(FollowUpStateEnum::STOPPED)
        ->and($decision->stopReason)->toBe('suppressed');
});

// ---------------------------------------------------------------------------
// Regola 3: scadenza, weekend, due/waiting
// ---------------------------------------------------------------------------

it('moves a Sunday deadline to Monday when the opening was sent on Friday', function (): void {
    $decision = decide([rome('2026-11-27 10:00')], now: rome('2026-11-27 12:00'));

    expect($decision->state)->toBe(FollowUpStateEnum::WAITING)
        ->and($decision->step)->toBe(1)
        ->and($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-11-30 10:00 CET')
        ->and($decision->stopReason)->toBeNull();
});

it('moves a Saturday deadline to Monday when the opening was sent on Thursday', function (): void {
    $decision = decide([rome('2026-11-26 10:00')], now: rome('2026-11-26 12:00'));

    expect($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-11-30 10:00 CET');
});

it('computes the deadline as last send plus the cadence gap, shifted off weekends', function (string $sentAt, string $expectedDueAt): void {
    $decision = decide([rome($sentAt)], now: rome($sentAt), cadenceDays: [2]);

    expect($decision->dueAt?->format(ROME_FORMAT))->toBe($expectedDueAt);
})->with([
    'monday -> wednesday' => ['2026-11-23 10:00', '2026-11-25 10:00 CET'],
    'tuesday -> thursday' => ['2026-11-24 10:00', '2026-11-26 10:00 CET'],
    'wednesday -> friday' => ['2026-11-25 10:00', '2026-11-27 10:00 CET'],
    'thursday -> saturday -> monday' => ['2026-11-26 10:00', '2026-11-30 10:00 CET'],
    'friday -> sunday -> monday' => ['2026-11-27 10:00', '2026-11-30 10:00 CET'],
    'saturday -> monday' => ['2026-11-28 10:00', '2026-11-30 10:00 CET'],
    'sunday -> tuesday' => ['2026-11-29 10:00', '2026-12-01 10:00 CET'],
]);

it('is due exactly at the deadline and waiting one second before', function (): void {
    $opening = rome('2026-11-23 10:00'); // scadenza: mer 2026-11-25 10:00

    $before = decide([$opening], now: rome('2026-11-25 09:59:59'));
    $at = decide([$opening], now: rome('2026-11-25 10:00:00'));
    $after = decide([$opening], now: rome('2026-11-27 18:00'));

    expect($before->state)->toBe(FollowUpStateEnum::WAITING)
        ->and($at->state)->toBe(FollowUpStateEnum::DUE)
        ->and($at->step)->toBe(1)
        ->and($after->state)->toBe(FollowUpStateEnum::DUE);
});

it('walks the whole default cadence step by step', function (): void {
    $opening = rome('2026-11-23 10:00');            // lun
    $followUp1 = rome('2026-11-25 10:30');          // mer (+2 dalla apertura)
    $followUp2 = rome('2026-11-27 11:00');          // ven (+2 dal FU1)

    $second = decide([$opening, $followUp1], now: rome('2026-11-26 09:00'));
    $third = decide([$opening, $followUp1, $followUp2], now: rome('2026-11-27 12:00'));

    expect($second->state)->toBe(FollowUpStateEnum::WAITING)
        ->and($second->step)->toBe(2)
        ->and($second->dueAt?->format(ROME_FORMAT))->toBe('2026-11-27 10:30 CET')
        // ven + 4 giorni = martedi' 2026-12-01 (mai weekend)
        ->and($third->state)->toBe(FollowUpStateEnum::WAITING)
        ->and($third->step)->toBe(3)
        ->and($third->dueAt?->format(ROME_FORMAT))->toBe('2026-12-01 11:00 CET');
});

it('measures the gap from the last real send, not from the planned deadline', function (): void {
    // FU1 pianificato per mer 25, inviato in ritardo ven 27: FU2 = 27 + 2 = dom 29 -> lun 30
    $decision = decide(
        [rome('2026-11-23 10:00'), rome('2026-11-27 10:00')],
        now: rome('2026-11-27 11:00'),
    );

    expect($decision->step)->toBe(2)
        ->and($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-11-30 10:00 CET');
});

// ---------------------------------------------------------------------------
// Regola 4: completamento
// ---------------------------------------------------------------------------

it('completes the sequence when every follow-up of the cadence was sent', function (): void {
    $sent = [
        rome('2026-11-02 10:00'),
        rome('2026-11-04 10:00'),
        rome('2026-11-06 10:00'),
        rome('2026-11-10 10:00'),
    ];

    $decision = decide($sent, now: rome('2026-11-11 10:00'));

    expect($decision->state)->toBe(FollowUpStateEnum::COMPLETED)
        ->and($decision->step)->toBeNull()
        ->and($decision->dueAt)->toBeNull()
        ->and($decision->stopReason)->toBeNull();
});

it('stays completed (not max_age) when the sequence is over even after the age limit', function (): void {
    $sent = [
        rome('2026-09-01 10:00'),
        rome('2026-09-03 10:00'),
        rome('2026-09-07 10:00'),
        rome('2026-09-09 10:00'),
    ];

    $decision = decide($sent, now: rome('2026-11-27 10:00'));

    expect($decision->state)->toBe(FollowUpStateEnum::COMPLETED)
        ->and($decision->stopReason)->toBeNull();
});

it('treats an empty cadence as completed right after the opening', function (): void {
    $decision = decide([rome('2026-11-27 10:00')], now: rome('2026-11-27 10:05'), cadenceDays: []);

    expect($decision->state)->toBe(FollowUpStateEnum::COMPLETED)
        ->and($decision->dueAt)->toBeNull();
});

it('stops with max_attempts when more follow-ups were sent than the cadence allows', function (): void {
    $sent = [
        rome('2026-11-02 10:00'),
        rome('2026-11-04 10:00'),
        rome('2026-11-06 10:00'),
    ];

    $decision = decide($sent, now: rome('2026-11-09 10:00'), cadenceDays: [2]);

    expect($decision->state)->toBe(FollowUpStateEnum::STOPPED)
        ->and($decision->stopReason)->toBe('max_attempts')
        ->and($decision->dueAt)->toBeNull()
        ->and($decision->step)->toBeNull();
});

// ---------------------------------------------------------------------------
// Regola 5: max_age
// ---------------------------------------------------------------------------

it('stops with max_age when the first email is older than the limit and a follow-up is still pending', function (): void {
    $decision = decide([rome('2026-10-01 10:00')], now: rome('2026-11-27 10:00'));

    expect($decision->state)->toBe(FollowUpStateEnum::STOPPED)
        ->and($decision->stopReason)->toBe('max_age')
        ->and($decision->dueAt)->toBeNull();
});

it('applies max_age to the first email, not to the last one', function (): void {
    // ultimo invio recente (FU1 ieri), ma la prima email ha 40 giorni
    $decision = decide(
        [rome('2026-10-18 10:00'), rome('2026-11-26 10:00')],
        now: rome('2026-11-27 10:00'),
    );

    expect($decision->stopReason)->toBe('max_age');
});

it('treats max_age as strictly beyond the limit', function (): void {
    $opening = rome('2026-11-02 10:00'); // +30 giorni = 2026-12-02 10:00

    $atLimit = decide([$opening], now: rome('2026-12-02 10:00'));
    $pastLimit = decide([$opening], now: rome('2026-12-02 10:00:01'));

    expect($atLimit->state)->toBe(FollowUpStateEnum::DUE)
        ->and($pastLimit->state)->toBe(FollowUpStateEnum::STOPPED)
        ->and($pastLimit->stopReason)->toBe('max_age');
});

it('honours a custom max age', function (): void {
    $opening = rome('2026-11-20 10:00');

    $decision = decide([$opening], now: rome('2026-11-27 10:00'), maxAgeDays: 5);

    expect($decision->stopReason)->toBe('max_age');
});

// ---------------------------------------------------------------------------
// Regola 2: stop e precedenze
// ---------------------------------------------------------------------------

it('stops for every explicit cause and never returns due', function (bool $suppressed, bool $replied, bool $meetingBooked, string $reason): void {
    // follow-up scaduto da giorni: senza stop sarebbe `due`
    $decision = decide(
        [rome('2026-11-16 10:00')],
        now: rome('2026-11-27 10:00'),
        replied: $replied,
        suppressed: $suppressed,
        meetingBooked: $meetingBooked,
    );

    expect($decision->state)->toBe(FollowUpStateEnum::STOPPED)
        ->and($decision->stopReason)->toBe($reason)
        ->and($decision->step)->toBeNull()
        ->and($decision->dueAt)->toBeNull();
})->with([
    'suppressed' => [true, false, false, 'suppressed'],
    'replied' => [false, true, false, 'replied'],
    'meeting booked' => [false, false, true, 'meeting_booked'],
]);

it('stops with max_attempts and max_age as implicit causes', function (): void {
    $overSent = decide(
        [rome('2026-11-16 10:00'), rome('2026-11-17 10:00')],
        now: rome('2026-11-18 10:00'),
        cadenceDays: [],
    );
    $tooOld = decide([rome('2026-09-01 10:00')], now: rome('2026-11-27 10:00'));

    expect($overSent->stopReason)->toBe('max_attempts')
        ->and($tooOld->stopReason)->toBe('max_age');
});

it('applies the stop precedence suppressed > replied > meeting_booked > max_attempts > max_age', function (): void {
    // 3 invii con cadenza [2] = max_attempts; prima email di 80 giorni = max_age
    $sent = [rome('2026-09-01 10:00'), rome('2026-09-03 10:00'), rome('2026-09-05 10:00')];
    $now = rome('2026-11-27 10:00');

    $all = decide($sent, $now, replied: true, suppressed: true, meetingBooked: true, cadenceDays: [2]);
    $repliedAndBooked = decide($sent, $now, replied: true, meetingBooked: true, cadenceDays: [2]);
    $bookedAndAttempts = decide($sent, $now, meetingBooked: true, cadenceDays: [2]);
    $attemptsAndAge = decide($sent, $now, cadenceDays: [2]);

    expect($all->stopReason)->toBe('suppressed')
        ->and($repliedAndBooked->stopReason)->toBe('replied')
        ->and($bookedAndAttempts->stopReason)->toBe('meeting_booked')
        ->and($attemptsAndAge->stopReason)->toBe('max_attempts');
});

it('lets an explicit stop win over a completed sequence', function (): void {
    $sent = [
        rome('2026-11-02 10:00'),
        rome('2026-11-04 10:00'),
        rome('2026-11-06 10:00'),
        rome('2026-11-10 10:00'),
    ];

    $decision = decide($sent, now: rome('2026-11-11 10:00'), replied: true);

    expect($decision->state)->toBe(FollowUpStateEnum::STOPPED)
        ->and($decision->stopReason)->toBe('replied');
});

// ---------------------------------------------------------------------------
// Regola 6: input robusti
// ---------------------------------------------------------------------------

it('ignores identical duplicates, including the same instant written in another timezone', function (): void {
    $opening = rome('2026-11-23 10:00');
    $sameInstantUtc = $opening->setTimezone('UTC');

    $decision = decide([$opening, $opening, $sameInstantUtc], now: rome('2026-11-23 11:00'));

    expect($decision->state)->toBe(FollowUpStateEnum::WAITING)
        ->and($decision->step)->toBe(1)
        ->and($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-11-25 10:00 CET');
});

it('gives the same answer whatever the order of the sends', function (): void {
    $sent = [rome('2026-11-23 10:00'), rome('2026-11-25 10:30'), rome('2026-11-27 11:00')];
    $now = rome('2026-11-28 09:00');

    $ordered = decide($sent, $now);
    $shuffled = decide([$sent[2], $sent[0], $sent[1]], $now);
    $reversed = decide(array_reverse($sent), $now);

    expect($shuffled->toArray())->toBe($ordered->toArray())
        ->and($reversed->toArray())->toBe($ordered->toArray())
        ->and($ordered->step)->toBe(3);
});

it('accepts strings and native DateTime objects and reads naive strings in the given timezone', function (): void {
    $fromStrings = decide(['2026-11-23 10:00'], now: rome('2026-11-23 11:00'));
    $fromNative = decide([new DateTimeImmutable('2026-11-23 10:00', rome('2026-11-23')->getTimezone())], now: rome('2026-11-23 11:00'));
    $fromOffset = decide(['2026-11-23T09:00:00+00:00'], now: rome('2026-11-23 11:00'));

    expect($fromStrings->dueAt?->format(ROME_FORMAT))->toBe('2026-11-25 10:00 CET')
        ->and($fromNative->dueAt?->format(ROME_FORMAT))->toBe('2026-11-25 10:00 CET')
        ->and($fromOffset->dueAt?->format(ROME_FORMAT))->toBe('2026-11-25 10:00 CET');
});

it('throws on a value that is not a date instead of guessing', function (): void {
    decide(['not a date'], now: rome('2026-11-23 11:00'));
})->throws(InvalidArgumentException::class);

it('treats zero and negative cadence gaps as one day', function (int $gap): void {
    $decision = decide([rome('2026-11-23 10:00')], now: rome('2026-11-23 11:00'), cadenceDays: [$gap]);

    expect($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-11-24 10:00 CET');
})->with([
    'zero' => [0],
    'minus one' => [-1],
    'minus ten' => [-10],
]);

it('does not depend on the keys of the cadence array', function (): void {
    $decision = decide([rome('2026-11-23 10:00')], now: rome('2026-11-23 11:00'), cadenceDays: [5 => 3, 9 => 1]);

    expect($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-11-26 10:00 CET');
});

it('computes weekdays and deadlines in the requested timezone', function (): void {
    // 2026-11-26 23:30 UTC = ven 2026-11-27 00:30 a Roma, ma giovedi' sera in UTC
    $opening = '2026-11-26 23:30:00+00:00';
    $now = CarbonImmutable::parse('2026-11-27 01:00:00', 'UTC');

    $rome = decide([$opening], $now, cadenceDays: [2], timezone: 'Europe/Rome');
    $utc = decide([$opening], $now, cadenceDays: [2], timezone: 'UTC');

    // Roma: ven 00:30 + 2 = dom 00:30 -> lun 2026-11-30 00:30 (CET)
    expect($rome->dueAt?->format(ROME_FORMAT))->toBe('2026-11-30 00:30 CET')
        // UTC: gio 23:30 + 2 = sab 23:30 -> lun 2026-11-30 23:30
        ->and($utc->dueAt?->format('Y-m-d H:i T'))->toBe('2026-11-30 23:30 UTC');
});

it('reads naive strings in the timezone given to the action', function (): void {
    $now = CarbonImmutable::parse('2026-11-23 12:00', 'UTC');

    $decision = decide(['2026-11-23 10:00'], $now, timezone: 'UTC');

    expect($decision->dueAt?->format('Y-m-d H:i T'))->toBe('2026-11-25 10:00 UTC');
});

// ---------------------------------------------------------------------------
// Ora legale: la scadenza segue l'orologio a muro, non le 48/72 ore
// ---------------------------------------------------------------------------

it('keeps the wall-clock hour across the spring DST change', function (): void {
    // CET -> CEST il 2026-03-29. Ven 03-27 10:00 + 3 giorni = lun 03-30 10:00 CEST (non 11:00)
    $decision = decide([rome('2026-03-27 10:00')], now: rome('2026-03-27 11:00'), cadenceDays: [3]);

    expect($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-03-30 10:00 CEST')
        ->and($decision->dueAt?->utc()->format('H:i'))->toBe('08:00');
});

it('keeps the wall-clock hour across the autumn DST change', function (): void {
    // CEST -> CET il 2026-10-25. Ven 10-23 10:00 + 3 giorni = lun 10-26 10:00 CET (non 09:00)
    $decision = decide([rome('2026-10-23 10:00')], now: rome('2026-10-23 11:00'), cadenceDays: [3]);

    expect($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-10-26 10:00 CET')
        ->and($decision->dueAt?->utc()->format('H:i'))->toBe('09:00');
});

it('shifts a Sunday deadline that falls on the DST change day to Monday at the same wall-clock hour', function (): void {
    // ven 03-27 10:00 + 2 = dom 03-29 10:00 (giorno del cambio) -> lun 03-30 10:00 CEST
    $decision = decide([rome('2026-03-27 10:00')], now: rome('2026-03-27 11:00'), cadenceDays: [2]);

    expect($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-03-30 10:00 CEST');
});

it('switches from waiting to due at the right instant across the DST change', function (): void {
    $opening = rome('2026-03-27 10:00'); // scadenza lun 03-30 10:00 CEST = 08:00 UTC

    $before = decide([$opening], now: CarbonImmutable::parse('2026-03-30 07:59:59', 'UTC'), cadenceDays: [3]);
    $at = decide([$opening], now: CarbonImmutable::parse('2026-03-30 08:00:00', 'UTC'), cadenceDays: [3]);

    expect($before->state)->toBe(FollowUpStateEnum::WAITING)
        ->and($at->state)->toBe(FollowUpStateEnum::DUE);
});

it('does not fail on a local time that does not exist on the DST change day', function (): void {
    // dom 2026-03-29 02:30 non esiste a Roma: si cade comunque su un lunedi' valido
    $decision = decide([rome('2026-03-27 02:30')], now: rome('2026-03-27 03:00'), cadenceDays: [2]);

    expect($decision->state)->toBe(FollowUpStateEnum::WAITING)
        ->and($decision->dueAt?->isMonday())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Orologio e invii "nel futuro"
// ---------------------------------------------------------------------------

it('treats a send dated after now as already sent and just waits', function (): void {
    $now = rome('2026-11-23 10:00');
    $scheduled = rome('2026-11-25 10:00'); // clock skew o invio programmato

    $decision = decide([$scheduled], $now);

    expect($decision->state)->toBe(FollowUpStateEnum::WAITING)
        ->and($decision->step)->toBe(1)
        ->and($decision->dueAt?->format(ROME_FORMAT))->toBe('2026-11-27 10:00 CET');
});

it('does not apply max_age to a first send in the future', function (): void {
    $decision = decide([rome('2026-12-10 10:00')], now: rome('2026-11-23 10:00'));

    expect($decision->state)->toBe(FollowUpStateEnum::WAITING);
});

it('uses the real clock when now is omitted', function (): void {
    $decision = decide(['2020-01-01 10:00']);

    expect($decision->state)->toBe(FollowUpStateEnum::STOPPED)
        ->and($decision->stopReason)->toBe('max_age');
});
