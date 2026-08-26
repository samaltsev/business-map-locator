<?php
if (!defined('ABSPATH')) {
    exit;
}

final class BML_Schema {
    public static function locations_index_sql(): string {
        global $wpdb;

        $table = BML_Database::locations_index_table();
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL DEFAULT '',
            address VARCHAR(500) NOT NULL DEFAULT '',
            city VARCHAR(191) NOT NULL DEFAULT '',
            city_slug VARCHAR(191) NOT NULL DEFAULT '',
            category VARCHAR(191) NOT NULL DEFAULT '',
            category_slug VARCHAR(191) NOT NULL DEFAULT '',
            region VARCHAR(191) NOT NULL DEFAULT '',
            country VARCHAR(191) NOT NULL DEFAULT '',
            postcode VARCHAR(64) NOT NULL DEFAULT '',
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            image_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            phone VARCHAR(100) NOT NULL DEFAULT '',
            email VARCHAR(191) NOT NULL DEFAULT '',
            website VARCHAR(255) NOT NULL DEFAULT '',
            hours VARCHAR(255) NOT NULL DEFAULT '',
            excerpt TEXT NULL,
            operational_status VARCHAR(50) NOT NULL DEFAULT 'open',
            visibility VARCHAR(50) NOT NULL DEFAULT 'public',
            search_text LONGTEXT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id),
            KEY city_slug (city_slug),
            KEY category_slug (category_slug),
            KEY operational_status (operational_status),
            KEY coordinates (latitude, longitude)
        ) {$charset_collate};";
    }
    public static function import_jobs_sql(): string {
        global $wpdb;

        $table = BML_Database::import_jobs_table();
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            token_hash CHAR(64) NOT NULL,
            owner_user_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'prepared',
            file_path TEXT NOT NULL,
            headers_json LONGTEXT NULL,
            position BIGINT UNSIGNED NOT NULL DEFAULT 0,
            read_position BIGINT UNSIGNED NOT NULL DEFAULT 0,
            committed_position BIGINT UNSIGNED NOT NULL DEFAULT 0,
            total_rows BIGINT UNSIGNED NOT NULL DEFAULT 0,
            processed_rows BIGINT UNSIGNED NOT NULL DEFAULT 0,
            read_rows BIGINT UNSIGNED NOT NULL DEFAULT 0,
            committed_rows BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_rows BIGINT UNSIGNED NOT NULL DEFAULT 0,
            updated_rows BIGINT UNSIGNED NOT NULL DEFAULT 0,
            skipped_rows BIGINT UNSIGNED NOT NULL DEFAULT 0,
            error_rows BIGINT UNSIGNED NOT NULL DEFAULT 0,
            inspection_errors BIGINT UNSIGNED NOT NULL DEFAULT 0,
            dry_run TINYINT(1) NOT NULL DEFAULT 0,
            payload_json LONGTEXT NULL,
            version BIGINT UNSIGNED NOT NULL DEFAULT 1,
            locked_by VARCHAR(64) NOT NULL DEFAULT '',
            locked_until DATETIME NULL,
            started_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token_hash (token_hash),
            KEY owner_status (owner_user_id, status),
            KEY status_expires (status, expires_at),
            KEY expires_at (expires_at)
        ) {$charset_collate};";
    }

    public static function import_job_rows_sql(): string {
        global $wpdb;

        $table = BML_Database::import_job_rows_table();
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,
            source_row_number BIGINT UNSIGNED NOT NULL,
            row_hash CHAR(64) NOT NULL,
            action VARCHAR(32) NOT NULL DEFAULT '',
            location_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'processing',
            error_code VARCHAR(64) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY job_row (job_id, source_row_number),
            UNIQUE KEY job_hash (job_id, row_hash),
            KEY job_status (job_id, status)
        ) {$charset_collate};";
    }

    public static function import_job_events_sql(): string {
        global $wpdb;
        $table = BML_Database::import_job_events_table();
        $charset_collate = $wpdb->get_charset_collate();
        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,
            level VARCHAR(20) NOT NULL DEFAULT 'info',
            event_code VARCHAR(64) NOT NULL DEFAULT '',
            source_row_number BIGINT UNSIGNED NULL,
            message VARCHAR(1000) NOT NULL DEFAULT '',
            context_json TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY job_created (job_id, created_at),
            KEY job_level (job_id, level),
            KEY event_code (event_code)
        ) {$charset_collate};";
    }

}
