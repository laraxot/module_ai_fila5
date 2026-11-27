<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Spatie\QueueableAction\QueueableAction;

/**
 * Ricerca dal vivo su OpenStreetMap per una zona: Nominatim trova l'area, Overpass le attivita' con un nome
 * delle categorie `ai.prospecting.osm_categories`; poi l'import esistente (normalizza, deduplica, rispetta le
 * soppressioni). Spento di default (`ai.prospecting.enabled`). Attribuzione: "© OpenStreetMap contributors".
 */
final class DiscoverProspectsInAreaAction
{
    use QueueableAction;

    public function __construct(
        private readonly GeocodeAreaAction $geocode,
        private readonly OsmRequestAction $osm,
        private readonly ImportOsmCandidatesAction $toCandidates,
        private readonly ImportProspectCandidatesAction $import,
    ) {}

    /**
     * @return array{area: string, found: int, imported: array<array-key, mixed>}
     */
    public function execute(string $workspace, string $area, int $limit = 200): array
    {
        $categories = ImportOsmCandidatesAction::categories();
        $box = $this->geocode->execute($area);
        $bbox = implode(',', [$box['south'], $box['west'], $box['north'], $box['east']]);
        $regex = implode('|', array_map(static fn (string $c): string => preg_quote($c, '/'), $categories));
        $limit = max(1, min(250, $limit));
        $query = \sprintf('[out:json][timeout:60];nwr["amenity"~"^(%s)$"]["name"](%s);out center tags %d;', $regex, $bbox, $limit);

        $url = config()->string('ai.prospecting.overpass_url', 'https://overpass-api.de/api/interpreter');
        /** @var array<string, mixed> $overpass */
        $overpass = $this->osm->execute('overpass', $url, ['data' => $query], 60 * 60 * 24, 'post');

        $candidates = $this->toCandidates->execute($overpass, $limit);

        return [
            'area' => $box['label'],
            'found' => \count($candidates),
            'imported' => $candidates === [] ? [] : $this->import->execute($workspace, $candidates),
        ];
    }
}
