<?php
if (!defined('ABSPATH')) {
    exit;
}

final class BML_Location_Indexer {
    private const META_KEYS = [
        'bml_address',
        'bml_region',
        'bml_country',
        'bml_postcode',
        'bml_lat',
        'bml_lng',
        'bml_phone',
        'bml_email',
        'bml_website',
        'bml_hours',
        'bml_operational_status',
        'bml_visible',
        '_thumbnail_id',
    ];

    private BML_Location_Index $index;
    private array $dirty_posts = [];
    private bool $shutdown_registered = false;

    public function __construct(?BML_Location_Index $index = null) {
        $this->index = $index ?: new BML_Location_Index();
    }

    public function hooks(): void {
        add_action('save_post_bml_location', [$this, 'sync_post'], 20, 3);
        add_action('before_delete_post', [$this, 'delete_post']);
        add_action('trashed_post', [$this, 'delete_post']);
        add_action('untrashed_post', [$this, 'sync_post_by_id']);
        add_action('set_object_terms', [$this, 'sync_terms'], 20, 6);
        add_action('added_post_meta', [$this, 'sync_meta'], 20, 4);
        add_action('updated_post_meta', [$this, 'sync_meta'], 20, 4);
        add_action('deleted_post_meta', [$this, 'sync_meta'], 20, 4);
    }

    public function sync_post(int $post_id, WP_Post $post, bool $update): void {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
            return;
        }

        if ($post->post_type !== 'bml_location') {
            return;
        }

        $this->mark_dirty($post_id);
    }

    public function sync_post_by_id(int $post_id): void {
        if (get_post_type($post_id) === 'bml_location') {
            $this->mark_dirty($post_id);
        }
    }

    public function delete_post(int $post_id): void {
        if (get_post_type($post_id) === 'bml_location') {
            $this->index->delete($post_id);
        }
    }

    public function sync_terms(int $object_id, $terms, $tt_ids, string $taxonomy): void {
        if (($taxonomy === 'bml_category' || $taxonomy === 'bml_city') && get_post_type($object_id) === 'bml_location') {
            $this->mark_dirty($object_id);
        }
    }

    public function sync_meta($meta_id, int $post_id, string $meta_key, $meta_value = null): void {
        if (!in_array($meta_key, self::META_KEYS, true) || get_post_type($post_id) !== 'bml_location') {
            return;
        }

        $this->mark_dirty($post_id);
    }

    public function flush_dirty(): void {
        if (!BML_Database::table_exists(BML_Database::locations_index_table())) {
            $this->dirty_posts = [];
            return;
        }

        foreach (array_keys($this->dirty_posts) as $post_id) {
            if (get_post_type((int) $post_id) === 'bml_location') {
                $this->index->upsert((int) $post_id);
            }
        }

        $this->dirty_posts = [];
    }

    private function mark_dirty(int $post_id): void {
        $this->dirty_posts[$post_id] = true;

        if (!$this->shutdown_registered) {
            $this->shutdown_registered = true;
            add_action('shutdown', [$this, 'flush_dirty']);
        }
    }
}
