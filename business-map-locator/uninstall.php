<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = (array) get_option('bml_settings', []);
if (empty($settings['delete_data'])) {
    return;
}

require_once __DIR__ . '/src/WordPress/Capabilities.php';
require_once __DIR__ . '/includes/Core/class-bml-capabilities.php';

BML_Capabilities::uninstall();

$posts = get_posts([
    'post_type' => 'bml_location',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

foreach ($posts as $post_id) {
    wp_delete_post($post_id, true);
}

foreach (['bml_category', 'bml_city'] as $taxonomy) {
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'fields' => 'ids',
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term_id) {
            wp_delete_term($term_id, $taxonomy);
        }
    }
}

global $wpdb;
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'bml_locations_index');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'bml_import_job_events');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'bml_import_job_rows');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'bml_import_jobs');

delete_option('bml_settings');
delete_option('bml_last_import');
delete_option('bml_import_jobs');
delete_option('bml_database_version');
delete_option('bml_schema_version');
delete_option('bml_index_rebuild_offset');
delete_option('bml_index_rebuild_required');
