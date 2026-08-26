<?php

namespace BusinessMapLocator\Admin\Assets;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminAssets
{
    public function enqueue(string $hook): void
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (strpos($page, 'bml-') !== 0 && $page !== 'business-map-locator') {
            return;
        }

        wp_enqueue_media();
        \BML_Assets::register_map_assets();
        wp_enqueue_style('bml-leaflet');
        wp_enqueue_script('bml-leaflet');
        wp_enqueue_style('bml-admin', \BML_URL . 'assets/css/admin.css', [], \BML_VERSION);

        if ($page === 'bml-settings' || in_array($page, ['bml-display', 'bml-providers', 'bml-embed'], true)) {
            wp_enqueue_style('bml-markercluster');
            wp_enqueue_style('bml-markercluster-default');
            wp_enqueue_script('bml-markercluster');
            wp_enqueue_style('bml-frontend', \BML_URL . 'assets/css/frontend.css', [], \BML_VERSION);
            wp_enqueue_style('bml-settings-ux', \BML_URL . 'assets/css/admin/settings-ux.css', ['bml-admin'], \BML_VERSION);
        }

        wp_enqueue_script('bml-admin', \BML_URL . 'assets/js/admin.js', ['bml-leaflet', 'wp-api-fetch', 'jquery'], \BML_VERSION, true);
        if (in_array($page, ['bml-settings', 'bml-display', 'bml-providers', 'bml-embed'], true)) {
            wp_enqueue_script('bml-settings-ux', \BML_URL . 'assets/js/admin/settings-ux.js', ['bml-admin'], \BML_VERSION, true);
        }

        if ($page === 'bml-import') {
            wp_enqueue_script('bml-import', \BML_URL . 'assets/js/admin/import.js', ['jquery'], \BML_VERSION, true);
        }

        if ($page === 'bml-categories') {
            wp_enqueue_style('bml-category-experience', \BML_URL . 'assets/css/admin/pages/category-experience.css', ['bml-admin'], \BML_VERSION);
            wp_enqueue_script('bml-category-experience', \BML_URL . 'assets/js/admin/category-experience.js', ['bml-admin'], \BML_VERSION, true);
        }

        if ($page === 'bml-cities') {
            wp_enqueue_style('bml-city-experience', \BML_URL . 'assets/css/admin/pages/city-experience.css', ['bml-admin'], \BML_VERSION);
            wp_enqueue_script('bml-city-experience', \BML_URL . 'assets/js/admin/city-experience.js', ['bml-admin'], \BML_VERSION, true);
        }

        if ($page === 'bml-locations') {
            wp_enqueue_style('bml-locations-experience', \BML_URL . 'assets/css/admin/pages/locations-experience.css', ['bml-admin'], \BML_VERSION);
            wp_enqueue_script('bml-locations-experience', \BML_URL . 'assets/js/admin/locations-experience.js', ['bml-admin'], \BML_VERSION, true);
        }

        if ($page === 'bml-location-edit') {
            wp_enqueue_style('bml-location-editor', \BML_URL . 'assets/css/admin/pages/location-editor.css', ['bml-admin'], \BML_VERSION);
            wp_enqueue_script('bml-location-editor', \BML_URL . 'assets/js/admin/location-editor.js', ['bml-admin'], \BML_VERSION, true);
            wp_enqueue_script('bml-location-map', \BML_URL . 'assets/js/admin/location-map.js', ['bml-location-editor', 'bml-leaflet', 'wp-api-fetch'], \BML_VERSION, true);
        }

        wp_localize_script('bml-admin', 'BMLAdmin', [
            'restUrl' => esc_url_raw(rest_url('business-map/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'importNonce' => wp_create_nonce('bml_import_ajax'),
            'locationEditorNonce' => wp_create_nonce('bml_location_editor'),
            'settings' => \BML_Plugin::settings(),
            'strings' => [
                'geocodeError' => __('Address could not be found.', 'business-map-locator'),
                'reverseNotice' => __('Address was filled automatically. Please verify it before saving.', 'business-map-locator'),
                'selectImage' => __('Select location image', 'business-map-locator'),
                'useImage' => __('Use this image', 'business-map-locator'),
                'selectCategoryIcon' => __('Select category icon', 'business-map-locator'),
                'useCategoryIcon' => __('Use this icon', 'business-map-locator'),
                'confirmDelete' => __('Delete this item permanently?', 'business-map-locator'),
                'unsavedChanges' => __('Unsaved changes', 'business-map-locator'),
                'businessName' => __('Business name', 'business-map-locator'),
                'addressPlaceholder' => __('Address will appear here', 'business-map-locator'),
                'openNow' => __('Open now', 'business-map-locator'),
                'temporarilyClosed' => __('Temporarily closed', 'business-map-locator'),
                'hidden' => __('Hidden', 'business-map-locator'),
                'noImage' => __('No image selected', 'business-map-locator'),
                'searching' => __('Searching...', 'business-map-locator'),
                'addressFound' => __('Address found', 'business-map-locator'),
                'savingDraft' => __('Saving draft...', 'business-map-locator'),
                'draftSaved' => __('Draft saved', 'business-map-locator'),
                'autosaveFailed' => __('Autosave failed', 'business-map-locator'),
                'importPreparing' => __('Checking CSV...', 'business-map-locator'),
                'importRunning' => __('Importing locations...', 'business-map-locator'),
                'importComplete' => __('Import completed.', 'business-map-locator'),
                'duplicateConfirm' => __('Delete extra duplicate records and keep the oldest location in every group?', 'business-map-locator'),
            ],
        ]);
    }
}
