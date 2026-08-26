<?php
if (!defined('ABSPATH')) { exit; }

final class BML_GoogleMaps_Provider implements BML_Map_Provider_Interface {
    public function get_id(): string {
        return 'google';
    }

    public function is_configured(array $settings): bool {
        return $this->has_valid_api_key($settings);
    }

    public function register_assets(array $settings): void {
        wp_register_script(
            'bml-provider-google',
            BML_URL . 'assets/js/providers/google-maps-provider.js',
            ['bml-provider-base'],
            BML_VERSION,
            true
        );
    }

    public function enqueue_assets(array $settings): void {
        BML_Assets::register_map_assets();

        wp_enqueue_script('bml-provider-google');

        wp_enqueue_style('bml-leaflet');
        wp_enqueue_style('bml-markercluster');
        wp_enqueue_style('bml-markercluster-default');
        wp_enqueue_script('bml-markercluster');
        wp_enqueue_script('bml-provider-osm');
    }

    public function get_client_config(array $settings): array {
        $key = $this->normalize_api_key((string) ($settings['google_key'] ?? ''));

        return [
            'id'               => $this->get_id(),
            'configured'       => $this->is_configured($settings),
            'apiKey'           => $key,
            'apiUrl'           => 'https://maps.googleapis.com/maps/api/js',
            'fallbackProvider' => 'osm',
            'fallbackConfig'   => (new BML_OpenStreetMap_Provider())->get_client_config($settings),
            'center'           => [
                'lat' => (float) ($settings['center_lat'] ?? 0),
                'lng' => (float) ($settings['center_lng'] ?? 0),
            ],
            'zoom'             => (int) ($settings['zoom'] ?? 11),
            'cluster'          => !empty($settings['cluster']),
            'errors'           => [
                'missingApiKey'  => __('Google Maps API key is missing.', 'business-map-locator'),
                'invalidApiKey'  => __('Google Maps API key is invalid or restricted.', 'business-map-locator'),
                'billingDisabled'=> __('Google Maps billing is disabled for this project.', 'business-map-locator'),
                'loadFailed'     => __('Google Maps could not be loaded.', 'business-map-locator'),
            ],
        ];
    }

    public function get_health(array $settings): array {
        $key = $this->normalize_api_key((string) ($settings['google_key'] ?? ''));

        if ($key === '') {
            return [
                'healthy' => false,
                'code' => 'missing_api_key',
                'message' => 'Missing API key.',
            ];
        }

        if (!$this->looks_like_api_key($key)) {
            return [
                'healthy' => false,
                'code' => 'invalid_api_key_format',
                'message' => 'The API key format does not look valid.',
            ];
        }

        return [
            'healthy' => true,
            'code' => 'ready',
            'message' => 'Google Maps is configured. Runtime API errors are handled in the browser with OpenStreetMap fallback.',
        ];
    }

    private function has_valid_api_key(array $settings): bool {
        $key = $this->normalize_api_key((string) ($settings['google_key'] ?? ''));
        return $key !== '' && $this->looks_like_api_key($key);
    }

    private function normalize_api_key(string $key): string {
        return trim($key);
    }

    private function looks_like_api_key(string $key): bool {
        return (bool) preg_match('/^[A-Za-z0-9_\\-]{20,}$/', $key);
    }
}
