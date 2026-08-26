<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Settings\View;

if (!defined('ABSPATH')) { exit; }

final class SettingsRenderer
{
    public function render(string $tab, array $s, array $context = []): void
    {
        $tab = $tab === 'advanced' ? 'advanced' : 'studio';
        ?>
        <div class="bml-settings-studio bml-settings-studio--fullscreen bml-settings-studio--v2 bml-settings-studio--v3" data-bml-settings-studio data-published-count="<?php echo esc_attr((string) ($context['published_locations'] ?? 0)); ?>">
            <header class="bml-settings-studio__header bml-studio-v2__topbar">
                <div class="bml-studio-v2__brandline">
                    <a class="bml-studio-v2__back" href="<?php echo esc_url(admin_url('admin.php?page=business-map-locator')); ?>" aria-label="<?php esc_attr_e('Back to dashboard', 'business-map-locator'); ?>">←</a>
                    <span class="bml-studio-v2__logo" aria-hidden="true">BML</span>
                    <div class="bml-studio-v2__product">
                        <strong><?php esc_html_e('Business Map Locator', 'business-map-locator'); ?></strong>
                        <span>/</span>
                        <span><?php esc_html_e('Locator Studio', 'business-map-locator'); ?></span>
                    </div>
                </div>

                <div class="bml-studio-v2__topbar-actions">
                    <span class="bml-settings-studio__save-state" data-save-state><i></i><span><?php esc_html_e('Saved', 'business-map-locator'); ?></span></span>
                    <button class="bml-settings-studio__button bml-settings-studio__button--ghost bml-settings-studio__collapse" type="button" data-collapse-inspector aria-expanded="true" aria-label="<?php esc_attr_e('Collapse settings panel', 'business-map-locator'); ?>"><span aria-hidden="true">⇤</span></button>
                    <button class="bml-settings-studio__button bml-settings-studio__button--ghost bml-studio-v2__undo" type="button" data-reset-form><span aria-hidden="true">↶</span><?php esc_html_e('Undo', 'business-map-locator'); ?></button>
                    <a class="bml-settings-studio__button bml-settings-studio__button--secondary" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Preview site', 'business-map-locator'); ?> ↗</a>
                    <?php if ($tab === 'studio') : ?>
                        <button class="bml-settings-studio__button bml-settings-studio__button--primary" type="button" data-submit-settings><?php esc_html_e('Save changes', 'business-map-locator'); ?></button>
                    <?php endif; ?>
                </div>
            </header>

            <?php $tab === 'studio' ? $this->studio($s, $context) : $this->advanced($s, $context); ?>
        </div>
        <?php
    }

    private function formStart(string $section): void
    {
        ?>
        <form class="bml-settings-studio__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="bml_save_settings">
            <input type="hidden" name="section" value="<?php echo esc_attr($section); ?>">
            <?php wp_nonce_field('bml_save_settings'); ?>
        <?php
    }

    private function studio(array $s, array $context): void
    {
        $provider = ($s['provider'] ?? 'osm') === 'google' ? 'google' : 'osm';
        $layout = in_array(($s['layout'] ?? 'split'), ['split', 'map', 'cards'], true) ? (string) $s['layout'] : 'split';
        $published = (int) ($context['published_locations'] ?? 0);
        $centerLat = (string) ($s['center_lat'] ?? '53.9006');
        $centerLng = (string) ($s['center_lng'] ?? '27.5590');
        $zoom = (int) ($s['zoom'] ?? 11);
        $minZoom = (int) ($s['min_zoom'] ?? 3);
        $maxZoom = (int) ($s['max_zoom'] ?? 19);
        $mapLanguage = (string) ($s['map_language'] ?? 'auto');
        $radius = (int) ($s['radius'] ?? 25);
        $this->formStart('providers');
        ?>
        <div class="bml-settings-studio__workspace bml-settings-studio__workspace--comfort bml-studio-v2__workspace">
            <aside class="bml-settings-studio__inspector bml-settings-studio__inspector--comfort bml-studio-v2__inspector" data-studio-inspector>
                <div class="bml-studio-v2__inspector-head">
                    <div>
                        <strong><?php esc_html_e('Customize locator', 'business-map-locator'); ?></strong>
                        <small><?php esc_html_e('Configure one part at a time. The live locator stays visible.', 'business-map-locator'); ?></small>
                    </div>
                </div>

                <div class="bml-studio-v3__inspector-body">
                <div class="bml-settings-studio__tabs bml-studio-v2__tabs" role="tablist" aria-label="<?php esc_attr_e('Locator settings sections', 'business-map-locator'); ?>">
                    <button type="button" class="is-active" role="tab" aria-selected="true" data-studio-tab="map"><span aria-hidden="true">⌖</span><b><?php esc_html_e('Map', 'business-map-locator'); ?></b></button>
                    <button type="button" role="tab" aria-selected="false" data-studio-tab="design"><span aria-hidden="true">◩</span><b><?php esc_html_e('Layout', 'business-map-locator'); ?></b></button>
                    <button type="button" role="tab" aria-selected="false" data-studio-tab="filters"><span aria-hidden="true">⌕</span><b><?php esc_html_e('Filters', 'business-map-locator'); ?></b></button>
                    <button type="button" role="tab" aria-selected="false" data-studio-tab="cards"><span aria-hidden="true">▤</span><b><?php esc_html_e('Cards', 'business-map-locator'); ?></b></button>
                    <button type="button" role="tab" aria-selected="false" data-studio-tab="publish"><span aria-hidden="true">↥</span><b><?php esc_html_e('Publish', 'business-map-locator'); ?></b></button>
                </div>

                <div class="bml-settings-studio__tab-panel is-active" role="tabpanel" data-studio-panel="map">
                    <section class="bml-settings-studio__section">
                        <div class="bml-settings-studio__section-heading">
                            <h2><?php esc_html_e('Map provider', 'business-map-locator'); ?></h2>
                            <p><?php esc_html_e('Choose the map service used by the locator.', 'business-map-locator'); ?></p>
                        </div>
                        <div class="bml-settings-studio__provider bml-studio-v2__provider" role="radiogroup" aria-label="<?php esc_attr_e('Map provider', 'business-map-locator'); ?>">
                            <label class="<?php echo $provider === 'osm' ? 'is-selected' : ''; ?>">
                                <input type="radio" name="provider" value="osm" <?php checked($provider, 'osm'); ?>>
                                <span class="bml-settings-studio__provider-icon is-osm">◈</span>
                                <span><strong>OpenStreetMap</strong><small><?php esc_html_e('Free · no API key', 'business-map-locator'); ?></small></span>
                                <i aria-hidden="true"></i>
                            </label>
                            <label class="<?php echo $provider === 'google' ? 'is-selected' : ''; ?>">
                                <input type="radio" name="provider" value="google" <?php checked($provider, 'google'); ?>>
                                <span class="bml-settings-studio__provider-icon is-google">G</span>
                                <span><strong>Google Maps</strong><small><?php esc_html_e('Bring your own API key', 'business-map-locator'); ?></small></span>
                                <i aria-hidden="true"></i>
                            </label>
                        </div>

                        <div class="bml-settings-studio__provider-panel" data-provider-panel="osm" <?php echo $provider !== 'osm' ? 'hidden' : ''; ?>>
                            <p class="bml-settings-studio__status is-success"><i></i><span><strong><?php esc_html_e('OpenStreetMap is ready', 'business-map-locator'); ?></strong><br><?php esc_html_e('Leaflet and clustering assets are loaded locally.', 'business-map-locator'); ?></span></p>
                        </div>
                        <div class="bml-settings-studio__provider-panel" data-provider-panel="google" <?php echo $provider !== 'google' ? 'hidden' : ''; ?>>
                            <label class="bml-settings-studio__field"><span><?php esc_html_e('Google Browser API key', 'business-map-locator'); ?></span><span class="bml-settings-studio__key-row"><input id="bml-google-key" name="google_key" type="password" autocomplete="off" value="<?php echo esc_attr((string) ($s['google_key'] ?? '')); ?>" placeholder="AIza..."><button type="button" data-toggle-key><?php esc_html_e('Show', 'business-map-locator'); ?></button></span></label>
                            <button class="bml-settings-studio__button bml-settings-studio__button--secondary bml-settings-studio__button--wide" type="button" data-test-google-key><?php esc_html_e('Test connection', 'business-map-locator'); ?></button>
                            <p class="bml-settings-studio__status is-idle" data-google-status><i></i><span><?php esc_html_e('The key has not been tested yet.', 'business-map-locator'); ?></span></p>
                        </div>
                    </section>

                    <section class="bml-settings-studio__section bml-settings-studio__section--location-search">
                        <div class="bml-settings-studio__section-heading">
                            <h2><?php esc_html_e('Initial map view', 'business-map-locator'); ?></h2>
                            <p><?php esc_html_e('Search for a place or use the current position from the preview.', 'business-map-locator'); ?></p>
                        </div>
                        <div class="bml-settings-studio__location-search bml-studio-v2__location-search">
                            <label class="bml-settings-studio__search-field" for="bml-center-search"><span class="screen-reader-text"><?php esc_html_e('Country, city or address', 'business-map-locator'); ?></span><span aria-hidden="true">⌕</span><input id="bml-center-search" type="search" autocomplete="off" placeholder="<?php esc_attr_e('For example: Helsinki, Finland', 'business-map-locator'); ?>" data-center-search><button type="button" data-clear-center-search aria-label="<?php esc_attr_e('Clear search', 'business-map-locator'); ?>">×</button></label>
                            <button class="bml-settings-studio__button bml-settings-studio__button--primary" type="button" data-find-center><?php esc_html_e('Find', 'business-map-locator'); ?></button>
                        </div>
                        <div class="bml-settings-studio__geocode-results" data-center-results hidden></div>
                        <p class="bml-settings-studio__status is-idle" data-center-status><i></i><span><?php esc_html_e('Type at least three characters and choose a result.', 'business-map-locator'); ?></span></p>
                        <div class="bml-settings-studio__center-summary bml-studio-v2__center-summary" data-center-summary>
                            <span class="bml-settings-studio__center-summary-icon">⌖</span>
                            <div><strong data-center-label><?php esc_html_e('Saved map center', 'business-map-locator'); ?></strong><code data-center-coordinates><?php echo esc_html($centerLat . ', ' . $centerLng); ?></code></div>
                            <button class="bml-studio-v2__text-button" type="button" data-use-map-center><?php esc_html_e('Use preview center', 'business-map-locator'); ?></button>
                        </div>
                        <input type="hidden" name="center_lat" value="<?php echo esc_attr($centerLat); ?>" data-center-lat>
                        <input type="hidden" name="center_lng" value="<?php echo esc_attr($centerLng); ?>" data-center-lng>

                        <label class="bml-settings-studio__field bml-settings-studio__zoom-field"><span><?php esc_html_e('Zoom level', 'business-map-locator'); ?> <output data-zoom-output><?php echo esc_html((string) $zoom); ?></output></span><input type="range" name="zoom" min="1" max="20" step="1" value="<?php echo esc_attr((string) $zoom); ?>" data-zoom-range></label>

                        <details class="bml-settings-studio__advanced-disclosure">
                            <summary><?php esc_html_e('Advanced map settings', 'business-map-locator'); ?></summary>
                            <div class="bml-settings-studio__advanced-content">
                                <div class="bml-settings-studio__two-columns">
                                    <label class="bml-settings-studio__field"><span><?php esc_html_e('Latitude', 'business-map-locator'); ?></span><input type="text" value="<?php echo esc_attr($centerLat); ?>" data-advanced-lat></label>
                                    <label class="bml-settings-studio__field"><span><?php esc_html_e('Longitude', 'business-map-locator'); ?></span><input type="text" value="<?php echo esc_attr($centerLng); ?>" data-advanced-lng></label>
                                </div>
                                <div class="bml-settings-studio__two-columns">
                                    <label class="bml-settings-studio__field"><span><?php esc_html_e('Minimum zoom', 'business-map-locator'); ?></span><input type="number" name="min_zoom" min="1" max="20" value="<?php echo esc_attr((string) $minZoom); ?>"></label>
                                    <label class="bml-settings-studio__field"><span><?php esc_html_e('Maximum zoom', 'business-map-locator'); ?></span><input type="number" name="max_zoom" min="1" max="20" value="<?php echo esc_attr((string) $maxZoom); ?>"></label>
                                </div>
                                <label class="bml-settings-studio__field"><span><?php esc_html_e('Map language', 'business-map-locator'); ?></span><select name="map_language"><option value="auto" <?php selected($mapLanguage, 'auto'); ?>><?php esc_html_e('Browser default', 'business-map-locator'); ?></option><option value="en" <?php selected($mapLanguage, 'en'); ?>>English</option><option value="de" <?php selected($mapLanguage, 'de'); ?>>Deutsch</option><option value="uk" <?php selected($mapLanguage, 'uk'); ?>>Українська</option><option value="ru" <?php selected($mapLanguage, 'ru'); ?>>Русский</option></select></label>
                                <a class="bml-settings-studio__advanced-link" href="<?php echo esc_url(admin_url('admin.php?page=bml-settings&tab=advanced')); ?>"><?php esc_html_e('Open system settings', 'business-map-locator'); ?> →</a>
                            </div>
                        </details>
                    </section>
                </div>

                <div class="bml-settings-studio__tab-panel" role="tabpanel" data-studio-panel="design" hidden>
                    <section class="bml-settings-studio__section">
                        <div class="bml-settings-studio__section-heading"><h2><?php esc_html_e('Layout', 'business-map-locator'); ?></h2><p><?php esc_html_e('Choose how the directory and map are arranged for visitors.', 'business-map-locator'); ?></p></div>
                        <div class="bml-settings-studio__layouts bml-studio-v2__layouts">
                            <?php foreach (['split' => [__('Split list + map', 'business-map-locator'), __('Balanced directory and map view', 'business-map-locator')], 'map' => [__('Map only', 'business-map-locator'), __('Map-first locator with marker popups', 'business-map-locator')], 'cards' => [__('Directory only', 'business-map-locator'), __('Card directory without the map', 'business-map-locator')]] as $value => $copy) : ?>
                                <label class="<?php echo $layout === $value ? 'is-selected' : ''; ?>">
                                    <input type="radio" name="layout" value="<?php echo esc_attr($value); ?>" <?php checked($layout, $value); ?>>
                                    <span class="bml-settings-studio__layout-sketch <?php echo $value === 'map' ? 'is-map' : ($value === 'cards' ? 'is-cards' : ''); ?>"><i></i><b></b></span>
                                    <span><strong><?php echo esc_html($copy[0]); ?></strong><small><?php echo esc_html($copy[1]); ?></small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="bml-settings-studio__section">
                        <div class="bml-settings-studio__section-heading"><h2><?php esc_html_e('Style', 'business-map-locator'); ?></h2><p><?php esc_html_e('Keep the main visual choices simple. Fine tuning stays in advanced settings.', 'business-map-locator'); ?></p></div>
                        <div class="bml-studio-v2__style-row">
                            <label class="bml-settings-studio__field"><span><?php esc_html_e('Marker color', 'business-map-locator'); ?></span><input type="color" name="marker_color" value="<?php echo esc_attr((string) ($s['marker_color'] ?? '#2876f0')); ?>" data-marker-color></label>
                            <label class="bml-settings-studio__field"><span><?php esc_html_e('Map height', 'business-map-locator'); ?> <output data-height-output><?php echo esc_html((string) ($s['map_height'] ?? 620)); ?> px</output></span><input type="range" name="map_height" min="420" max="900" step="20" value="<?php echo esc_attr((string) ($s['map_height'] ?? 620)); ?>" data-map-height></label>
                        </div>
                        <div class="bml-settings-studio__map-styles bml-studio-v2__map-styles">
                            <?php foreach (['streets' => __('Standard', 'business-map-locator'), 'soft' => __('Light', 'business-map-locator'), 'mono' => __('Monochrome', 'business-map-locator')] as $value => $label) : ?>
                                <label class="<?php echo ($s['map_style'] ?? 'streets') === $value ? 'is-selected' : ''; ?>"><input type="radio" name="map_style" data-map-style value="<?php echo esc_attr($value); ?>" <?php checked(($s['map_style'] ?? 'streets'), $value); ?>><span class="<?php echo $value === 'soft' ? 'is-soft' : ($value === 'mono' ? 'is-mono' : ''); ?>"></span><strong><?php echo esc_html($label); ?></strong></label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>

                <div class="bml-settings-studio__tab-panel" role="tabpanel" data-studio-panel="filters" hidden>
                    <section class="bml-settings-studio__section">
                        <div class="bml-settings-studio__section-heading"><h2><?php esc_html_e('Search & filters', 'business-map-locator'); ?></h2><p><?php esc_html_e('Choose which discovery tools visitors can use.', 'business-map-locator'); ?></p></div>
                        <div class="bml-settings-studio__switches bml-settings-studio__switches--cards bml-studio-v2__switches">
                            <label><span><strong><?php esc_html_e('Search', 'business-map-locator'); ?></strong><small><?php esc_html_e('Search locations by title or address.', 'business-map-locator'); ?></small></span><input type="checkbox" name="show_search" value="1" <?php checked(!empty($s['show_search'])); ?>></label>
                            <label><span><strong><?php esc_html_e('Category and city filters', 'business-map-locator'); ?></strong><small><?php esc_html_e('Show the current taxonomy filters.', 'business-map-locator'); ?></small></span><input type="checkbox" name="show_filters" value="1" <?php checked(!empty($s['show_filters'])); ?>></label>
                            <label><span><strong><?php esc_html_e('Near me', 'business-map-locator'); ?></strong><small><?php esc_html_e('Allow visitors to search around their location.', 'business-map-locator'); ?></small></span><input type="checkbox" name="show_geolocation" value="1" <?php checked(!empty($s['show_geolocation'])); ?>></label>
                            <label><span><strong><?php esc_html_e('Marker clustering', 'business-map-locator'); ?></strong><small><?php esc_html_e('Group nearby markers when the map is zoomed out.', 'business-map-locator'); ?></small></span><input type="checkbox" name="cluster" value="1" <?php checked(!empty($s['cluster'])); ?>></label>
                        </div>
                        <label class="bml-settings-studio__field bml-studio-v2__radius"><span><?php esc_html_e('Default radius', 'business-map-locator'); ?></span><div class="bml-studio-v2__input-suffix"><input type="number" name="radius" min="1" max="500" value="<?php echo esc_attr((string) $radius); ?>"><span><?php echo esc_html(($s['distance_unit'] ?? 'km') === 'mi' ? 'mi' : 'km'); ?></span></div></label>
                    </section>
                </div>

                <div class="bml-settings-studio__tab-panel" role="tabpanel" data-studio-panel="cards" hidden>
                    <section class="bml-settings-studio__section">
                        <div class="bml-settings-studio__section-heading"><h2><?php esc_html_e('Location card', 'business-map-locator'); ?></h2><p><?php esc_html_e('Choose what appears in compact location cards.', 'business-map-locator'); ?></p></div>
                        <div class="bml-settings-studio__switches bml-settings-studio__switches--cards bml-studio-v2__switches">
                            <label><span><strong><?php esc_html_e('Address', 'business-map-locator'); ?></strong><small><?php esc_html_e('Show the public street address.', 'business-map-locator'); ?></small></span><input type="checkbox" name="show_address" value="1" data-preview-toggle="address" <?php checked(!empty($s['show_address'])); ?>></label>
                            <label><span><strong><?php esc_html_e('Phone action', 'business-map-locator'); ?></strong><small><?php esc_html_e('Show the call action when a phone number exists.', 'business-map-locator'); ?></small></span><input type="checkbox" name="show_phone" value="1" data-preview-toggle="phone" <?php checked(!empty($s['show_phone'])); ?>></label>
                            <label><span><strong><?php esc_html_e('Directions action', 'business-map-locator'); ?></strong><small><?php esc_html_e('Show a navigation link to the location.', 'business-map-locator'); ?></small></span><input type="checkbox" name="show_navigation" value="1" data-preview-toggle="directions" <?php checked(!empty($s['show_navigation'])); ?>></label>
                        </div>
                    </section>
                </div>

                <div class="bml-settings-studio__tab-panel" role="tabpanel" data-studio-panel="publish" hidden>
                    <section class="bml-settings-studio__section">
                        <div class="bml-settings-studio__section-heading"><h2><?php esc_html_e('Publish your locator', 'business-map-locator'); ?></h2><p><?php esc_html_e('Use the shortcode or Gutenberg block anywhere on the site.', 'business-map-locator'); ?></p></div>
                        <section class="bml-settings-studio__publish-card bml-settings-studio__publish-card--panel bml-studio-v2__publish-card">
                            <div class="bml-settings-studio__publish-head"><strong><?php esc_html_e('Ready to publish', 'business-map-locator'); ?></strong><span><?php esc_html_e('Published', 'business-map-locator'); ?></span></div>
                            <label><?php esc_html_e('Shortcode', 'business-map-locator'); ?></label>
                            <div class="bml-settings-studio__copy-row"><code>[business_map_locator]</code><button type="button" data-copy-shortcode aria-label="<?php esc_attr_e('Copy shortcode', 'business-map-locator'); ?>">⧉</button></div>
                            <label><?php esc_html_e('Gutenberg block', 'business-map-locator'); ?></label>
                            <div class="bml-settings-studio__copy-row"><span>Business Map Locator</span><button type="button" data-copy-shortcode aria-label="<?php esc_attr_e('Copy shortcode', 'business-map-locator'); ?>">⧉</button></div>
                            <dl><div><dt><?php esc_html_e('Provider', 'business-map-locator'); ?></dt><dd data-provider-label><?php echo esc_html($provider === 'google' ? 'Google Maps' : 'OpenStreetMap'); ?></dd></div><div><dt><?php esc_html_e('Locations', 'business-map-locator'); ?></dt><dd><?php echo esc_html((string) $published); ?></dd></div><div><dt>REST API</dt><dd><?php esc_html_e('Active', 'business-map-locator'); ?></dd></div></dl>
                            <button class="bml-settings-studio__button bml-settings-studio__button--primary bml-settings-studio__button--wide" type="button" data-submit-settings><?php esc_html_e('Save and publish', 'business-map-locator'); ?></button>
                        </section>
                    </section>
                </div>
                </div><!-- /.bml-studio-v3__inspector-body -->
            </aside>

            <main class="bml-settings-studio__preview-panel bml-studio-v2__preview-panel" data-preview-mode="map">
                <div class="bml-studio-v2__preview-head bml-studio-v3__preview-head">
                    <div class="bml-studio-v3__preview-meta">
                        <div class="bml-studio-v3__preview-heading">
                            <strong><?php esc_html_e('Live locator', 'business-map-locator'); ?></strong>
                            <span class="bml-studio-v3__live-badge"><i></i><?php esc_html_e('Published', 'business-map-locator'); ?></span>
                        </div>
                        <span class="bml-studio-v3__preview-counts"><strong><?php echo esc_html((string) $published); ?></strong> <?php esc_html_e('locations', 'business-map-locator'); ?> · <strong data-loaded-count>0</strong> <?php esc_html_e('in viewport', 'business-map-locator'); ?></span>
                    </div>
                    <div class="bml-studio-v3__preview-controls">
                        <span><?php esc_html_e('Preview', 'business-map-locator'); ?></span>
                        <div class="bml-studio-v2__devices" aria-label="<?php esc_attr_e('Preview size', 'business-map-locator'); ?>">
                            <button type="button" class="is-active" data-preview-device="desktop" aria-pressed="true" title="<?php esc_attr_e('Desktop preview', 'business-map-locator'); ?>"><span aria-hidden="true">▰</span><b><?php esc_html_e('Desktop', 'business-map-locator'); ?></b></button>
                            <button type="button" data-preview-device="tablet" aria-pressed="false" title="<?php esc_attr_e('Tablet preview', 'business-map-locator'); ?>"><span aria-hidden="true">▯</span><b><?php esc_html_e('Tablet', 'business-map-locator'); ?></b></button>
                            <button type="button" data-preview-device="mobile" aria-pressed="false" title="<?php esc_attr_e('Mobile preview', 'business-map-locator'); ?>"><span aria-hidden="true">▯</span><b><?php esc_html_e('Mobile', 'business-map-locator'); ?></b></button>
                        </div>
                    </div>
                </div>

                <div class="bml-studio-v2__canvas bml-studio-v3__canvas" data-preview-canvas data-device="desktop">
                    <div class="bml-settings-studio__frontend-preview bml-studio-v2__locator" data-frontend-preview data-layout="<?php echo esc_attr($layout); ?>">
                        <div class="bml-settings-studio__frontend-toolbar bml-studio-v2__locator-toolbar">
                            <div class="bml-studio-v2__search"><span aria-hidden="true">⌕</span><input type="search" data-preview-search placeholder="<?php esc_attr_e('Search by title or address', 'business-map-locator'); ?>" disabled></div>
                            <select data-preview-categories disabled><option><?php esc_html_e('All categories', 'business-map-locator'); ?></option></select>
                            <select data-preview-cities disabled><option><?php esc_html_e('All cities', 'business-map-locator'); ?></option></select>
                            <button type="button" disabled><?php esc_html_e('Near me', 'business-map-locator'); ?></button>
                        </div>

                        <div class="bml-studio-v2__locator-main">
                            <div class="bml-settings-studio__map-shell bml-studio-v2__map-shell" data-preview-shell data-provider="<?php echo esc_attr($provider); ?>" data-style="<?php echo esc_attr((string) ($s['map_style'] ?? 'streets')); ?>" style="--bml-map-height:<?php echo esc_attr((string) ($s['map_height'] ?? 620)); ?>px;--bml-marker-color:<?php echo esc_attr((string) ($s['marker_color'] ?? '#2876f0')); ?>;">
                                <div id="bml-settings-map" class="bml-settings-studio__map" aria-label="<?php esc_attr_e('Map preview', 'business-map-locator'); ?>"></div>
                                <div id="bml-google-preview-map" class="bml-settings-studio__map" hidden aria-label="<?php esc_attr_e('Google Maps preview', 'business-map-locator'); ?>"></div>
                                <div class="bml-settings-studio__map-tools bml-studio-v2__map-tools" aria-label="<?php esc_attr_e('Map tools', 'business-map-locator'); ?>">
                                    <button type="button" data-use-map-center title="<?php esc_attr_e('Use map center', 'business-map-locator'); ?>"><span>⌖</span><b class="screen-reader-text"><?php esc_html_e('Use center', 'business-map-locator'); ?></b></button>
                                    <button type="button" data-reload-map title="<?php esc_attr_e('Refresh locations', 'business-map-locator'); ?>"><span>↻</span><b class="screen-reader-text"><?php esc_html_e('Refresh', 'business-map-locator'); ?></b></button>
                                    <button type="button" data-fit-all title="<?php esc_attr_e('Fit all locations', 'business-map-locator'); ?>"><span>⛶</span><b class="screen-reader-text"><?php esc_html_e('Show all', 'business-map-locator'); ?></b></button>
                                </div>
                                <div class="bml-settings-studio__loading bml-studio-v2__loading" data-map-loading data-loading-text="<?php esc_attr_e('Loading markers…', 'business-map-locator'); ?>" data-empty-text="<?php esc_attr_e('There are no markers in this viewport.', 'business-map-locator'); ?>" data-truncated-text="<?php esc_attr_e('Too many markers. Zoom in.', 'business-map-locator'); ?>" data-error-text="<?php esc_attr_e('Could not load markers.', 'business-map-locator'); ?>"><span class="spinner is-active"></span><strong><?php esc_html_e('Loading current viewport…', 'business-map-locator'); ?></strong><small data-loading-progress></small></div>
                            </div>

                            <section class="bml-studio-v2__directory">
                                <div class="bml-studio-v2__directory-head"><div><strong><?php esc_html_e('Location directory', 'business-map-locator'); ?></strong><small data-frontend-count><?php esc_html_e('Loading locations…', 'business-map-locator'); ?></small></div><span><?php esc_html_e('Sort: Default', 'business-map-locator'); ?></span></div>
                                <div class="bml-studio-v2__cards" data-frontend-list></div>
                            </section>
                        </div>
                    </div>
                </div>

                <footer class="bml-settings-studio__preview-footer bml-studio-v2__preview-footer"><span><?php esc_html_e('Preview reflects saved locations and unsaved display settings.', 'business-map-locator'); ?></span><a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open frontend', 'business-map-locator'); ?> ↗</a></footer>
            </main>
        </div>
        </form>
        <?php
    }

    private function advanced(array $s, array $context): void
    {
        $this->formStart('system');
        ?>
        <div class="bml-settings-studio__advanced">
            <section><h2><?php esc_html_e('Performance', 'business-map-locator'); ?></h2><p><?php esc_html_e('Optimize map and REST loading for large directories.', 'business-map-locator'); ?></p><label class="bml-settings-studio__toggle-row"><span><strong><?php esc_html_e('Cache REST API responses', 'business-map-locator'); ?></strong><small><?php esc_html_e('Recommended for large location directories.', 'business-map-locator'); ?></small></span><input type="checkbox" name="rest_cache" value="1" <?php checked(!empty($s['rest_cache'])); ?>></label><label class="bml-settings-studio__field"><span><?php esc_html_e('Geocoding cache lifetime', 'business-map-locator'); ?></span><select name="cache_ttl"><option value="86400" <?php selected((int)($s['cache_ttl'] ?? DAY_IN_SECONDS), DAY_IN_SECONDS); ?>>24 hours</option><option value="604800" <?php selected((int)($s['cache_ttl'] ?? DAY_IN_SECONDS), WEEK_IN_SECONDS); ?>>7 days</option><option value="2592000" <?php selected((int)($s['cache_ttl'] ?? DAY_IN_SECONDS), MONTH_IN_SECONDS); ?>>30 days</option></select></label><label class="bml-settings-studio__field"><span><?php esc_html_e('Distance units', 'business-map-locator'); ?></span><select name="distance_unit"><option value="km" <?php selected($s['distance_unit'] ?? 'km', 'km'); ?>>Kilometers</option><option value="mi" <?php selected($s['distance_unit'] ?? 'km', 'mi'); ?>>Miles</option></select></label><label class="bml-settings-studio__toggle-row"><span><strong><?php esc_html_e('Import log', 'business-map-locator'); ?></strong><small><?php esc_html_e('Keep import events for diagnostics.', 'business-map-locator'); ?></small></span><input type="checkbox" name="import_logging" value="1" <?php checked(!empty($s['import_logging'])); ?>></label></section>
            <aside><h2><?php esc_html_e('System status', 'business-map-locator'); ?></h2><dl><div><dt><?php esc_html_e('Published locations', 'business-map-locator'); ?></dt><dd><?php echo esc_html((string) ($context['published_locations'] ?? 0)); ?></dd></div><div><dt>REST API</dt><dd><?php esc_html_e('Available', 'business-map-locator'); ?></dd></div><div><dt><?php esc_html_e('Plugin version', 'business-map-locator'); ?></dt><dd><?php echo esc_html((string) ($context['plugin_version'] ?? '')); ?></dd></div></dl><label class="bml-settings-studio__toggle-row is-danger"><span><strong><?php esc_html_e('Delete all data when uninstalling', 'business-map-locator'); ?></strong><small><?php esc_html_e('Permanently deletes locations, taxonomies and settings.', 'business-map-locator'); ?></small></span><input type="checkbox" name="delete_data" value="1" <?php checked(!empty($s['delete_data'])); ?>></label></aside>
        </div>
        <div class="bml-settings-studio__savebar"><span><i></i><strong><?php esc_html_e('Advanced settings', 'business-map-locator'); ?></strong></span><button type="submit" class="bml-settings-studio__button bml-settings-studio__button--primary"><?php esc_html_e('Save', 'business-map-locator'); ?></button></div>
        </form>
        <?php
    }
}
