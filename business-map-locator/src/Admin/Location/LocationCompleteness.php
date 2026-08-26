<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Location;

if (!defined('ABSPATH')) {
    exit;
}

final class LocationCompleteness
{
    /** @param array<string,mixed> $data */
    public function calculate(array $data): array
    {
        $meta = (array) ($data['meta'] ?? []);
        $checks = [
            'name' => [__('Location name', 'business-map-locator'), trim((string) ($data['title'] ?? '')) !== ''],
            'category' => [__('Category', 'business-map-locator'), !empty($data['category_ids'])],
            'city' => [__('City', 'business-map-locator'), !empty($data['city_ids'])],
            'address' => [__('Address', 'business-map-locator'), trim((string) ($meta['address'] ?? '')) !== ''],
            'coordinates' => [__('Map position', 'business-map-locator'), is_numeric($meta['lat'] ?? null) && is_numeric($meta['lng'] ?? null)],
            'phone' => [__('Phone', 'business-map-locator'), trim((string) ($meta['phone'] ?? '')) !== ''],
        ];

        $completed = 0;
        foreach ($checks as $check) {
            if ($check[1]) {
                $completed++;
            }
        }

        return [
            'percent' => (int) round(($completed / count($checks)) * 100),
            'checks' => $checks,
        ];
    }
}
