<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Settings\Action;

use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) { exit; }
final class SaveSettingsAction
{
    public function __construct(private AdminActionResponder $responder, private AdminRequest $request)
    {
    }

    public function handle(): void {
        if (!current_user_can(Capabilities::MANAGE_SETTINGS)) { $this->responder->error(__('Insufficient permissions.', 'business-map-locator')); }
        $current = \BML_Plugin::settings();
        $section = sanitize_key($this->request->postString('section', 'system'));

        check_admin_referer('bml_save_settings');

        if ($section === 'providers') {
            $current['provider'] = $this->request->postString('provider') === 'google' ? 'google' : 'osm';
            $current['tile_url'] = \BML_Plugin::sanitize_tile_url($this->request->postRawString('tile_url', (string) $current['tile_url']));
            $current['attribution'] = $this->request->postString('attribution', (string) $current['attribution']);
            $current['center_lat'] = (string) max(-90, min(90, (float) $this->request->postRawString('center_lat', (string) $current['center_lat'])));
            $current['center_lng'] = (string) max(-180, min(180, (float) $this->request->postRawString('center_lng', (string) $current['center_lng'])));
            $current['zoom'] = max(1, min(20, $this->request->postInt('zoom', (int) $current['zoom'])));
            $current['min_zoom'] = max(1, min(20, $this->request->postInt('min_zoom', (int) ($current['min_zoom'] ?? 3))));
            $current['max_zoom'] = max($current['min_zoom'], min(20, $this->request->postInt('max_zoom', (int) ($current['max_zoom'] ?? 19))));
            $mapLanguage = sanitize_key($this->request->postString('map_language', (string) ($current['map_language'] ?? 'auto')));
            $current['map_language'] = in_array($mapLanguage, ['auto', 'en', 'de', 'uk', 'ru'], true) ? $mapLanguage : 'auto';
            $current['google_key'] = $this->request->postString('google_key', (string) $current['google_key']);
            $mapStyle = sanitize_key($this->request->postString('map_style'));
            $current['map_style'] = in_array($mapStyle, ['streets', 'soft', 'mono'], true) ? $mapStyle : 'streets';
            $markerColor = sanitize_hex_color($this->request->postString('marker_color', (string) ($current['marker_color'] ?? '#2876f0')));
            $current['marker_color'] = $markerColor ?: '#2876f0';
            $layout = sanitize_key($this->request->postString('layout', (string) ($current['layout'] ?? 'split')));
            $current['layout'] = in_array($layout, ['split','map','cards'], true) ? $layout : 'split';
            $current['map_height'] = max(300, min(1200, $this->request->postInt('map_height', (int) ($current['map_height'] ?? 680))));
            $current['list_width'] = max(25, min(60, $this->request->postInt('list_width', (int) ($current['list_width'] ?? 38))));
            $current['per_page'] = max(10, min(500, $this->request->postInt('per_page', (int) ($current['per_page'] ?? 200))));
            $current['radius'] = max(1, min(500, $this->request->postInt('radius', (int) ($current['radius'] ?? 25))));
            foreach (['show_search','show_filters','show_geolocation','show_address','show_phone','show_navigation','cluster'] as $key) { $current[$key] = $this->request->postBool($key) ? 1 : 0; }
            $tab = 'studio';
        } elseif ($section === 'appearance') {
            $layout = sanitize_key($this->request->postString('layout'));
            $current['layout'] = in_array($layout, ['split','map','cards'], true) ? $layout : 'split';
            $current['map_height'] = max(300, min(1200, $this->request->postInt('map_height', 620)));
            $current['list_width'] = max(25, min(60, $this->request->postInt('list_width', 38)));
            $current['per_page'] = max(10, min(500, $this->request->postInt('per_page', 200)));
            $current['radius'] = max(1, min(500, $this->request->postInt('radius', 25)));
            foreach (['show_search','show_filters','show_geolocation','show_address','show_phone','show_navigation','cluster'] as $key) { $current[$key] = $this->request->postBool($key) ? 1 : 0; }
            $tab = 'studio';
        } else {
            $current['cache_ttl'] = max(DAY_IN_SECONDS, $this->request->postInt('cache_ttl', DAY_IN_SECONDS));
            $current['rest_cache'] = $this->request->postBool('rest_cache') ? 1 : 0;
            $current['distance_unit'] = $this->request->postString('distance_unit', 'km') === 'mi' ? 'mi' : 'km';
            $current['import_logging'] = $this->request->postBool('import_logging') ? 1 : 0;
            $current['delete_data'] = $this->request->postBool('delete_data') ? 1 : 0;
            $tab = 'advanced';
        }
        update_option('bml_settings', $current);
        update_option('bml_settings_saved_at', time(), false);
        \BML_Location_Cache::invalidate();
        $this->responder->redirect('bml-settings', 'settings-saved', ['tab' => $tab]);
    }
}
