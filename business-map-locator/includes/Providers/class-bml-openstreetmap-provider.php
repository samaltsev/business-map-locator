<?php
if (!defined('ABSPATH')) { exit; }

final class BML_OpenStreetMap_Provider implements BML_Map_Provider_Interface {
    public function get_id(): string {
        return 'osm';
    }

    public function is_configured(array $settings): bool {
        return !empty($settings['tile_url']);
    }

    public function get_health(array $settings): array {
        $tile_url = trim((string) ($settings['tile_url'] ?? ''));

        if ($tile_url === '') {
            return [
                'healthy' => false,
                'code' => 'missing_tile_url',
                'message' => 'OpenStreetMap tile URL is missing.',
            ];
        }

        if (strpos($tile_url, '{z}') === false || strpos($tile_url, '{x}') === false || strpos($tile_url, '{y}') === false) {
            return [
                'healthy' => false,
                'code' => 'invalid_tile_url',
                'message' => 'OpenStreetMap tile URL must contain {z}, {x} and {y} placeholders.',
            ];
        }

        return [
            'healthy' => true,
            'code' => 'ready',
            'message' => 'OpenStreetMap is ready.',
        ];
    }

    public function register_assets(array $settings): void {
        BML_Assets::register_map_assets();

        wp_register_script(
            'bml-provider-osm',
            BML_URL . 'assets/js/providers/openstreetmap-provider.js',
            ['bml-provider-base', 'bml-leaflet', 'bml-markercluster'],
            BML_VERSION,
            true
        );
    }

    public function enqueue_assets(array $settings): void {
        wp_enqueue_style('bml-leaflet');

        if (!empty($settings['cluster'])) {
            wp_enqueue_style('bml-markercluster');
            wp_enqueue_style('bml-markercluster-default');
            wp_enqueue_script('bml-markercluster');
        } else {
            wp_enqueue_script('bml-leaflet');
        }

        wp_enqueue_script('bml-provider-osm');
    }

    public function get_client_config(array $settings): array {
        return [
            'id'          => $this->get_id(),
            'tileUrl'     => (string) ($settings['tile_url'] ?? ''),
            'attribution' => (string) ($settings['attribution'] ?? ''),
            'center'      => [
                'lat' => (float) ($settings['center_lat'] ?? 0),
                'lng' => (float) ($settings['center_lng'] ?? 0),
            ],
            'zoom'        => (int) ($settings['zoom'] ?? 11),
            'cluster'     => !empty($settings['cluster']),
        ];
    }
}
