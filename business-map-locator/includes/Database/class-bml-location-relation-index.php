<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rebuildable mirror of direct Location taxonomy relationships.
 * WordPress term relationships remain the source of truth.
 */
final class BML_Location_Relation_Index {
    public const TAXONOMIES = ['bml_category', 'bml_city', 'bml_area'];

    /** @return list<array{location_id:int,taxonomy:string,term_id:int,is_primary:int}>|null */
    public function direct_rows(int $post_id): ?array {
        $rows = [];

        foreach (self::TAXONOMIES as $taxonomy) {
            $term_ids = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
            if (is_wp_error($term_ids)) {
                return null;
            }

            $term_ids = array_values(array_unique(array_map('absint', is_array($term_ids) ? $term_ids : [])));
            sort($term_ids, SORT_NUMERIC);
            foreach ($term_ids as $term_id) {
                if ($term_id > 0) {
                    $rows[] = [
                        'location_id' => $post_id,
                        'taxonomy' => $taxonomy,
                        'term_id' => $term_id,
                        'is_primary' => 0,
                    ];
                }
            }
        }

        return $rows;
    }

    public function sync(int $post_id): bool {
        global $wpdb;

        if (!BML_Database::table_exists(BML_Database::location_terms_table())) {
            return false;
        }

        $table = BML_Database::location_terms_table();
        $rows = $this->direct_rows($post_id);
        if ($rows === null || $wpdb->query('START TRANSACTION') === false) {
            return false;
        }

        if ($wpdb->delete($table, ['location_id' => $post_id], ['%d']) === false) {
            $this->rollback();
            return false;
        }

        foreach ($rows as $row) {
            if ($wpdb->insert($table, $row, ['%d', '%s', '%d', '%d']) === false) {
                $this->rollback();
                return false;
            }
        }

        if ($wpdb->query('COMMIT') === false) {
            $this->rollback();
            return false;
        }

        return true;
    }

    public function delete(int $post_id): bool {
        if (!BML_Database::table_exists(BML_Database::location_terms_table())) {
            return false;
        }

        global $wpdb;

        return $wpdb->delete(BML_Database::location_terms_table(), ['location_id' => $post_id], ['%d']) !== false;
    }

    private function rollback(): void {
        global $wpdb;

        $wpdb->query('ROLLBACK');
    }
}
