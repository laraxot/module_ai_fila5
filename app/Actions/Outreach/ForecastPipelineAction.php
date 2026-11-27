<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Outreach;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\AI\Datas\DealSnapshotData;
use Modules\AI\Datas\ForecastResultData;
use Modules\AI\Enums\DealStageEnum;
use Spatie\QueueableAction\QueueableAction;

/**
 * Previsione di chiusura del mese di `$monthStart`.
 *
 * Perimetro: solo i deal APERTI con `closeDate` dentro il mese (nel fuso di `$monthStart`,
 * mezzanotte del giorno 1 inclusa, mezzanotte del mese dopo esclusa). Senza `closeDate` = fuori.
 *
 *  - commit = somma dei valori in `contract_sent`
 *  - best   = somma dei valori di tutti i deal del perimetro
 *  - likely = somma di valore x probabilita' dello stadio
 *
 * Soldi: solo interi. La probabilita' diventa parti-per-milione intere e il prodotto si calcola
 * spezzando il valore in quoziente/resto di 1.000.000, cosi' non supera mai PHP_INT_MAX nemmeno
 * con valori enormi. L'arrotondamento e' UNO SOLO, sul totale, a "mezzo centesimo in su"
 * (half-up, 0,5 -> 1; non banker's). Se una somma esce da int si lancia ERR_OVERFLOW, mai float.
 *
 * Calibrazione: con `$closedSample >= 10` e un win rate noto, le probabilita' si moltiplicano per
 * `winRate / 0.25` con tetto 0,95. Non tocca commit e best.
 *
 * Valute: tutti i deal del perimetro devono avere la stessa valuta (confronto senza maiuscole/minuscole);
 * i deal fuori perimetro non contano. Con perimetro vuoto la valuta e' quella del primo deal ricevuto, o EUR.
 *
 * Errori: InvalidArgumentException il cui messaggio e' ESATTAMENTE uno dei codici ERR_*.
 *
 * ponytail: nessuna conversione di cambio (le valute miste sono un errore), nessun deal "won" del mese
 * (il report "won so far" non e' richiesto), upgrade a un value object Money quando serviranno cambi.
 *
 * @see docs/adr/0004-phpstan-zero-errori.md — costanti tipizzate (PHP 8.3 `const string/int/float`): ERR_* sempre stringhe, le soglie numeriche mai `mixed`
 */
final class ForecastPipelineAction
{
    use QueueableAction;

    public const string ERR_MIXED_CURRENCY = 'forecast_mixed_currency';

    public const string ERR_AMOUNT_OVERFLOW = 'forecast_amount_overflow';

    public const string ERR_NEGATIVE_VALUE = 'forecast_negative_value';

    public const string ERR_WIN_RATE_OUT_OF_RANGE = 'forecast_win_rate_out_of_range';

    private const int MIN_CLOSED_SAMPLE = 10;

    /** Win rate che le probabilita' di DealStageEnum assumono. */
    private const float BASE_WIN_RATE = 0.25;

    private const float MAX_SCALED_PROBABILITY = 0.95;

    private const string DEFAULT_CURRENCY = 'EUR';

    /** Probabilita' in parti per milione: 0,05 -> 50_000. Con tetto 0,95 il quoziente x ppm resta sotto PHP_INT_MAX. */
    private const int PPM = 1_000_000;

    private const int HALF_PPM = 500_000;

    /**
     * @param  list<DealSnapshotData>  $deals
     *
     * @throws InvalidArgumentException con un codice ERR_* come messaggio
     */
    public function execute(
        array $deals,
        CarbonImmutable $monthStart,
        ?float $historicalWinRate = null,
        int $closedSample = 0,
    ): ForecastResultData {
        $scale = $this->calibrationScale($historicalWinRate, $closedSample);
        $from = $monthStart->startOfMonth();
        $until = $from->addMonth();

        $commit = 0;
        $best = 0;
        $likelyCents = 0;
        $likelyMicro = 0; // frazione di centesimo in milionesimi, sempre < PPM dopo il riporto
        $count = 0;
        $currency = null;

        foreach ($deals as $deal) {
            if (! $this->isInScope($deal, $from, $until)) {
                continue;
            }

            if ($deal->valueCents < 0) {
                throw new InvalidArgumentException(self::ERR_NEGATIVE_VALUE);
            }

            $dealCurrency = strtoupper($deal->currency);
            if ($currency !== null && $currency !== $dealCurrency) {
                throw new InvalidArgumentException(self::ERR_MIXED_CURRENCY);
            }
            $currency = $dealCurrency;

            $ppm = $this->probabilityPpm($deal->stage, $scale);
            $likelyCents = $this->add($likelyCents, intdiv($deal->valueCents, self::PPM) * $ppm);
            $likelyMicro += ($deal->valueCents % self::PPM) * $ppm;
            $likelyCents = $this->add($likelyCents, intdiv($likelyMicro, self::PPM));
            $likelyMicro %= self::PPM;

            $best = $this->add($best, $deal->valueCents);
            if ($deal->stage === DealStageEnum::CONTRACT_SENT) {
                $commit = $this->add($commit, $deal->valueCents);
            }
            $count++;
        }

        if ($likelyMicro >= self::HALF_PPM) {
            $likelyCents = $this->add($likelyCents, 1);
        }

        return new ForecastResultData(
            commitCents: $commit,
            likelyCents: $likelyCents,
            bestCents: $best,
            currency: $currency ?? strtoupper($deals[0]->currency ?? self::DEFAULT_CURRENCY),
            dealCount: $count,
        );
    }

    private function isInScope(DealSnapshotData $deal, CarbonImmutable $from, CarbonImmutable $until): bool
    {
        $closeDate = $deal->closeDate;

        return $deal->stage->isOpen()
            && $closeDate !== null
            && $closeDate->greaterThanOrEqualTo($from)
            && $closeDate->lessThan($until);
    }

    /** null = nessuna calibrazione (win rate ignoto o campione troppo piccolo). */
    private function calibrationScale(?float $historicalWinRate, int $closedSample): ?float
    {
        if ($historicalWinRate === null) {
            return null;
        }

        // Scritto in positivo: cosi' NAN e INF finiscono nell'errore.
        if (! ($historicalWinRate >= 0.0 && $historicalWinRate <= 1.0)) {
            throw new InvalidArgumentException(self::ERR_WIN_RATE_OUT_OF_RANGE);
        }

        return $closedSample >= self::MIN_CLOSED_SAMPLE ? $historicalWinRate / self::BASE_WIN_RATE : null;
    }

    private function probabilityPpm(DealStageEnum $stage, ?float $scale): int
    {
        $probability = $stage->probability();

        if ($scale !== null) {
            $probability = min(self::MAX_SCALED_PROBABILITY, $probability * $scale);
        }

        return (int) round($probability * self::PPM);
    }

    /** Somma di interi non negativi che lancia invece di diventare float. */
    private function add(int $a, int $b): int
    {
        if ($a > PHP_INT_MAX - $b) {
            throw new InvalidArgumentException(self::ERR_AMOUNT_OVERFLOW);
        }

        return $a + $b;
    }
}
