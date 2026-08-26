<?php
if (!defined('ABSPATH')) {
    exit;
}

class BML_REST {
    private BusinessMapLocator\Rest\LocationsController $locations_controller;

    public function __construct(BusinessMapLocator\Rest\LocationsController $locations_controller) {
        $this->locations_controller = $locations_controller;
    }

    public function hooks(): void {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void {
        $this->register_locations_routes();
        $this->register_filters_routes();
        $this->register_geocoding_routes();
        $this->register_health_routes();
    }

    private function register_locations_routes(): void {
        $this->locations_controller->registerRoutes();
    }

    private function register_filters_routes(): void {
        register_rest_route('business-map/v1', '/filters', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'filters'],
            'permission_callback' => '__return_true',
            'args' => [
                'category' => ['sanitize_callback' => 'sanitize_title'],
                'city' => ['sanitize_callback' => 'sanitize_title'],
            ],
        ]);
    }

    private function register_geocoding_routes(): void {
        register_rest_route('business-map/v1', '/geocode/search', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'geocode_search'],
            'permission_callback' => static function () { return current_user_can(\BusinessMapLocator\WordPress\Capabilities::EDIT_LOCATIONS); },
            'args' => [
                'q' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'city' => ['sanitize_callback' => 'sanitize_text_field'],
                'region' => ['sanitize_callback' => 'sanitize_text_field'],
                'country' => ['sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);
        register_rest_route('business-map/v1', '/geocode/reverse', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'geocode_reverse'],
            'permission_callback' => static function () { return current_user_can(\BusinessMapLocator\WordPress\Capabilities::EDIT_LOCATIONS); },
            'args' => [
                'lat' => ['required' => true, 'validate_callback' => static function ($v) { return is_numeric($v) && $v >= -90 && $v <= 90; }],
                'lng' => ['required' => true, 'validate_callback' => static function ($v) { return is_numeric($v) && $v >= -180 && $v <= 180; }],
            ],
        ]);
    }

    private function register_health_routes(): void {
        register_rest_route('business-map/v1', '/health', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'publicHealth'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('business-map/v1', '/diagnostics', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'diagnostics'],
            'permission_callback' => static function (): bool {
                return current_user_can(\BusinessMapLocator\WordPress\Capabilities::VIEW_DIAGNOSTICS);
            },
        ]);
    }

    /*
     * Filters
     */
    public function filters(WP_REST_Request $request): WP_REST_Response {
        $category = sanitize_title((string) $request->get_param('category'));
        $city = sanitize_title((string) $request->get_param('city'));
        $params = [
            'category' => $category,
            'city' => $city,
            'hide_empty' => true,
        ];
        $cached = BML_Location_Cache::get('filters', $params);
        if ($cached !== false) {
            return rest_ensure_response($cached);
        }

        $payload = [
            'categories' => $this->filter_terms_from_index('category', $city),
            'cities' => $this->filter_terms_from_index('city', $category),
        ];
        BML_Location_Cache::set('filters', $params, $payload);
        return rest_ensure_response($payload);
    }

    private function filter_terms_from_index(string $type, string $related_slug): array {
        global $wpdb;

        $table = BML_Database::locations_index_table();
        if (!BML_Database::table_exists($table)) {
            return [];
        }

        $name_column = $type === 'city' ? 'city' : 'category';
        $slug_column = $type === 'city' ? 'city_slug' : 'category_slug';
        $related_column = $type === 'city' ? 'category_slug' : 'city_slug';
        $where = [
            "visibility = 'public'",
            "operational_status <> 'hidden'",
            'latitude IS NOT NULL',
            'longitude IS NOT NULL',
            "{$name_column} <> ''",
            "{$slug_column} <> ''",
        ];
        $values = [];

        if ($related_slug !== '') {
            $where[] = "{$related_column} = %s";
            $values[] = $related_slug;
        }

        $sql = "SELECT {$name_column} AS name, {$slug_column} AS slug, COUNT(1) AS count
            FROM {$table}
            WHERE " . implode(' AND ', $where) . "
            GROUP BY {$slug_column}, {$name_column}
            ORDER BY {$name_column} ASC";

        if ($values !== []) {
            $sql = $wpdb->prepare($sql, $values);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'count' => (int) $row['count'],
            ];
        }, $rows);
    }

    /*
     * Geocoding
     */
    public function geocode_search(WP_REST_Request $request) {
        $q = trim((string) $request->get_param('q'));
        if (mb_strlen($q) < 3) {
            return new WP_Error('bml_short_query', __('Enter at least three characters.', 'business-map-locator'), ['status' => 400]);
        }

        if (preg_match('/^\s*(-?\d{1,2}(?:[.,]\d+)?)\s*[,; ]\s*(-?\d{1,3}(?:[.,]\d+)?)\s*$/u', $q, $matches)) {
            $lat = (float) str_replace(',', '.', $matches[1]);
            $lng = (float) str_replace(',', '.', $matches[2]);
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return rest_ensure_response([[
                    'display_name' => sprintf(__('Coordinates: %1$s, %2$s', 'business-map-locator'), $lat, $lng),
                    'lat' => $lat,
                    'lng' => $lng,
                    'type' => 'coordinates',
                    'address' => [],
                ]]);
            }
        }

        $city = trim((string) $request->get_param('city'));
        $region = trim((string) $request->get_param('region'));
        $country = trim((string) $request->get_param('country'));
        $context = array_values(array_filter([$city, $region, $country]));
        $search_query = $q;

        foreach ($context as $part) {
            if (mb_stripos($search_query, $part) === false) {
                $search_query .= ', ' . $part;
            }
        }

        $key = 'bml_geo_' . md5('search|' . mb_strtolower($search_query));
        $cached = get_transient($key);
        if ($cached !== false) {
            return rest_ensure_response($cached);
        }
        if (get_transient('bml_nominatim_lock')) {
            return new WP_Error('bml_rate_limit', __('Please wait a second before another address search.', 'business-map-locator'), ['status' => 429]);
        }
        set_transient('bml_nominatim_lock', 1, 1);

        $url = add_query_arg([
            'q' => $search_query,
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'namedetails' => 1,
            'dedupe' => 1,
            'limit' => 8,
        ], 'https://nominatim.openstreetmap.org/search');

        $response = wp_remote_get($url, [
            'timeout' => 12,
            'headers' => [
                'User-Agent' => 'Business Map Locator/' . BML_VERSION . ' ' . home_url('/'),
                'Accept-Language' => str_replace('_', '-', get_locale()),
            ],
        ]);
        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return new WP_Error('bml_geocode_http_error', __('The address service returned an error.', 'business-map-locator'), ['status' => 502]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            return new WP_Error('bml_geocode_invalid_response', __('The address service returned an invalid response.', 'business-map-locator'), ['status' => 502]);
        }

        $items = [];
        $seen = [];
        foreach ($body as $item) {
            $lat = (float) ($item['lat'] ?? 0);
            $lng = (float) ($item['lon'] ?? 0);
            if (!$lat && !$lng) {
                continue;
            }

            $identity = round($lat, 6) . '|' . round($lng, 6);
            if (isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = true;

            $items[] = [
                'display_name' => sanitize_text_field($item['display_name'] ?? ''),
                'lat' => $lat,
                'lng' => $lng,
                'type' => sanitize_key((string) ($item['type'] ?? 'place')),
                'importance' => (float) ($item['importance'] ?? 0),
                'address' => $this->map_address((array) ($item['address'] ?? [])),
            ];
        }

        set_transient($key, $items, DAY_IN_SECONDS);
        return rest_ensure_response($items);
    }

    public function geocode_reverse(WP_REST_Request $request) {
        $lat = round((float) $request->get_param('lat'), 6);
        $lng = round((float) $request->get_param('lng'), 6);
        $key = 'bml_geo_' . md5('reverse|' . $lat . '|' . $lng);
        $cached = get_transient($key);
        if ($cached !== false) {
            return rest_ensure_response($cached);
        }
        if (get_transient('bml_nominatim_lock')) {
            return new WP_Error('bml_rate_limit', __('Please wait a second before another address request.', 'business-map-locator'), ['status' => 429]);
        }
        set_transient('bml_nominatim_lock', 1, 1);
        $url = add_query_arg(['lat' => $lat, 'lon' => $lng, 'format' => 'jsonv2', 'addressdetails' => 1], 'https://nominatim.openstreetmap.org/reverse');
        $response = wp_remote_get($url, [
            'timeout' => 12,
            'headers' => [
                'User-Agent' => 'Business Map Locator/' . BML_VERSION . ' ' . home_url('/'),
                'Accept-Language' => get_locale(),
            ],
        ]);
        if (is_wp_error($response)) {
            return $response;
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $data = [
            'display_name' => sanitize_text_field($body['display_name'] ?? ''),
            'address' => $this->map_address((array) ($body['address'] ?? [])),
        ];
        set_transient($key, $data, DAY_IN_SECONDS);
        return rest_ensure_response($data);
    }

    private function map_address(array $a): array {
        $road = $a['road'] ?? ($a['pedestrian'] ?? ($a['footway'] ?? ''));
        $house = $a['house_number'] ?? '';
        return [
            'address' => trim($road . ($house ? ' ' . $house : '')),
            'city' => $a['city'] ?? ($a['town'] ?? ($a['village'] ?? ($a['municipality'] ?? ''))),
            'region' => $a['state'] ?? ($a['region'] ?? ''),
            'country' => $a['country'] ?? '',
            'postcode' => $a['postcode'] ?? '',
        ];
    }

    /*
     * Health
     */
    public function publicHealth(): WP_REST_Response {
        return rest_ensure_response([
            'status' => 'ok',
        ]);
    }

    public function diagnostics(): WP_REST_Response {
        $settings = BML_Plugin::settings();
        return rest_ensure_response(array_merge([
            'status' => 'ok',
            'provider' => $settings['provider'],
            'google_configured' => !empty($settings['google_key']),
            'rest_cache_enabled' => !empty($settings['rest_cache']),
            'cache_ttl' => (int) $settings['cache_ttl'],
            'version' => BML_VERSION,
        ], BML_Diagnostics::get()));
    }
}
