<?php
declare(strict_types=1);

namespace BusinessMapLocator\Application\Location;

use BusinessMapLocator\Infrastructure\Cache\LocationCache;
use BusinessMapLocator\Infrastructure\Database\LocationRepository;
use InvalidArgumentException;

final readonly class SearchLocationsHandler
{
    public function __construct(
        private LocationRepository $locations,
        private ?LocationCache $cache = null
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function handle(SearchLocationsQuery $query): array
    {
        $this->validate($query);

        $cacheKey = $query->cacheKey();
        $cached = $this->cache?->get('locations', $cacheKey);
        if ($cached !== false && is_array($cached)) {
            return $this->sort($cached, $query);
        }

        $result = $this->locations->search($query);
        $result = $this->sort($result, $query);

        $this->cache?->set('locations', $cacheKey, $result);

        return $result;
    }

    private function validate(SearchLocationsQuery $query): void
    {
        if ($query->page < 1) {
            throw new InvalidArgumentException('Page must be greater than zero.');
        }

        if ($query->perPage < 1 || $query->perPage > 500) {
            throw new InvalidArgumentException('Per page must be between 1 and 500.');
        }

        if ($query->radius && !$query->origin) {
            throw new InvalidArgumentException('Radius search requires latitude and longitude.');
        }

        if ($query->orderby === 'distance' && !$query->origin) {
            throw new InvalidArgumentException('Distance sorting requires latitude and longitude.');
        }
    }

    /**
     * @param array{items: list<array<string, mixed>>, total: int} $result
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    private function sort(array $result, SearchLocationsQuery $query): array
    {
        if ($result['items'] === []) {
            return $result;
        }

        if ($query->orderby === 'distance' && $query->origin) {
            usort($result['items'], static function (array $first, array $second): int {
                $firstDistance = $first['distance'] ?? PHP_FLOAT_MAX;
                $secondDistance = $second['distance'] ?? PHP_FLOAT_MAX;
                $distanceCompare = (float) $firstDistance <=> (float) $secondDistance;

                if ($distanceCompare !== 0) {
                    return $distanceCompare;
                }

                return strnatcasecmp((string) ($first['title'] ?? ''), (string) ($second['title'] ?? ''));
            });

            return $result;
        }

        if ($query->orderby === 'title') {
            usort($result['items'], static function (array $first, array $second) use ($query): int {
                $compare = strnatcasecmp((string) ($first['title'] ?? ''), (string) ($second['title'] ?? ''));
                return $query->order === 'DESC' ? -$compare : $compare;
            });
        }

        return $result;
    }
}
