<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions\Outreach;

use Generator;
use InvalidArgumentException;
use Modules\AI\Actions\Outreach\AdvanceDealStageAction;
use Modules\AI\Enums\DealStageEnum;
use Modules\AI\Tests\TestCase;

use function Safe\preg_split;

uses(TestCase::class);

/**
 * Matrice 8x8 delle transizioni, righe = stadio di partenza, colonne = stadio di arrivo,
 * nell'ordine: lead contacted replied call_booked proposal contract_sent won lost.
 *
 * Legenda: "=" stesso stadio (no-op) · "ok" ammessa · "T" stadio terminale
 *          "H" verso stadio solo-umano (ha la precedenza, anche se e' pure all'indietro)
 *          "B" all'indietro · "S" salto in avanti (solo umano)
 *
 * Per i test l'arrivo "lost" porta sempre un motivo valido.
 */
dataset('deal_stage_matrix', function (): Generator {
    $order = ['lead', 'contacted', 'replied', 'call_booked', 'proposal', 'contract_sent', 'won', 'lost'];

    /** @var array<string, array<string, string>> $grids */
    $grids = [
        'human' => [
            'lead' => '=  ok ok ok ok ok ok ok',
            'contacted' => 'ok =  ok ok ok ok ok ok',
            'replied' => 'ok ok =  ok ok ok ok ok',
            'call_booked' => 'ok ok ok =  ok ok ok ok',
            'proposal' => 'ok ok ok ok =  ok ok ok',
            'contract_sent' => 'ok ok ok ok ok =  ok ok',
            'won' => 'T  T  T  T  T  T  =  T',
            'lost' => 'T  ok T  T  T  T  T  =',
        ],
        'auto' => [
            'lead' => '=  ok S  S  H  H  H  ok',
            'contacted' => 'B  =  ok S  H  H  H  ok',
            'replied' => 'B  B  =  ok H  H  H  ok',
            'call_booked' => 'B  B  B  =  H  H  H  ok',
            'proposal' => 'B  B  B  B  =  H  H  ok',
            'contract_sent' => 'B  B  B  B  H  =  H  ok',
            'won' => 'T  T  T  T  T  T  =  T',
            'lost' => 'T  T  T  T  T  T  T  =',
        ],
    ];

    foreach ($grids as $actor => $grid) {
        foreach ($grid as $from => $row) {
            $cells = array_map(static function (mixed $cell): string {
                if (! is_string($cell)) {
                    throw new \LogicException('Bad matrix cell type');
                }

                return $cell;
            }, preg_split('/\s+/', trim($row)));
            if (count($cells) !== 8) {
                throw new \LogicException("Bad matrix row {$actor}/{$from}");
            }

            foreach ($order as $i => $to) {
                yield "{$actor}: {$from} -> {$to} = {$cells[$i]}" => [
                    DealStageEnum::from($from),
                    DealStageEnum::from($to),
                    $actor === 'human',
                    $cells[$i],
                ];
            }
        }
    }
});

const STAGE_ERRORS = [
    'T' => 'deal_stage_terminal',
    'H' => 'deal_stage_human_only',
    'B' => 'deal_stage_backwards',
    'S' => 'deal_stage_skip_requires_human',
];

describe('DealStageEnum', function (): void {
    test('has the eight stages with stable backing values', function (): void {
        expect(array_map(static fn (DealStageEnum $s): string => $s->value, DealStageEnum::cases()))
            ->toBe(['lead', 'contacted', 'replied', 'call_booked', 'proposal', 'contract_sent', 'won', 'lost']);
    });

    test('probability follows the stage table', function (string $stage, float $expected): void {
        expect(DealStageEnum::from($stage)->probability())->toBe($expected);
    })->with([
        ['lead', 0.05],
        ['contacted', 0.10],
        ['replied', 0.20],
        ['call_booked', 0.30],
        ['proposal', 0.50],
        ['contract_sent', 0.80],
        ['won', 1.0],
        ['lost', 0.0],
    ]);

    test('only won and lost are closed', function (): void {
        $open = array_values(array_filter(DealStageEnum::cases(), static fn (DealStageEnum $s): bool => $s->isOpen()));

        expect($open)->toBe([
            DealStageEnum::LEAD,
            DealStageEnum::CONTACTED,
            DealStageEnum::REPLIED,
            DealStageEnum::CALL_BOOKED,
            DealStageEnum::PROPOSAL,
            DealStageEnum::CONTRACT_SENT,
        ]);
    });

    test('only proposal, contract_sent and won are human-only', function (): void {
        $humanOnly = array_values(array_filter(DealStageEnum::cases(), static fn (DealStageEnum $s): bool => $s->isHumanOnly()));

        expect($humanOnly)->toBe([DealStageEnum::PROPOSAL, DealStageEnum::CONTRACT_SENT, DealStageEnum::WON]);
    });

    test('rank grows along the funnel order and is unique', function (): void {
        $ranks = array_map(static fn (DealStageEnum $s): int => $s->rank(), DealStageEnum::cases());

        expect($ranks)->toBe([0, 1, 2, 3, 4, 5, 6, 7])
            ->and(DealStageEnum::LOST->rank())->toBeGreaterThan(DealStageEnum::WON->rank());
    });
});

describe('AdvanceDealStageAction', function (): void {
    test('applies the full 8x8 transition matrix for human and automatic actors', function (
        DealStageEnum $from,
        DealStageEnum $to,
        bool $byHuman,
        string $expected,
    ): void {
        $action = new AdvanceDealStageAction();
        $reason = $to === DealStageEnum::LOST ? 'no_budget' : null;

        if ($expected === '=') {
            expect($action->execute($from, $to, $byHuman, $reason))->toBe($from);

            return;
        }

        if ($expected === 'ok') {
            expect($action->execute($from, $to, $byHuman, $reason))->toBe($to);

            return;
        }

        $code = STAGE_ERRORS[$expected];

        expect(fn () => $action->execute($from, $to, $byHuman, $reason))
            ->toThrow(fn (InvalidArgumentException $e) => expect($e->getMessage())->toBe($code));
    })->with('deal_stage_matrix');

    test('requires a non-blank lost reason from every open stage and for every actor', function (
        DealStageEnum $from,
        bool $byHuman,
        ?string $reason,
    ): void {
        expect(fn () => (new AdvanceDealStageAction())->execute($from, DealStageEnum::LOST, $byHuman, $reason))
            ->toThrow(fn (InvalidArgumentException $e) => expect($e->getMessage())->toBe('deal_stage_lost_reason_required'));
    })->with(function (): Generator {
        foreach (DealStageEnum::cases() as $from) {
            if (! $from->isOpen()) {
                continue;
            }

            foreach ([true, false] as $byHuman) {
                foreach ([null, '', '   ', "\n\t "] as $reason) {
                    yield [$from, $byHuman, $reason];
                }
            }
        }
    });

    test('accepts any non-blank lost reason and returns lost', function (): void {
        expect((new AdvanceDealStageAction())->execute(DealStageEnum::PROPOSAL, DealStageEnum::LOST, false, 'went_with_competitor'))
            ->toBe(DealStageEnum::LOST);
    });

    test('ignores the lost reason when the target is not lost', function (): void {
        expect((new AdvanceDealStageAction())->execute(DealStageEnum::LEAD, DealStageEnum::CONTACTED, false, 'ignored'))
            ->toBe(DealStageEnum::CONTACTED);
    });

    test('same stage is a no-op even for terminal stages and without a reason', function (DealStageEnum $stage): void {
        foreach ([true, false] as $byHuman) {
            expect((new AdvanceDealStageAction())->execute($stage, $stage, $byHuman))->toBe($stage);
        }
    })->with(function (): Generator {
        foreach (DealStageEnum::cases() as $stage) {
            yield $stage->value => [$stage];
        }
    });

    test('win-back lost -> contacted is allowed only for a human', function (): void {
        $action = new AdvanceDealStageAction();

        expect($action->execute(DealStageEnum::LOST, DealStageEnum::CONTACTED, true))->toBe(DealStageEnum::CONTACTED)
            ->and(fn () => $action->execute(DealStageEnum::LOST, DealStageEnum::CONTACTED, false))
            ->toThrow(fn (InvalidArgumentException $e) => expect($e->getMessage())->toBe('deal_stage_terminal'));
    });

    test('win-back is the only exit from a terminal stage, even for a human', function (DealStageEnum $from, DealStageEnum $to): void {
        expect(fn () => (new AdvanceDealStageAction())->execute($from, $to, true, 'reason'))
            ->toThrow(fn (InvalidArgumentException $e) => expect($e->getMessage())->toBe('deal_stage_terminal'));
    })->with([
        'won -> contacted' => [DealStageEnum::WON, DealStageEnum::CONTACTED],
        'won -> lead' => [DealStageEnum::WON, DealStageEnum::LEAD],
        'won -> lost' => [DealStageEnum::WON, DealStageEnum::LOST],
        'lost -> lead' => [DealStageEnum::LOST, DealStageEnum::LEAD],
        'lost -> replied' => [DealStageEnum::LOST, DealStageEnum::REPLIED],
        'lost -> won' => [DealStageEnum::LOST, DealStageEnum::WON],
    ]);

    test('error messages are exactly the stable code, with no extra text', function (): void {
        try {
            (new AdvanceDealStageAction())->execute(DealStageEnum::WON, DealStageEnum::LEAD, true);
            $this->fail('expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            expect($e->getMessage())->toBe('deal_stage_terminal');
        }
    });
});
