<?php
if (!defined('ABSPATH')) { exit; }

final class BML_Cache_Invalidator {
    public function hooks(): void {
        add_action('save_post_bml_location', [$this, 'invalidate'], 10, 3);
        add_action('before_delete_post', [$this, 'delete_post']);
        add_action('set_object_terms', [$this, 'terms_changed'], 10, 6);
        add_action('created_bml_category', [$this, 'invalidate']);
        add_action('edited_bml_category', [$this, 'invalidate']);
        add_action('delete_bml_category', [$this, 'invalidate']);
        add_action('created_bml_city', [$this, 'invalidate']);
        add_action('edited_bml_city', [$this, 'invalidate']);
        add_action('delete_bml_city', [$this, 'invalidate']);
        add_action('update_option_bml_settings', [$this, 'invalidate']);
    }

    public function invalidate(): void {
        BML_Location_Cache::invalidate();
    }

    public function delete_post(int $post_id): void {
        if (get_post_type($post_id) === 'bml_location') {
            $this->invalidate();
        }
    }

    public function terms_changed(int $object_id, $terms, $tt_ids, string $taxonomy): void {
        if ($taxonomy === 'bml_category' || $taxonomy === 'bml_city') {
            $this->invalidate();
        }
    }
}
