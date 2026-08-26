<?php
if (!defined('ABSPATH')) { exit; }

class BML_Shortcode {
    public function hooks(): void {
        add_shortcode('business_map_locator', [__CLASS__, 'render_locator']);
        add_shortcode('business_locator', [__CLASS__, 'render_locator']);
    }

    public static function render_locator($attributes = []): string {
        $settings = BML_Plugin::settings();
        $attributes = shortcode_atts([
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
        ], is_array($attributes) ? $attributes : [], 'business_map_locator');
        $attributes['category_mode'] = self::filter_mode($attributes['category_mode']);
        $attributes['city_mode'] = self::filter_mode($attributes['city_mode']);
        BML_Frontend::enqueue();
        return BML_Locator_Renderer::render($attributes);
    }

    private static function filter_mode($value): string {
        $mode = strtolower(trim((string) $value));
        return in_array($mode, ['visible', 'locked', 'hidden'], true) ? $mode : 'visible';
    }
}
