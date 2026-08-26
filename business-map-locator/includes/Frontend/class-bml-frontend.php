<?php
if (!defined('ABSPATH')) { exit; }

class BML_Frontend {
    /** @var BML_Provider_Registry */
    private $providers;

    public function __construct(?BML_Provider_Registry $providers = null) {
        $this->providers = $providers ?? new BML_Provider_Registry();
    }

    public function hooks(): void {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('enqueue_block_assets', [$this, 'register_assets']);
    }

    public function register_assets(): void {
        $settings = self::settings();

        $this->register_styles();
        $this->register_scripts();
        $this->providers->register_assets($settings);
    }

    public static function enqueue(): void {
        $settings = self::settings();
        $registry = new BML_Provider_Registry();
        $provider = $registry->get_active($settings);
        $frontend = new self($registry);

        $frontend->register_assets();
        self::enqueue_assets($provider, $settings);
        self::localize($registry, $provider, $settings);
    }

    public static function settings(): array {
        return BML_Plugin::settings();
    }

    private function register_styles(): void {
        wp_register_style(
            'bml-frontend',
            BML_URL . 'assets/css/frontend.css',
            [],
            self::asset_version('assets/css/frontend.css')
        );
    }

    private function register_scripts(): void {
        wp_register_script(
            'bml-provider-base',
            BML_URL . 'assets/js/providers/base-provider.js',
            [],
            self::asset_version('assets/js/providers/base-provider.js'),
            true
        );

        wp_register_script(
            'bml-map-controller',
            BML_URL . 'assets/js/map-controller.js',
            ['bml-provider-base'],
            self::asset_version('assets/js/map-controller.js'),
            true
        );

        wp_register_script(
            'bml-locator-renderer',
            BML_URL . 'assets/js/locator-renderer.js',
            ['bml-map-controller'],
            self::asset_version('assets/js/locator-renderer.js'),
            true
        );

        wp_register_script(
            'bml-frontend',
            BML_URL . 'assets/js/frontend.js',
            ['bml-locator-renderer'],
            self::asset_version('assets/js/frontend.js'),
            true
        );
    }


    private static function asset_version(string $relative_path): string {
        $path = BML_DIR . ltrim($relative_path, '/\\');
        $modified = is_file($path) ? filemtime($path) : false;

        return BML_VERSION . ($modified !== false ? '.' . (string) $modified : '');
    }

    private static function enqueue_assets(BML_Map_Provider_Interface $provider, array $settings): void {
        wp_enqueue_style('bml-frontend');
        wp_enqueue_script('bml-provider-base');
        $provider->enqueue_assets($settings);
        wp_enqueue_script('bml-map-controller');
        wp_enqueue_script('bml-locator-renderer');
        wp_enqueue_script('bml-frontend');
    }

    private static function localize(BML_Provider_Registry $registry, BML_Map_Provider_Interface $provider, array $settings): void {
        wp_localize_script('bml-frontend', 'BMLFrontend', array_merge(
            self::rest_config(),
            [
            'settings' => $settings,
            'provider' => self::provider_config($registry, $provider, $settings),
            'strings' => self::localization(),
            ]
        ));
    }

    private static function rest_config(): array {
        return [
            'restUrl' => esc_url_raw(rest_url('business-map/v1/')),
        ];
    }

    private static function provider_config(BML_Provider_Registry $registry, BML_Map_Provider_Interface $provider, array $settings): array {
        return [
            'active' => $provider->get_id(),
            'config' => $provider->get_client_config($settings),
            'statuses' => $registry->get_statuses($settings),
            'health' => $registry->get_health($settings),
        ];
    }

    private static function localization(): array {
        return [
            'allCategories' => __('All categories', 'business-map-locator'),
            'allCities' => __('All cities', 'business-map-locator'),
            'noResults' => __('No locations found.', 'business-map-locator'),
            'requestError' => __('Locations could not be loaded.', 'business-map-locator'),
            'providerError' => __('The selected map provider could not be initialized.', 'business-map-locator'),
            'providerFallback' => __('The selected map provider failed, so OpenStreetMap was loaded instead.', 'business-map-locator'),
            'navigation' => __('Navigation', 'business-map-locator'),
            'website' => __('Website', 'business-map-locator'),
            'showOnMap' => __('Show on map', 'business-map-locator'),
            'details' => __('Details', 'business-map-locator'),
            'directions' => __('Directions', 'business-map-locator'),
            'call' => __('Call', 'business-map-locator'),
            'visitWebsite' => __('Visit website', 'business-map-locator'),
            'temporarilyClosed' => __('Temporarily closed', 'business-map-locator'),
            'imageUnavailable' => __('Image unavailable', 'business-map-locator'),
            'close' => __('Close', 'business-map-locator'),
            'back' => __('Back', 'business-map-locator'),
            'loadingDetails' => __('Loading location details…', 'business-map-locator'),
            'detailsError' => __('Could not load location details.', 'business-map-locator'),
            'email' => __('Email', 'business-map-locator'),
            'nearMe' => __('Near me', 'business-map-locator'),
            'currentLocation' => __('You are here', 'business-map-locator'),
            'location' => __('Location', 'business-map-locator'),
            'geolocationError' => __('Your location could not be determined.', 'business-map-locator'),
        ];
    }
}
