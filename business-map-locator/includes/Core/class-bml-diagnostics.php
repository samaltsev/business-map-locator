<?php
if (!defined('ABSPATH')) { exit; }

final class BML_Diagnostics {
    public static function get(): array {
        $settings = BML_Plugin::settings();
        $vendor = BML_Assets::local_vendor_status();
        $all_local = !in_array(false, $vendor, true);
        $registry = new BML_Provider_Registry();
        $active = $registry->get_active($settings);
        return [
            'version' => BML_VERSION,
            'provider' => $active->get_id(),
            'requested_provider' => $settings['provider'],
            'provider_detection' => $registry->detect_provider($settings),
            'provider_statuses' => $registry->get_statuses($settings),
            'provider_health' => $registry->get_health($settings),
            'google_key_present' => !empty($settings['google_key']),
            'local_vendor' => $vendor,
            'all_vendor_local' => $all_local,
            'rest_url' => rest_url('business-map/v1/health'),
            'php' => PHP_VERSION,
            'wordpress' => get_bloginfo('version'),
        ];
    }
}
