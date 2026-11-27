<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Prospecting;

use Spatie\QueueableAction\QueueableAction;

/**
 * Converts an already-licensed OSM/Overpass export into business candidates.
 * Performs NO network request or contact harvesting. ODbL attribution is mandatory.
 */
final class ImportOsmCandidatesAction
{
    use QueueableAction;

    /**
     * Categorie OSM `amenity` accettate: `ai.prospecting.osm_categories` dell'host, altrimenti quelle del pilota ristoranti.
     *
     * @return list<string>
     */
    public static function categories(): array
    {
        $configured = config('ai.prospecting.osm_categories', []);
        $valid = is_array($configured) ? array_values(array_filter($configured, is_string(...))) : [];

        return $valid !== [] ? $valid : ['restaurant', 'cafe', 'fast_food', 'bar', 'pub'];
    }

    /**
     * @param array<string, mixed> $overpass
     * @return list<array<string, mixed>>
     */
    public function execute(array $overpass, int $limit = 200): array
    {
        $elements = $overpass['elements'] ?? [];
        if (! is_array($elements)) {
            return [];
        }

        $allowed = self::categories();

        $candidates = [];
        foreach ($elements as $element) {
            if (! is_array($element)) {
                continue;
            }
            $tags = $element['tags'] ?? null;
            $type = $element['type'] ?? null;
            $id = $element['id'] ?? null;
            if (! is_array($tags) || ! in_array($type, ['node', 'way', 'relation'], true)
                || ! is_int($id) || $id <= 0 || ! is_string($tags['name'] ?? null)) {
                continue;
            }
            $category = $tags['amenity'] ?? null;
            if (! in_array($category, $allowed, true)) {
                continue;
            }

            $candidates[] = [
                'name' => $tags['name'],
                'website' => is_string($tags['website'] ?? null) ? $tags['website'] : null,
                'category' => $category,
                'city' => is_string($tags['addr:city'] ?? null) ? $tags['addr:city'] : null,
                'country' => is_string($tags['addr:country'] ?? null) ? $tags['addr:country'] : null,
                'source_provider' => 'openstreetmap',
                'source_url' => 'https://www.openstreetmap.org/'.$type.'/'.$id,
            ];

            if (count($candidates) >= max(1, min(10000, $limit))) {
                break;
            }
        }

        return $candidates;
    }
}
