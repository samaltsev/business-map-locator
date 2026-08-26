<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Ajax;

use BusinessMapLocator\WordPress\Capabilities;
use BusinessMapLocator\Support\SlugGenerator;

if (!defined('ABSPATH')) {
    exit;
}

final class LocationEditorAjaxController
{
    public function createTerm(): void
    {
        check_ajax_referer('bml_location_editor', 'nonce');

        if (!current_user_can(Capabilities::MANAGE_TERMS)) {
            wp_send_json_error(['message' => __('You are not allowed to create categories or cities.', 'business-map-locator')], 403);
        }

        $taxonomy = isset($_POST['taxonomy']) && !is_array($_POST['taxonomy'])
            ? sanitize_key(wp_unslash($_POST['taxonomy']))
            : '';
        $name = isset($_POST['name']) && !is_array($_POST['name'])
            ? sanitize_text_field(wp_unslash($_POST['name']))
            : '';
        $providedSlug = isset($_POST['slug']) && !is_array($_POST['slug'])
            ? sanitize_text_field(wp_unslash($_POST['slug']))
            : '';
        $slug = SlugGenerator::fromTerm($name, $providedSlug);

        if (!in_array($taxonomy, ['bml_category', 'bml_city'], true)) {
            wp_send_json_error(['message' => __('Invalid taxonomy.', 'business-map-locator')], 400);
        }

        if ($name === '') {
            wp_send_json_error(['message' => __('A name is required.', 'business-map-locator')], 422);
        }

        $existing = term_exists($slug, $taxonomy);
        if (!$existing) {
            $existing = term_exists($name, $taxonomy);
        }
        if ($existing) {
            $termId = is_array($existing) ? (int) $existing['term_id'] : (int) $existing;
            $term = get_term($termId, $taxonomy);
            if ($term && !is_wp_error($term)) {
                wp_send_json_success(['id' => $termId, 'name' => $term->name, 'slug' => $term->slug, 'existing' => true]);
            }
        }

        $result = wp_insert_term($name, $taxonomy, ['slug' => $slug]);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        \BML_Location_Cache::invalidate();
        $term = get_term((int) $result['term_id'], $taxonomy);
        wp_send_json_success([
            'id' => (int) $result['term_id'],
            'name' => $term && !is_wp_error($term) ? $term->name : $name,
            'slug' => $term && !is_wp_error($term) ? $term->slug : $slug,
            'existing' => false,
        ]);
    }


    public function autoSave(): void
    {
        check_ajax_referer('bml_location_editor', 'nonce');

        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        if ($id > 0) {
            $post = get_post($id);
            if (!$post || $post->post_type !== 'bml_location' || !current_user_can(Capabilities::EDIT_LOCATION, $id)) {
                wp_send_json_error(['message' => __('You are not allowed to edit this location.', 'business-map-locator')], 403);
            }
        } elseif (!current_user_can(Capabilities::CREATE_LOCATIONS)) {
            wp_send_json_error(['message' => __('You are not allowed to create locations.', 'business-map-locator')], 403);
        }

        $title = isset($_POST['title']) && !is_array($_POST['title'])
            ? sanitize_text_field(wp_unslash($_POST['title']))
            : '';
        if ($title === '') {
            wp_send_json_error(['message' => __('A location name is required for autosave.', 'business-map-locator')], 422);
        }

        $requestedStatus = isset($_POST['status']) && !is_array($_POST['status'])
            ? sanitize_key(wp_unslash($_POST['status']))
            : 'draft';
        $postStatus = $requestedStatus === 'publish' && current_user_can(Capabilities::PUBLISH_LOCATIONS)
            ? 'publish'
            : 'draft';

        $postData = [
            'post_type' => 'bml_location',
            'post_title' => $title,
            'post_content' => '',
            'post_status' => $postStatus,
        ];
        if ($id > 0) {
            $postData['ID'] = $id;
            $result = wp_update_post($postData, true);
        } else {
            $result = wp_insert_post($postData, true);
        }
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }
        $id = (int) $result;

        foreach (['address','region','country','postcode','phone'] as $key) {
            $value = isset($_POST[$key]) && !is_array($_POST[$key])
                ? sanitize_text_field(wp_unslash($_POST[$key]))
                : '';
            update_post_meta($id, 'bml_' . $key, $value);
        }
        foreach (['lat','lng'] as $key) {
            $raw = isset($_POST[$key]) && !is_array($_POST[$key]) ? trim((string) wp_unslash($_POST[$key])) : '';
            if ($raw === '' || !is_numeric($raw)) {
                delete_post_meta($id, 'bml_' . $key);
            } else {
                update_post_meta($id, 'bml_' . $key, (float) $raw);
            }
        }
        $operational = isset($_POST['operational_status']) && !is_array($_POST['operational_status'])
            ? sanitize_key(wp_unslash($_POST['operational_status']))
            : 'open';
        if (!in_array($operational, ['open', 'temporarily_closed', 'hidden'], true)) {
            $operational = 'open';
        }
        update_post_meta($id, 'bml_operational_status', $operational);

        foreach (['bml_category' => 'category_id', 'bml_city' => 'city_id'] as $taxonomy => $field) {
            $termId = isset($_POST[$field]) ? absint(wp_unslash($_POST[$field])) : 0;
            wp_set_object_terms($id, $termId ? [$termId] : [], $taxonomy);
        }

        if (class_exists('BML_Location_Cache')) {
            \BML_Location_Cache::invalidate();
        }

        wp_send_json_success([
            'id' => $id,
            'message' => $postStatus === 'publish' ? __('Location saved.', 'business-map-locator') : __('Draft saved.', 'business-map-locator'),
            'status' => $postStatus,
            'saved_at' => current_time('mysql'),
        ]);
    }
}
