<?php
declare(strict_types=1);

namespace BusinessMapLocator\Application\Location;

use BusinessMapLocator\Domain\Geo\BoundingBox;
use BusinessMapLocator\Domain\Geo\Coordinates;
use BusinessMapLocator\Domain\Geo\Distance;
use BusinessMapLocator\Domain\Geo\DistanceUnit;
use InvalidArgumentException;

final readonly class SearchLocationsQuery
{
    public function __construct(
        public string $search,
        public string $category,
        public string $city,
        public int $page,
        public int $perPage,
        public string $orderby,
        public string $order,
        public DistanceUnit $unit = DistanceUnit::Kilometres,
        public ?BoundingBox $boundingBox = null,
        public ?Coordinates $origin = null,
        public ?Distance $radius = null
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromArray(array $params): self
    {
        $lat = self::nullableFloat($params['lat'] ?? null);
        $lng = self::nullableFloat($params['lng'] ?? null);
        $radius = self::nullableFloat($params['radius'] ?? null);
        $unit = DistanceUnit::tryFrom((string) ($params['unit'] ?? 'km')) ?? DistanceUnit::Kilometres;

        if (($lat === null || $lng === null) && $radius !== null) {
            throw new InvalidArgumentException('Radius search requires latitude and longitude.');
        }

        return new self(
            search: trim((string) ($params['search'] ?? '')),
            category: (string) ($params['category'] ?? ''),
            city: (string) ($params['city'] ?? ''),
            page: max(1, (int) ($params['page'] ?? 1)),
            perPage: min(500, max(1, (int) ($params['per_page'] ?? 200))),
            orderby: self::orderby((string) ($params['orderby'] ?? 'title')),
            order: strtoupper((string) ($params['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC',
            unit: $unit,
            boundingBox: self::boundingBox($params),
            origin: $lat !== null && $lng !== null ? new Coordinates($lat, $lng) : null,
            radius: $radius !== null ? new Distance($radius, $unit) : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function cacheKey(): array
    {
        return [
            'search' => $this->search,
            'category' => $this->category,
            'city' => $this->city,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'orderby' => $this->orderby,
            'order' => $this->order,
            'unit' => $this->unit->value,
            'bbox' => $this->boundingBox ? [$this->boundingBox->west, $this->boundingBox->south, $this->boundingBox->east, $this->boundingBox->north] : null,
            'origin' => $this->origin ? [$this->origin->latitude, $this->origin->longitude] : null,
            'radius' => $this->radius ? [$this->radius->value, $this->radius->unit->value] : null,
        ];
    }

    private static function orderby(string $value): string
    {
        return in_array($value, ['title', 'date', 'modified', 'menu_order', 'distance'], true) ? $value : 'title';
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function boundingBox(array $params): ?BoundingBox
    {
        if (!empty($params['bounds'])) {
            $parts = array_map('trim', explode(',', (string) $params['bounds']));
            if (count($parts) !== 4) {
                throw new InvalidArgumentException('Invalid bounds.');
            }

            return new BoundingBox((float) $parts[0], (float) $parts[1], (float) $parts[2], (float) $parts[3]);
        }

        if (!empty($params['bbox'])) {
            $parts = array_map('trim', explode(',', (string) $params['bbox']));
            if (count($parts) !== 4) {
                throw new InvalidArgumentException('Invalid bbox.');
            }

            return new BoundingBox((float) $parts[0], (float) $parts[1], (float) $parts[2], (float) $parts[3]);
        }

        foreach (['west', 'south', 'east', 'north'] as $key) {
            if (!isset($params[$key]) || $params[$key] === '') {
                return null;
            }
        }

        return new BoundingBox(
            (float) $params['west'],
            (float) $params['south'],
            (float) $params['east'],
            (float) $params['north']
        );
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Invalid numeric value.');
        }

        return (float) $value;
    }
}
