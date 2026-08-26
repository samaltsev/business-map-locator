<?php
if (!defined('ABSPATH')) {
    exit;
}

final class BML_Database {
    public const VERSION = '1.3.3';
    public const VERSION_OPTION = 'bml_database_version';
    public const REBUILD_OFFSET_OPTION = 'bml_index_rebuild_offset';
    public const REBUILD_REQUIRED_OPTION = 'bml_index_rebuild_required';

    public static function locations_index_table(): string {
        global $wpdb;

        return $wpdb->prefix . 'bml_locations_index';
    }

    public static function import_job_rows_table(): string {
        global $wpdb;

        return $wpdb->prefix . 'bml_import_job_rows';
    }

    public static function import_job_events_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'bml_import_job_events';
    }

    public static function import_jobs_table(): string {
        global $wpdb;

        return $wpdb->prefix . 'bml_import_jobs';
    }

    public static function install(): bool {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta(BML_Schema::locations_index_sql());
        dbDelta(BML_Schema::import_jobs_sql());
        dbDelta(BML_Schema::import_job_rows_sql());
        dbDelta(BML_Schema::import_job_events_sql());
        (new \BusinessMapLocator\Infrastructure\Database\Migration\SchemaMigrator())->migrate();

        if (!self::table_exists(self::locations_index_table()) || !self::table_exists(self::import_jobs_table()) || !self::table_exists(self::import_job_rows_table()) || !self::table_exists(self::import_job_events_table())) {
            return false;
        }

        update_option(self::VERSION_OPTION, self::VERSION, false);
        update_option(self::REBUILD_REQUIRED_OPTION, 1, false);

        if (get_option(self::REBUILD_OFFSET_OPTION, null) === null) {
            add_option(self::REBUILD_OFFSET_OPTION, 0, '', false);
        }

        return true;
    }

    public static function needs_upgrade(): bool {
        $stored = (string) get_option(self::VERSION_OPTION, '');

        return $stored === ''
            || version_compare($stored, self::VERSION, '<')
            || !self::table_exists(self::locations_index_table())
            || !self::table_exists(self::import_jobs_table())
            || !self::table_exists(self::import_job_rows_table())
            || !self::table_exists(self::import_job_events_table())
            || (int) get_option(\BusinessMapLocator\Infrastructure\Database\Migration\SchemaMigrator::OPTION, 0) < \BusinessMapLocator\Infrastructure\Database\Migration\SchemaMigrator::CURRENT_VERSION;
    }

    public static function table_exists(string $table): bool {
        global $wpdb;

        $pattern = $wpdb->esc_like($table);
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $pattern));

        return is_string($found) && $found === $table;
    }

    public static function index_ready(): bool {
        return self::table_exists(self::locations_index_table())
            && !self::rebuild_required();
    }

    public static function rebuild_required(): bool {
        return (bool) get_option(self::REBUILD_REQUIRED_OPTION, false);
    }

    public static function mark_rebuild_complete(): void {
        update_option(self::REBUILD_REQUIRED_OPTION, 0, false);
        delete_option(self::REBUILD_OFFSET_OPTION);
    }
}
