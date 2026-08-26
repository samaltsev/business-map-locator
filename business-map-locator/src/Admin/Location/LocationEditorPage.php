<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Location;
use BusinessMapLocator\Domain\Location\LocationServiceCatalog;
if (!defined('ABSPATH')) {
    exit;
}

final class LocationEditorPage {
    public function __construct(
        private LocationServiceCatalog $services,
        private LocationCompleteness $completeness
    ) {
    }

    public function render(): void {
        if (!current_user_can(\BusinessMapLocator\WordPress\Capabilities::EDIT_LOCATIONS)) {
            wp_die(esc_html__('Insufficient permissions.', 'business-map-locator'));
        }

        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
        $post = $id ? get_post($id) : null;

        if ($post && ($post->post_type !== 'bml_location' || !current_user_can(\BusinessMapLocator\WordPress\Capabilities::EDIT_LOCATION, $id))) {
            wp_die(esc_html__('You are not allowed to edit this location.', 'business-map-locator'));
        }

        $meta = [];
        foreach (['address','region','country','postcode','lat','lng','phone','email','website','hours','operational_status'] as $key) {
            $meta[$key] = $post ? get_post_meta($post->ID, 'bml_' . $key, true) : '';
        }
        if ($meta['operational_status'] === 'open') { $meta['operational_status'] = 'active'; }

        $data = [
            'id' => $id,
            'post' => $post,
            'title' => $post ? $post->post_title : '',
            'content' => $post ? $post->post_content : '',
            'status' => $post ? $post->post_status : 'publish',
            'meta' => $meta,
            'services' => $post ? (array) get_post_meta($post->ID, 'bml_services', true) : [],
            'service_options' => $this->services->all(),
            'category_ids' => $post ? wp_get_post_terms($post->ID, 'bml_category', ['fields' => 'ids']) : [],
            'city_ids' => $post ? wp_get_post_terms($post->ID, 'bml_city', ['fields' => 'ids']) : [],
            'image_id' => $post ? get_post_thumbnail_id($post->ID) : 0,
            'categories' => get_terms(['taxonomy' => 'bml_category', 'hide_empty' => false]),
            'cities' => get_terms(['taxonomy' => 'bml_city', 'hide_empty' => false]),
            'can_manage_terms' => current_user_can(\BusinessMapLocator\WordPress\Capabilities::MANAGE_TERMS),
            'can_publish' => current_user_can(\BusinessMapLocator\WordPress\Capabilities::PUBLISH_LOCATIONS),
        ];

        if (is_wp_error($data['categories'])) { $data['categories'] = []; }
        if (is_wp_error($data['cities'])) { $data['cities'] = []; }
        $data['completeness'] = $this->completeness->calculate($data);

        $view = \BML_DIR . 'src/Admin/Location/View/location-editor.php';
        if (file_exists($view)) {
            include $view;
        }
    }
}
