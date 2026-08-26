<?php
declare(strict_types=1);

namespace BusinessMapLocator\WordPress;

final class PrivacyPolicy
{
    public function register(): void
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = '<p>' . esc_html__(
            'Business Map Locator can load map tiles from OpenStreetMap and can send address or coordinate queries to the Nominatim geocoding service when an administrator explicitly uses address search. The optional visitor geolocation feature asks for browser permission and is not stored by the plugin.',
            'business-map-locator'
        ) . '</p>';

        wp_add_privacy_policy_content(
            'Business Map Locator',
            wp_kses_post($content)
        );
    }
}
