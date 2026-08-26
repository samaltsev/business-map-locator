<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Settings;

use BusinessMapLocator\Admin\Shared\AdminShell;
use BusinessMapLocator\Admin\Settings\View\SettingsRenderer;

if (!defined('ABSPATH')) { exit; }

final class SettingsPage
{
    public function __construct(private AdminShell $shell, private SettingsRenderer $renderer) {}

    public function render(): void
    {
        $settings = \BML_Plugin::settings();
        $requested = sanitize_key(wp_unslash($_GET['tab'] ?? 'studio'));
        $legacy = ['setup'=>'studio','design'=>'studio','publish'=>'studio','providers'=>'studio','appearance'=>'studio','system'=>'advanced','performance'=>'advanced','general'=>'advanced'];
        $tab = $legacy[$requested] ?? $requested;
        if (!in_array($tab, ['studio', 'advanced'], true)) { $tab = 'studio'; }

        $counts = wp_count_posts('bml_location');
        $published = isset($counts->publish) ? (int) $counts->publish : 0;
        $context = [
            'published_locations' => $published,
            'provider_ready' => ($settings['provider'] ?? 'osm') === 'osm' || !empty($settings['google_key']),
            'rest_url' => rest_url('business-map/v1/locations'),
            'plugin_version' => defined('BML_VERSION') ? BML_VERSION : '',
            'last_saved' => (int) get_option('bml_settings_saved_at', 0),
        ];

        $this->shell->start(__('Settings', 'business-map-locator'), __('Configure the locator and preview the final visitor experience in one workspace.', 'business-map-locator'));
        $this->renderer->render($tab, $settings, $context);
        $this->shell->end();
    }
}
