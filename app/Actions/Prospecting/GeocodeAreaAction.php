<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Modules\AI\Exceptions\AiRequestException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Da "Treviso" o "Mestre, Venezia" all'area in cui cercare (riquadro sud, ovest, nord, est) con Nominatim.
 * Risultato in cache per 30 giorni: i confini di una citta' non cambiano.
 */
final class GeocodeAreaAction
{
    use QueueableAction;

    public function __construct(private readonly OsmRequestAction $osm) {}

    /**
     * @return array{label: string, south: float, west: float, north: float, east: float}
     */
    public function execute(string $area): array
    {
        $area = trim($area);
        if ($area === '') {
            throw new AiRequestException('Indica una citta\' o una zona.');
        }

        $base = config('ai.prospecting.nominatim_url');
        $url = rtrim(\is_string($base) ? $base : 'https://nominatim.openstreetmap.org', '/').'/search';
        $results = $this->osm->execute('nominatim', $url, ['q' => $area, 'format' => 'jsonv2', 'limit' => 1], 60 * 60 * 24 * 30);

        $first = $results[0] ?? null;
        $box = \is_array($first) ? ($first['boundingbox'] ?? null) : null;
        if (! \is_array($first) || ! \is_array($box) || \count($box) !== 4) {
            throw new AiRequestException(\sprintf('Zona "%s" non trovata su OpenStreetMap.', $area));
        }

        [$south, $north, $west, $east] = array_map(static fn (mixed $v): float => is_numeric($v) ? (float) $v : 0.0, array_values($box));
        $label = $first['display_name'] ?? $area;

        return [
            'label' => \is_string($label) ? $label : $area,
            'south' => $south,
            'west' => $west,
            'north' => $north,
            'east' => $east,
        ];
    }
}
