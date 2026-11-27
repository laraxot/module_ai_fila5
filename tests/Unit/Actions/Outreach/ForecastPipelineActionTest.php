<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions\Outreach;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\AI\Actions\Outreach\ForecastPipelineAction;
use Modules\AI\Datas\DealSnapshotData;
use Modules\AI\Datas\ForecastResultData;
use Modules\AI\Enums\DealStageEnum;
use Modules\AI\Tests\TestCase;

uses(TestCase::class);

function outreachDeal(
    DealStageEnum $stage,
    int $valueCents,
    ?string $closeDate = '2026-11-15 12:00:00',
    string $currency = 'EUR',
    string $timezone = 'UTC',
): DealSnapshotData {
    return new DealSnapshotData(
        stage: $stage,
        valueCents: $valueCents,
        closeDate: $closeDate === null ? null : CarbonImmutable::parse($closeDate, $timezone),
        currency: $currency,
    );
}

function outreachNovember(string $timezone = 'UTC'): CarbonImmutable
{
    return CarbonImmutable::parse('2026-11-01 00:00:00', $timezone);
}

/** @param  list<DealSnapshotData>  $deals */
function outreachForecast(array $deals, ?float $winRate = null, int $sample = 0): ForecastResultData
{
    return (new ForecastPipelineAction())->execute($deals, outreachNovember(), $winRate, $sample);
}

function expectForecastError(callable $call, string $code): void
{
    expect($call)->toThrow(fn (InvalidArgumentException $e) => expect($e->getMessage())->toBe($code));
}

describe('scope of the forecast', function (): void {
    test('returns zeros and the default currency when there are no deals at all', function (): void {
        $result = outreachForecast([]);

        expect($result->commitCents)->toBe(0)
            ->and($result->likelyCents)->toBe(0)
            ->and($result->bestCents)->toBe(0)
            ->and($result->dealCount)->toBe(0)
            ->and($result->currency)->toBe('EUR');
    });

    test('returns zeros when no deal closes in the month, keeping the pipeline currency', function (): void {
        $result = outreachForecast([
            outreachDeal(DealStageEnum::PROPOSAL, 500_000, '2026-12-10 10:00:00', 'USD'),
            outreachDeal(DealStageEnum::LEAD, 100_000, '2026-10-10 10:00:00', 'USD'),
        ]);

        expect($result->bestCents)->toBe(0)
            ->and($result->dealCount)->toBe(0)
            ->and($result->currency)->toBe('USD');
    });

    test('excludes deals without a close date', function (): void {
        $result = outreachForecast([
            outreachDeal(DealStageEnum::CONTRACT_SENT, 100_000, null),
            outreachDeal(DealStageEnum::CONTRACT_SENT, 200_000),
        ]);

        expect($result->commitCents)->toBe(200_000)
            ->and($result->bestCents)->toBe(200_000)
            ->and($result->dealCount)->toBe(1);
    });

    test('excludes won and lost deals even when they close in the month', function (): void {
        $result = outreachForecast([
            outreachDeal(DealStageEnum::WON, 900_000),
            outreachDeal(DealStageEnum::LOST, 800_000),
            outreachDeal(DealStageEnum::REPLIED, 100_000),
        ]);

        expect($result->bestCents)->toBe(100_000)
            ->and($result->dealCount)->toBe(1);
    });

    test('commit is contract_sent only, best is every open deal, likely is value x stage probability', function (): void {
        $result = outreachForecast([
            outreachDeal(DealStageEnum::LEAD, 100_000),          // 0.05 ->   5_000
            outreachDeal(DealStageEnum::CONTACTED, 200_000),     // 0.10 ->  20_000
            outreachDeal(DealStageEnum::REPLIED, 300_000),       // 0.20 ->  60_000
            outreachDeal(DealStageEnum::CALL_BOOKED, 400_000),   // 0.30 -> 120_000
            outreachDeal(DealStageEnum::PROPOSAL, 500_000),      // 0.50 -> 250_000
            outreachDeal(DealStageEnum::CONTRACT_SENT, 1_000_000), // 0.80 -> 800_000
        ]);

        expect($result->commitCents)->toBe(1_000_000)
            ->and($result->bestCents)->toBe(2_500_000)
            ->and($result->likelyCents)->toBe(1_255_000)
            ->and($result->dealCount)->toBe(6)
            ->and($result->currency)->toBe('EUR');
    });
});

describe('month boundaries', function (): void {
    test('includes the first instant and the last second of the month, excludes the neighbours', function (string $closeDate, bool $included): void {
        $result = outreachForecast([outreachDeal(DealStageEnum::PROPOSAL, 100_000, $closeDate)]);

        expect($result->dealCount)->toBe($included ? 1 : 0)
            ->and($result->bestCents)->toBe($included ? 100_000 : 0);
    })->with([
        'previous month last second' => ['2026-10-31 23:59:59', false],
        'first day 00:00:00' => ['2026-11-01 00:00:00', true],
        'last day 23:59:59' => ['2026-11-30 23:59:59', true],
        'last day 23:59:59.999999' => ['2026-11-30 23:59:59.999999', true],
        'next month 00:00:00' => ['2026-12-01 00:00:00', false],
    ]);

    test('uses the timezone of the month start, not UTC', function (string $closeDateUtc, bool $included): void {
        // Rome is UTC+1 in November: the Rome month spans 2026-10-31T23:00Z .. 2026-11-30T23:00Z.
        $monthStart = CarbonImmutable::parse('2026-11-01 00:00:00', 'Europe/Rome');
        $deal = outreachDeal(DealStageEnum::PROPOSAL, 100_000, $closeDateUtc, 'EUR', 'UTC');

        $result = (new ForecastPipelineAction())->execute([$deal], $monthStart);

        expect($result->dealCount)->toBe($included ? 1 : 0);
    })->with([
        'Oct 31 22:59Z is still October in Rome' => ['2026-10-31 22:59:59', false],
        'Oct 31 23:00Z is Nov 1 00:00 in Rome' => ['2026-10-31 23:00:00', true],
        'Nov 30 22:59Z is still November in Rome' => ['2026-11-30 22:59:59', true],
        'Nov 30 23:00Z is already December in Rome' => ['2026-11-30 23:00:00', false],
    ]);

    test('a month start in the middle of the month still selects the whole month', function (): void {
        $result = (new ForecastPipelineAction())->execute(
            [
                outreachDeal(DealStageEnum::PROPOSAL, 100_000, '2026-11-02 08:00:00'),
                outreachDeal(DealStageEnum::PROPOSAL, 100_000, '2026-11-29 08:00:00'),
            ],
            CarbonImmutable::parse('2026-11-15 10:30:00', 'UTC'),
        );

        expect($result->dealCount)->toBe(2);
    });

    test('handles a leap-year February', function (): void {
        $result = (new ForecastPipelineAction())->execute(
            [
                outreachDeal(DealStageEnum::PROPOSAL, 100_000, '2028-02-29 23:59:59'),
                outreachDeal(DealStageEnum::PROPOSAL, 100_000, '2028-03-01 00:00:00'),
            ],
            CarbonImmutable::parse('2028-02-01', 'UTC'),
        );

        expect($result->dealCount)->toBe(1);
    });
});

describe('integer money arithmetic', function (): void {
    test('rounds half cents up to the next integer cent', function (int $valueCents, int $expectedLikely): void {
        $result = outreachForecast([outreachDeal(DealStageEnum::CONTACTED, $valueCents)]);

        expect($result->likelyCents)->toBe($expectedLikely);
    })->with([
        '0.4 cents -> 0' => [4, 0],
        '0.5 cents -> 1' => [5, 1],
        '1.5 cents -> 2 (half up, not banker)' => [15, 2],
        '2.5 cents -> 3 (half up, not banker)' => [25, 3],
        '0.1 cents -> 0' => [1, 0],
        'exact integer cents' => [100, 10],
    ]);

    test('rounds once on the total, not once per deal', function (): void {
        // 5 x 0.10 = 0.5 cents each: per-deal rounding would give 2, the exact total 1.0 gives 1.
        $result = outreachForecast([
            outreachDeal(DealStageEnum::CONTACTED, 5),
            outreachDeal(DealStageEnum::CONTACTED, 5),
        ]);

        expect($result->likelyCents)->toBe(1);
    });

    test('sums exact fractions of a cent before rounding', function (): void {
        // 3 x 0.50 = 1.5 cents; 1_000_000_007 x 0.20 = 200_000_001.4 cents; total 200_000_002.9 -> 200_000_003.
        $result = outreachForecast([
            outreachDeal(DealStageEnum::PROPOSAL, 3),
            outreachDeal(DealStageEnum::REPLIED, 1_000_000_007),
        ]);

        expect($result->likelyCents)->toBe(200_000_003);
    });

    test('keeps exact precision with the largest representable value', function (): void {
        $result = outreachForecast([outreachDeal(DealStageEnum::CONTACTED, PHP_INT_MAX)]);

        // 9223372036854775807 x 0.10 = 922337203685477580.7 -> 922337203685477581
        expect($result->bestCents)->toBe(PHP_INT_MAX)
            ->and($result->likelyCents)->toBe(922_337_203_685_477_581);
    });

    test('does not overflow the probability product for a huge contract_sent deal', function (): void {
        $result = outreachForecast([outreachDeal(DealStageEnum::CONTRACT_SENT, PHP_INT_MAX)]);

        // 9223372036854775807 x 0.80 = 7378697629483820645.6 -> 7378697629483820646
        expect($result->commitCents)->toBe(PHP_INT_MAX)
            ->and($result->likelyCents)->toBe(7_378_697_629_483_820_646);
    });

    test('throws instead of silently turning into float when totals overflow int', function (): void {
        expectForecastError(
            fn () => outreachForecast([
                outreachDeal(DealStageEnum::CONTACTED, PHP_INT_MAX),
                outreachDeal(DealStageEnum::CONTACTED, 1),
            ]),
            'forecast_amount_overflow',
        );
    });

    test('rejects negative values', function (): void {
        expectForecastError(
            fn () => outreachForecast([outreachDeal(DealStageEnum::PROPOSAL, -1)]),
            'forecast_negative_value',
        );
    });

    test('ignores an invalid value on a deal that is out of scope', function (): void {
        $result = outreachForecast([
            outreachDeal(DealStageEnum::LOST, -1),
            outreachDeal(DealStageEnum::PROPOSAL, -1, '2026-12-01 00:00:00'),
            outreachDeal(DealStageEnum::PROPOSAL, 100),
        ]);

        expect($result->bestCents)->toBe(100);
    });
});

describe('win-rate calibration', function (): void {
    test('keeps the standard probabilities below 10 closed deals', function (): void {
        $result = outreachForecast([outreachDeal(DealStageEnum::CONTRACT_SENT, 100_000)], 0.5, 9);

        expect($result->likelyCents)->toBe(80_000);
    });

    test('keeps the standard probabilities when the historical win rate is unknown', function (): void {
        $result = outreachForecast([outreachDeal(DealStageEnum::CONTRACT_SENT, 100_000)], null, 500);

        expect($result->likelyCents)->toBe(80_000);
    });

    test('scales the open-stage probabilities by winRate / 0.25 from 10 closed deals', function (): void {
        $deals = [
            outreachDeal(DealStageEnum::CONTRACT_SENT, 100_000),
            outreachDeal(DealStageEnum::CALL_BOOKED, 100_000),
        ];

        // scale 0.5: 0.80 -> 0.40, 0.30 -> 0.15
        expect(outreachForecast($deals, 0.125, 10)->likelyCents)->toBe(55_000)
            // scale 1.0: unchanged
            ->and(outreachForecast($deals, 0.25, 10)->likelyCents)->toBe(110_000);
    });

    test('caps a scaled probability at 0.95', function (): void {
        // winRate 0.5 -> scale 2: contract_sent 1.6 -> 0.95 ; proposal 1.0 -> 0.95 ; call_booked 0.6 stays 0.6
        $result = outreachForecast([
            outreachDeal(DealStageEnum::CONTRACT_SENT, 100_000),
            outreachDeal(DealStageEnum::PROPOSAL, 100_000),
            outreachDeal(DealStageEnum::CALL_BOOKED, 100_000),
        ], 0.5, 40);

        expect($result->likelyCents)->toBe(95_000 + 95_000 + 60_000);
    });

    test('a zero win rate zeroes likely but never commit or best', function (): void {
        $result = outreachForecast([outreachDeal(DealStageEnum::CONTRACT_SENT, 100_000)], 0.0, 10);

        expect($result->likelyCents)->toBe(0)
            ->and($result->commitCents)->toBe(100_000)
            ->and($result->bestCents)->toBe(100_000);
    });

    test('calibration never changes commit or best', function (): void {
        $deals = [
            outreachDeal(DealStageEnum::CONTRACT_SENT, 100_000),
            outreachDeal(DealStageEnum::LEAD, 50_000),
        ];

        $plain = outreachForecast($deals);
        $scaled = outreachForecast($deals, 0.5, 10);

        expect($scaled->commitCents)->toBe($plain->commitCents)
            ->and($scaled->bestCents)->toBe($plain->bestCents);
    });

    test('rejects a win rate outside 0..1 or not a number', function (float $winRate): void {
        expectForecastError(
            fn () => outreachForecast([outreachDeal(DealStageEnum::PROPOSAL, 100)], $winRate, 10),
            'forecast_win_rate_out_of_range',
        );
    })->with([-0.01, 1.01, NAN, INF]);
});

describe('currencies', function (): void {
    test('refuses to add up different currencies in scope', function (): void {
        expectForecastError(
            fn () => outreachForecast([
                outreachDeal(DealStageEnum::PROPOSAL, 100, currency: 'EUR'),
                outreachDeal(DealStageEnum::PROPOSAL, 100, currency: 'USD'),
            ]),
            'forecast_mixed_currency',
        );
    });

    test('does not mind a different currency on deals that are out of scope', function (): void {
        $result = outreachForecast([
            outreachDeal(DealStageEnum::PROPOSAL, 100),
            outreachDeal(DealStageEnum::WON, 100, currency: 'USD'),
            outreachDeal(DealStageEnum::PROPOSAL, 100, '2026-12-05 10:00:00', 'USD'),
            outreachDeal(DealStageEnum::PROPOSAL, 100, null, 'USD'),
        ]);

        expect($result->bestCents)->toBe(100)
            ->and($result->currency)->toBe('EUR');
    });

    test('treats currency codes case-insensitively and reports them uppercase', function (): void {
        $result = outreachForecast([
            outreachDeal(DealStageEnum::PROPOSAL, 100, currency: 'eur'),
            outreachDeal(DealStageEnum::PROPOSAL, 100, currency: 'EUR'),
        ]);

        expect($result->bestCents)->toBe(200)
            ->and($result->currency)->toBe('EUR');
    });

    test('reports the currency of the deals in scope', function (): void {
        $result = outreachForecast([outreachDeal(DealStageEnum::PROPOSAL, 100, currency: 'USD')]);

        expect($result->currency)->toBe('USD');
    });
});

describe('snapshot data defaults', function (): void {
    test('DealSnapshotData defaults to EUR', function (): void {
        $deal = new DealSnapshotData(stage: DealStageEnum::LEAD, valueCents: 1, closeDate: null);

        expect($deal->currency)->toBe('EUR')
            ->and($deal->closeDate)->toBeNull();
    });
});
