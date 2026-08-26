<?php
declare(strict_types=1);

namespace BusinessMapLocator\Settings;

final class Settings
{
    public const DEFAULT_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'provider' => 'osm',
            'tile_url' => self::DEFAULT_TILE_URL,
            'attribution' => '&copy; OpenStreetMap contributors',
            'center_lat' => '53.9006',
            'center_lng' => '27.5590',
            'zoom' => 11,
            'min_zoom' => 3,
            'max_zoom' => 19,
            'map_language' => 'auto',
            'google_key' => '',
            'map_style' => 'streets',
            'marker_color' => '#2876f0',
            'map_height' => 620,
            'show_search' => 1,
            'show_filters' => 1,
            'show_geolocation' => 1,
            'show_address' => 1,
            'show_phone' => 1,
            'show_navigation' => 1,
            'cluster' => 1,
            'layout' => 'split',
            'list_width' => 38,
            'per_page' => 200,
            'radius' => 25,
            'distance_unit' => 'km',
            'cache_ttl' => DAY_IN_SECONDS,
            'rest_cache' => 0,
            'import_logging' => 1,
            'delete_data' => 0,
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $settings = wp_parse_args((array) get_option('bml_settings', []), $this->defaults());
        $settings['tile_url'] = $this->sanitizeTileUrl(
            $settings['tile_url'] ?? self::DEFAULT_TILE_URL
        );

        return $settings;
    }

    public function installDefaults(): void
    {
        add_option('bml_settings', $this->defaults());
    }

    public function sanitizeTileUrl(mixed $value): string
    {
        $value = trim((string) $value);
        $value = wp_strip_all_tags($value);
        $value = (string) preg_replace('/[\x00-\x1F\x7F\s]+/', '', $value);

        if ($value === '' || !preg_match('#^https?://#i', $value)) {
            return self::DEFAULT_TILE_URL;
        }

        foreach (['{z}', '{x}', '{y}'] as $token) {
            if (!str_contains($value, $token)) {
                return self::DEFAULT_TILE_URL;
            }
        }

        $probe = strtr($value, [
            '{s}' => 'a',
            '{z}' => '0',
            '{x}' => '0',
            '{y}' => '0',
            '{r}' => '',
        ]);
        $probe = esc_url_raw($probe, ['http', 'https']);
        $host = wp_parse_url($probe, PHP_URL_HOST);

        return $probe && $host ? $value : self::DEFAULT_TILE_URL;
    }
}
