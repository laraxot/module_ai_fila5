<?php

declare(strict_types=1);

namespace Modules\AI\Actions;

use Illuminate\Support\Carbon;
use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\AI\Models\AiProspect;
use Modules\AI\Models\AiSuppression;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Import di prospezze da dati pubblici già acquisiti (CSV, OSM, listing).
 *
 * Idempotente: identità deterministica (sha256 domain -> public_id).
 * Rispetta le suppressioni (do-not-contact) anche nei re-import.
 * Conserva source URL e timestamp della raccolta dati.
 *
 * SECURITY: niente scraping di siti privati/login; niente email dedotte; niente
 * invio automatico: lo stato resta a `pending`.
 *
 * @see docs/adr/0004-phpstan-zero-errori.md — `getString()` fa narrowing dei campi `mixed`
 *      delle righe CSV/OSM (is_scalar -> string) e gli array shapes dei parametri
 *      restano dichiarati in `@param`
 */
class ImportProspectsAction
{
    use QueueableAction;

    /**
     * @param  array<int, array<string, mixed>>  $rows  righe di import con domain/name/industry...
     */
    public function execute(array $rows, string $workspaceId, ?string $importSource = null, ?string $sourceUrl = null): int
    {
        $imported = 0;

        foreach ($rows as $row) {
            Assert::isArray($row);

            $domain = $this->getString($row, 'domain') ?? $this->getString($row, 'email_domain') ?? '';

            if ($domain === '') {
                continue;
            }

            $publicId = AiProspect::deterministicId($domain);

            $existing = AiProspect::query()
                ->where('public_id', $publicId)
                ->where('workspace_id', $workspaceId)
                ->first();

            if ($existing !== null && $this->isSuppressed($existing)) {
                continue;
            }

            $now = Carbon::now();

            $prospect = $existing === null ? new AiProspect() : $existing;

            if ($existing === null) {
                $prospect->fill([
                    'public_id' => $publicId,
                    'workspace_id' => $workspaceId,
                    'name' => $this->getString($row, 'name'),
                    'domain' => $domain,
                    'website' => $this->getString($row, 'website'),
                    'source' => $importSource,
                    'source_url' => $sourceUrl,
                    'source_at' => $now,
                    'city' => $this->getString($row, 'city'),
                    'region' => $this->getString($row, 'region'),
                    'country_code' => $this->getString($row, 'country_code'),
                    'industry' => $this->getString($row, 'industry'),
                    'phone' => $this->getString($row, 'phone'),
                    'raw_data' => $row,
                    'status' => AiProspectStatusEnum::NEW,
                ]);
                $prospect->save();

                $imported++;
            }

            $this->updateSource($prospect, $importSource, $sourceUrl, $now);

        }

        return $imported;
    }

    /**
     * Safely extract a string value from an array.
     *
     * @param  array<string, mixed>  $data
     */
    private function getString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Update source provenance without changing the prospect's workflow state.
     */
    private function updateSource(AiProspect $prospect, ?string $importSource, ?string $sourceUrl, Carbon $now): void
    {
        $prospect->update([
            'source' => $prospect->source ?? $importSource,
            'source_url' => $prospect->source_url ?? $sourceUrl,
            'source_at' => $now,
        ]);
    }

    private function isSuppressed(AiProspect $prospect): bool
    {
        return $prospect->status === AiProspectStatusEnum::COLD_LOST
            || AiSuppression::findForPublicId($prospect->public_id, $prospect->workspace_id) !== null;
    }
}
