<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Notice;

use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) { exit; }
final class AdminNotices
{
    public function diagnostics(): void {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== 'bml-providers' || !current_user_can(Capabilities::VIEW_DIAGNOSTICS)) {
            return;
        }
        $diagnostics = \BML_Diagnostics::get();
        if (!$diagnostics['all_vendor_local']) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Local map vendor files are incomplete. Copy the official Leaflet and MarkerCluster files into assets/vendor; external CDN assets are not loaded automatically.', 'business-map-locator') . '</p></div>';
        }
        if ($diagnostics['provider'] === 'google' && !$diagnostics['google_key_present']) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Google Maps is selected but no API key is configured.', 'business-map-locator') . '</p></div>';
        }
    }
    public function general(): void {
        $notice = $_GET['bml_notice'] ?? ($_GET['bml_general'] ?? '');
        if ($notice === '') {
            return;
        }

        $key = sanitize_key(wp_unslash($notice));
        $messages = [
            'location-saved' => __('Location saved.', 'business-map-locator'),
            'location-deleted' => __('Location deleted.', 'business-map-locator'),
            'location-duplicated' => __('Location duplicated as draft.', 'business-map-locator'),
            'bulk-complete' => __('Bulk action completed.', 'business-map-locator'),
            'term-saved' => __('Item saved.', 'business-map-locator'),
            'term-deleted' => __('Item deleted.', 'business-map-locator'),
            'term-duplicated' => __('Category duplicated.', 'business-map-locator'),
            'settings-saved' => __('Settings saved.', 'business-map-locator'),
            'demo-installed' => __('Demo data installed.', 'business-map-locator'),
            'import-error' => __('CSV upload failed.', 'business-map-locator'),
            'imported' => sprintf(
                __('Import completed: %1$d imported, %2$d errors.', 'business-map-locator'),
                absint(wp_unslash($_GET['count'] ?? 0)),
                absint(wp_unslash($_GET['errors'] ?? 0))
            ),
        ];
        if (!isset($messages[$key])) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$key]) . '</p></div>';
    }
}
