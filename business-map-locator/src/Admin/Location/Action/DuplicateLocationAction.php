<?php

namespace BusinessMapLocator\Admin\Location\Action;

use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class DuplicateLocationAction
{
    public function __construct(private \BML_Location_Index $index, private AdminActionResponder $responder, private AdminRequest $request) {}

    public function handle(): void
    {
        $id = $this->request->getInt('id');
        $post = $id ? get_post($id) : null;
        if (!$post || $post->post_type !== 'bml_location' || !current_user_can(Capabilities::EDIT_LOCATION, $id)) {
            $this->responder->error(__('Insufficient permissions.', 'business-map-locator'));
        }

        check_admin_referer('bml_duplicate_location_' . $id);

        $new_id = wp_insert_post([
            'post_type' => 'bml_location',
            'post_title' => $post->post_title . ' ' . __('Copy', 'business-map-locator'),
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_status' => 'draft',
        ], true);
        if (is_wp_error($new_id)) {
            $this->responder->error($new_id->get_error_message());
        }

        $newId = (int) $new_id;
        if ($newId <= 0) {
            $this->responder->error(__('Location could not be duplicated.', 'business-map-locator'));
        }

        $excluded = ['_edit_lock', '_edit_last', '_wp_old_slug', '_thumbnail_id'];
        foreach (get_post_meta($id) as $key => $values) {
            if (in_array($key, $excluded, true)) {
                continue;
            }
            foreach ($values as $value) {
                add_post_meta($newId, $key, maybe_unserialize($value));
            }
        }

        foreach (['bml_category', 'bml_city'] as $tax) {
            $terms = wp_get_post_terms($id, $tax, ['fields' => 'ids']);
            if (!is_wp_error($terms)) {
                wp_set_object_terms($newId, $terms, $tax);
            }
        }

        if (has_post_thumbnail($id)) {
            set_post_thumbnail($newId, get_post_thumbnail_id($id));
        }

        $this->index->upsert($newId);
        \BML_Location_Cache::invalidate();

        $this->responder->redirect('bml-location-edit', 'location-duplicated', ['id' => $newId]);
    }
}
