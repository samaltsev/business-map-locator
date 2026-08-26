<?php
if (!defined('ABSPATH')) { exit; }

final class BML_Locator_Renderer {
    public static function render(array $attributes = []): string {
        BML_Frontend::enqueue();

        return self::template('locator.php', self::context($attributes));
    }

    private static function context(array $attributes): array {
        $settings = BML_Frontend::settings();
        $attributes = wp_parse_args($attributes, [
            'layout' => 'split',
            'category' => '',
            'city' => '',
            'category_mode' => 'visible',
            'city_mode' => 'visible',
            'height' => $settings['map_height'] ?? 620,
            'list_width' => $settings['list_width'] ?? 38,
            'per_page' => 24,
            'search' => $settings['show_search'] ?? 1,
            'filters' => $settings['show_filters'] ?? 1,
            'geolocation' => $settings['show_geolocation'] ?? 1,
            'show_address' => $settings['show_address'] ?? 1,
            'show_phone' => $settings['show_phone'] ?? 1,
            'show_navigation' => $settings['show_navigation'] ?? 1,
            'preview_mode' => false,
            'load_all' => true,
        ]);

        $layout = in_array($attributes['layout'], ['split', 'map', 'cards'], true) ? $attributes['layout'] : 'split';

        $category = sanitize_title((string) $attributes['category']);
        $city = sanitize_title((string) $attributes['city']);
        $categoryMode = self::filterMode($attributes['category_mode'] ?? 'visible');
        $cityMode = self::filterMode($attributes['city_mode'] ?? 'visible');
        $perPage = max(12, min(36, (int) $attributes['per_page']));

        return [
            'id' => 'bml-locator-' . wp_generate_uuid4(),
            'layout' => $layout,
            'category' => $category,
            'city' => $city,
            'category_mode' => $categoryMode,
            'city_mode' => $cityMode,
            'height' => max(300, min(1200, (int) $attributes['height'])),
            'list_width' => max(25, min(60, (int) $attributes['list_width'])),
            'search' => (bool) $attributes['search'],
            'filters' => (bool) $attributes['filters'],
            'geolocation' => (bool) $attributes['geolocation'],
            'preview_mode' => (bool) $attributes['preview_mode'],
            'settings' => [
                'layout' => $layout,
                'category' => $category,
                'city' => $city,
                'categoryMode' => $categoryMode,
                'cityMode' => $cityMode,
                'height' => max(300, min(1200, (int) $attributes['height'])),
                'listWidth' => max(25, min(60, (int) $attributes['list_width'])),
                'per_page' => $perPage,
                'search' => (bool) $attributes['search'],
                'filters' => (bool) $attributes['filters'],
                'geolocation' => (bool) $attributes['geolocation'],
                'showAddress' => (bool) $attributes['show_address'],
                'showPhone' => (bool) $attributes['show_phone'],
                'showNavigation' => (bool) $attributes['show_navigation'],
                'previewMode' => (bool) $attributes['preview_mode'],
                'loadAll' => $layout === 'split' ? (bool) $attributes['load_all'] : false,
            ],
        ];
    }

    private static function filterMode(mixed $value): string {
        $mode = strtolower(trim((string) $value));

        return in_array($mode, ['visible', 'locked', 'hidden'], true) ? $mode : 'visible';
    }

    public static function template(string $template, array $context = []): string {
        $path = BML_DIR . 'templates/frontend/' . ltrim($template, '/\\');

        if (!is_readable($path)) {
            return '';
        }

        ob_start();
        extract($context, EXTR_SKIP);
        include $path;
        return (string) ob_get_clean();
    }
}
