<?php
if (!defined('ABSPATH')) {
    exit;
}

final class BML_Location_Index {
    public function upsert(int $post_id): bool {
        global $wpdb;

        if (!BML_Database::table_exists(BML_Database::locations_index_table())) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'bml_location') {
            return false;
        }

        $city = $this->first_term($post_id, 'bml_city');
        $category = $this->first_term($post_id, 'bml_category');
        $address = $this->meta($post_id, 'bml_address', 500);
        $region = $this->meta($post_id, 'bml_region', 191);
        $country = $this->meta($post_id, 'bml_country', 191);
        $postcode = $this->meta($post_id, 'bml_postcode', 64);
        $phone = $this->meta($post_id, 'bml_phone', 100);
        $email = $this->meta($post_id, 'bml_email', 191);
        $website = $this->meta($post_id, 'bml_website', 255);
        $hours = $this->meta($post_id, 'bml_hours', 255);
        $excerpt = $this->truncate_string($post->post_excerpt !== '' ? $post->post_excerpt : wp_trim_words($post->post_content, 35, ''), 1000);
        $image_id = (int) get_post_thumbnail_id($post_id);
        $operational_status = sanitize_key((string) get_post_meta($post_id, 'bml_operational_status', true));
        $operational_status = $operational_status === 'open' ? 'active' : $operational_status;
        if (!in_array($operational_status, ['active', 'temporarily_closed', 'hidden'], true)) {
            $operational_status = 'active';
        }

        $latitude = $this->coordinate(get_post_meta($post_id, 'bml_lat', true), -90, 90);
        $longitude = $this->coordinate(get_post_meta($post_id, 'bml_lng', true), -180, 180);
        $visibility = $post->post_status === 'publish' ? 'public' : sanitize_key($post->post_status);
        $search_text = $this->search_text([
            $post->post_title,
            $address,
            $city['name'],
            $region,
            $country,
            $postcode,
            $phone,
            $email,
            $website,
            $hours,
            $excerpt,
        ]);

        $table = BML_Database::locations_index_table();
        $latitude_sql = $latitude === null ? 'NULL' : '%f';
        $longitude_sql = $longitude === null ? 'NULL' : '%f';

        $sql = "INSERT INTO {$table}
            (post_id, title, address, city, city_slug, category, category_slug, region, country, postcode, latitude, longitude, image_id, phone, email, website, hours, excerpt, operational_status, visibility, search_text, updated_at)
            VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, {$latitude_sql}, {$longitude_sql}, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                address = VALUES(address),
                city = VALUES(city),
                city_slug = VALUES(city_slug),
                category = VALUES(category),
                category_slug = VALUES(category_slug),
                region = VALUES(region),
                country = VALUES(country),
                postcode = VALUES(postcode),
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                image_id = VALUES(image_id),
                phone = VALUES(phone),
                email = VALUES(email),
                website = VALUES(website),
                hours = VALUES(hours),
                excerpt = VALUES(excerpt),
                operational_status = VALUES(operational_status),
                visibility = VALUES(visibility),
                search_text = VALUES(search_text),
                updated_at = VALUES(updated_at)";

        $values = [
            $post_id,
            $this->truncate_string($post->post_title, 255),
            $address,
            $city['name'],
            $city['slug'],
            $category['name'],
            $category['slug'],
            $region,
            $country,
            $postcode,
        ];

        if ($latitude !== null) {
            $values[] = $latitude;
        }
        if ($longitude !== null) {
            $values[] = $longitude;
        }

        $values = array_merge($values, [
            $image_id,
            $phone,
            $email,
            $website,
            $hours,
            $excerpt,
            $operational_status,
            $visibility,
            $search_text,
            current_time('mysql'),
        ]);

        return $wpdb->query($wpdb->prepare($sql, $values)) !== false;
    }

    public function delete(int $post_id): bool {
        global $wpdb;

        if (!BML_Database::table_exists(BML_Database::locations_index_table())) {
            return false;
        }

        return $wpdb->delete(BML_Database::locations_index_table(), ['post_id' => $post_id], ['%d']) !== false;
    }

    public function rebuild(int $offset = 0, int $limit = 100): array {
        if (!BML_Database::table_exists(BML_Database::locations_index_table())) {
            return [
                'offset' => max(0, $offset),
                'limit' => max(1, min(500, $limit)),
                'processed' => 0,
                'indexed' => 0,
                'failed' => 0,
                'done' => false,
            ];
        }

        $ids = get_posts([
            'post_type' => 'bml_location',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'offset' => max(0, $offset),
            'posts_per_page' => max(1, min(500, $limit)),
            'no_found_rows' => true,
        ]);

        $indexed = 0;
        $failed = 0;

        foreach ($ids as $post_id) {
            if ($this->upsert((int) $post_id)) {
                $indexed++;
            } else {
                $failed++;
            }
        }

        return [
            'offset' => max(0, $offset),
            'limit' => max(1, min(500, $limit)),
            'processed' => count($ids),
            'indexed' => $indexed,
            'failed' => $failed,
            'done' => count($ids) < max(1, min(500, $limit)),
        ];
    }

    public function truncate(): bool {
        global $wpdb;

        if (!BML_Database::table_exists(BML_Database::locations_index_table())) {
            return false;
        }

        return $wpdb->query('TRUNCATE TABLE ' . BML_Database::locations_index_table()) !== false;
    }

    public function exists(int $post_id): bool {
        global $wpdb;

        if (!BML_Database::table_exists(BML_Database::locations_index_table())) {
            return false;
        }

        $table = BML_Database::locations_index_table();

        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM {$table} WHERE post_id = %d", $post_id)) > 0;
    }

    private function first_term(int $post_id, string $taxonomy): array {
        $terms = wp_get_post_terms($post_id, $taxonomy);
        if (is_wp_error($terms) || empty($terms)) {
            return ['name' => '', 'slug' => ''];
        }

        $term = $terms[0];

        return [
            'name' => $this->truncate_string($term->name, 191),
            'slug' => $this->truncate_string($term->slug, 191),
        ];
    }

    private function meta(int $post_id, string $key, int $limit): string {
        return $this->truncate_string((string) get_post_meta($post_id, $key, true), $limit);
    }

    private function coordinate($value, float $min, float $max): ?float {
        if ($value === '' || $value === null) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < $min || $value > $max) {
            return null;
        }

        return round((float) $value, 7);
    }

    private function search_text(array $parts): string {
        $parts = array_filter(array_map(static function ($part) {
            return trim(wp_strip_all_tags((string) $part));
        }, $parts));

        return implode(' ', array_unique($parts));
    }

    private function truncate_string(string $value, int $limit): string {
        $value = trim(wp_strip_all_tags($value));

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit);
        }

        return substr($value, 0, $limit);
    }
}
