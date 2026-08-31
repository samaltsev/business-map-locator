<?php
declare(strict_types=1);

namespace BusinessMapLocator\Infrastructure\Database;

use BusinessMapLocator\Application\Location\SearchLocationsQuery;
use BusinessMapLocator\Domain\Geo\BoundingBox;
use BusinessMapLocator\Domain\Geo\Coordinates;
use BusinessMapLocator\Domain\Geo\Distance;

final readonly class LocationRepository
{
    /** @return array{items:list<array<string,mixed>>,truncated:bool} */
    public function markers(float $north, float $south, float $east, float $west, string $category = '', string $city = '', string $search = '', int $limit = 1000, bool $fullWorld = false, ?Coordinates $origin = null, ?Distance $radius = null): array
    {
        global $wpdb;
        $limit = max(1, min(2000, $limit)); $table = \BML_Database::locations_index_table(); $where = ["visibility = 'public'", "operational_status <> 'hidden'", 'latitude BETWEEN %f AND %f']; $values = [$south, $north];
        if (!$fullWorld) {
            if ($west <= $east) {
                $where[] = 'longitude BETWEEN %f AND %f';
            } else {
                $where[] = '(longitude >= %f OR longitude <= %f)';
            }
            array_push($values, $west, $east);
        }
        $this->appendPublicFilters($where, $values, $category, $city, $search);

        $distanceSql = '';
        $havingSql = '';
        if ($origin && $radius) {
            $bbox = $this->radiusBoundingBox($origin, $radius);
            $where[] = 'latitude BETWEEN %f AND %f';
            array_push($values, $bbox->south, $bbox->north);
            $where[] = 'longitude BETWEEN %f AND %f';
            array_push($values, $bbox->west, $bbox->east);
            $distanceSql = $this->distanceSql($origin, $radius);
            $havingSql = $wpdb->prepare(' HAVING distance <= %f', $radius->value);
        }

        $values[] = $limit + 1;
        $orderSql = $distanceSql !== '' ? 'ORDER BY distance ASC, title ASC' : 'ORDER BY title ASC';
        $sql = "SELECT post_id,title,latitude,longitude,operational_status,category,category_slug{$distanceSql} FROM {$table} WHERE " . implode(' AND ', $where) . "{$havingSql} {$orderSql} LIMIT %d";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A);
        $truncated = is_array($rows) && count($rows) > $limit; $rows = array_slice(is_array($rows) ? $rows : [], 0, $limit);
        return ['items' => array_map(static fn(array $row): array => ['id'=>(int)$row['post_id'],'title'=>(string)$row['title'],'lat'=>(float)$row['latitude'],'lng'=>(float)$row['longitude'],'operational_status'=>(string)$row['operational_status'],'category'=>$row['category'] !== '' ? ['name'=>(string)$row['category'],'slug'=>(string)$row['category_slug']] : null,'distance'=>isset($row['distance']) ? round((float)$row['distance'], 3) : null], $rows), 'truncated' => $truncated];
    }
    /** @return array{north:?float,south:?float,east:?float,west:?float,total:int} */
    public function publicBounds(string $category = '', string $city = '', string $search = ''): array
    {
        global $wpdb;

        $table = \BML_Database::locations_index_table();
        $where = [
            "visibility = 'public'",
            "operational_status <> 'hidden'",
            'latitude IS NOT NULL',
            'longitude IS NOT NULL',
        ];
        $values = [];
        $this->appendPublicFilters($where, $values, $category, $city, $search);
        $sql = "SELECT MIN(latitude) AS south, MAX(latitude) AS north, MIN(longitude) AS west, MAX(longitude) AS east, COUNT(1) AS total FROM {$table} WHERE " . implode(' AND ', $where);
        $row = $wpdb->get_row($this->prepare($sql, $values), ARRAY_A);

        if (!is_array($row) || (int) ($row['total'] ?? 0) === 0) {
            return ['north' => null, 'south' => null, 'east' => null, 'west' => null, 'total' => 0];
        }

        return [
            'north' => (float) $row['north'],
            'south' => (float) $row['south'],
            'east' => (float) $row['east'],
            'west' => (float) $row['west'],
            'total' => (int) $row['total'],
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function search(SearchLocationsQuery $query): array
    {
        global $wpdb;

        $table = \BML_Database::locations_index_table();
        $where = [
            "visibility = 'public'",
            "operational_status <> 'hidden'",
            'latitude IS NOT NULL',
            'longitude IS NOT NULL',
        ];
        $values = [];

        $this->appendPublicFilters($where, $values, $query->category, $query->city, $query->search);

        if ($query->boundingBox) {
            $this->appendBoundingBox($where, $values, $query->boundingBox);
        }

        if ($query->origin && $query->radius) {
            $this->appendBoundingBox($where, $values, $this->radiusBoundingBox($query->origin, $query->radius));
        }

        $distanceSql = '';
        if ($query->origin) {
            $distanceSql = $this->distanceSql($query->origin, $query->radius);
        }

        $whereSql = implode(' AND ', $where);
        $havingSql = '';
        if ($distanceSql !== '' && $query->radius) {
            $havingSql = $wpdb->prepare(' HAVING distance <= %f', $query->radius->value);
        }

        $orderSql = $this->orderSql($query);
        $offset = ($query->page - 1) * $query->perPage;
        $baseSql = "FROM {$table} WHERE {$whereSql}";
        $totalSql = "SELECT COUNT(1) {$baseSql}";

        if ($havingSql !== '') {
            $countSql = "SELECT COUNT(1) FROM (SELECT id {$distanceSql} {$baseSql}{$havingSql}) bml_radius_count";
            $total = (int) $wpdb->get_var($this->prepare($countSql, $values));
        } else {
            $total = (int) $wpdb->get_var($this->prepare($totalSql, $values));
        }

        $sql = "SELECT *{$distanceSql} {$baseSql}{$havingSql} {$orderSql} LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results($this->prepare($sql, array_merge($values, [$query->perPage, $offset])), ARRAY_A);

        return [
            'items' => array_map(fn (array $row): array => $this->rowToLocation($row, $query->perPage <= 100), is_array($rows) ? $rows : []),
            'total' => $total,
        ];
    }

    private function radiusBoundingBox(Coordinates $origin, Distance $radius): BoundingBox
    {
        $distanceKm = $radius->unit->value === 'mi' ? $radius->value * 1.609344 : $radius->value;
        $latDelta = $distanceKm / 111.045;
        $lngDelta = $distanceKm / max(0.000001, 111.045 * cos(deg2rad($origin->latitude)));
        $west = $origin->longitude - $lngDelta;
        $east = $origin->longitude + $lngDelta;

        // BoundingBox cannot express a dateline-wrapping range. A full longitude
        // prefilter is deliberately broader here; the Haversine HAVING predicate
        // remains the authoritative radius filter and prevents false negatives.
        if ($west < -180 || $east > 180) {
            $west = -180;
            $east = 180;
        }

        return new BoundingBox(
            $west,
            max(-90, $origin->latitude - $latDelta),
            $east,
            min(90, $origin->latitude + $latDelta)
        );
    }

    /** @param list<string> $where @param list<mixed> $values */
    private function appendBoundingBox(array &$where, array &$values, BoundingBox $boundingBox): void
    {
        $where[] = 'latitude BETWEEN %f AND %f';
        array_push($values, $boundingBox->south, $boundingBox->north);
        $where[] = 'longitude BETWEEN %f AND %f';
        array_push($values, $boundingBox->west, $boundingBox->east);
    }

    private function distanceSql(Coordinates $origin, ?Distance $radius = null): string
    {
        global $wpdb;

        $earthRadius = $radius ? $radius->unit->earthRadius() : 6371.0088;

        return $wpdb->prepare(
            ', (%f * 2 * ASIN(SQRT(POWER(SIN(RADIANS(latitude - %f) / 2), 2) + COS(RADIANS(%f)) * COS(RADIANS(latitude)) * POWER(SIN(RADIANS(longitude - %f) / 2), 2)))) AS distance',
            $earthRadius,
            $origin->latitude,
            $origin->latitude,
            $origin->longitude
        );
    }

    /**
     * @param list<string> $where
     * @param list<mixed> $values
     */
    private function appendPublicFilters(array &$where, array &$values, string $category, string $city, string $search): void
    {
        global $wpdb;

        if ($category !== '') {
            $where[] = 'category_slug = %s';
            $values[] = $category;
        }

        if ($city !== '') {
            $where[] = 'city_slug = %s';
            $values[] = $city;
        }

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(search_text LIKE %s OR title LIKE %s OR address LIKE %s)';
            array_push($values, $like, $like, $like);
        }
    }

    private function orderSql(SearchLocationsQuery $query): string
    {
        if ($query->orderby === 'distance' && $query->origin) {
            return 'ORDER BY distance ASC, title ASC';
        }

        $columns = [
            'title' => 'title',
            'date' => 'post_id',
            'modified' => 'updated_at',
            'menu_order' => 'title',
        ];
        $column = $columns[$query->orderby] ?? 'title';

        return sprintf('ORDER BY %s %s', $column, $query->order);
    }

    /**
     * @param list<mixed> $values
     */
    private function prepare(string $sql, array $values): string
    {
        global $wpdb;

        if ($values === []) {
            return $sql;
        }

        return $wpdb->prepare($sql, $values);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function rowToLocation(array $row, bool $includeImage): array
    {
        return [
            'id' => (int) $row['post_id'],
            'title' => (string) $row['title'],
            'excerpt' => (string) ($row['excerpt'] ?? ''),
            'address' => (string) $row['address'],
            'region' => (string) $row['region'],
            'country' => (string) $row['country'],
            'postcode' => (string) $row['postcode'],
            'lat' => (float) $row['latitude'],
            'lng' => (float) $row['longitude'],
            'phone' => (string) $row['phone'],
            'email' => (string) ($row['email'] ?? ''),
            'website' => (string) ($row['website'] ?? ''),
            'hours' => (string) ($row['hours'] ?? ''),
            'operational_status' => (string) ($row['operational_status'] ?? 'active'),
            'services' => [],
            'category' => $row['category'] !== '' ? ['name' => (string) $row['category'], 'slug' => (string) $row['category_slug']] : null,
            'city' => $row['city'] !== '' ? ['name' => (string) $row['city'], 'slug' => (string) $row['city_slug']] : null,
            'image' => $includeImage ? $this->imageUrl($row) : '',
            'distance' => isset($row['distance']) ? round((float) $row['distance'], 3) : null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function imageUrl(array $row): string
    {
        $imageId = (int) ($row['image_id'] ?? 0);
        if ($imageId > 0) {
            return (string) (wp_get_attachment_image_url($imageId, 'medium') ?: '');
        }

        return (string) (get_the_post_thumbnail_url((int) $row['post_id'], 'medium') ?: '');
    }
}
