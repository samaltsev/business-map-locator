<?php
if (!defined('ABSPATH')) { exit; }

final class BML_Assets {
    private const LEAFLET_VERSION = '1.9.4';
    private const CLUSTER_VERSION = '1.5.3';

    public static function register_map_assets(): void {
        wp_register_style('bml-leaflet', BML_URL . 'assets/vendor/leaflet/leaflet.css', [], self::LEAFLET_VERSION);
        wp_register_style('bml-markercluster', BML_URL . 'assets/vendor/markercluster/MarkerCluster.css', ['bml-leaflet'], self::CLUSTER_VERSION);
        wp_register_style('bml-markercluster-default', BML_URL . 'assets/vendor/markercluster/MarkerCluster.Default.css', ['bml-markercluster'], self::CLUSTER_VERSION);
        wp_register_script('bml-leaflet', BML_URL . 'assets/vendor/leaflet/leaflet.js', [], self::LEAFLET_VERSION, true);
        wp_register_script('bml-markercluster', BML_URL . 'assets/vendor/markercluster/leaflet.markercluster.js', ['bml-leaflet'], self::CLUSTER_VERSION, true);
    }

    public static function local_vendor_status(): array {
        return [
            'leaflet_css' => file_exists(BML_DIR . 'assets/vendor/leaflet/leaflet.css'),
            'leaflet_js' => file_exists(BML_DIR . 'assets/vendor/leaflet/leaflet.js'),
            'cluster_css' => file_exists(BML_DIR . 'assets/vendor/markercluster/MarkerCluster.css') && file_exists(BML_DIR . 'assets/vendor/markercluster/MarkerCluster.Default.css'),
            'cluster_js' => file_exists(BML_DIR . 'assets/vendor/markercluster/leaflet.markercluster.js'),
        ];
    }

}
