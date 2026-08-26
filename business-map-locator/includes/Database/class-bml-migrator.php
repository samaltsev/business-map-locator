<?php
if (!defined('ABSPATH')) {
    exit;
}

final class BML_Migrator {
    private const BATCH_SIZE = 100;

    public function hooks(): void {
        add_action('admin_init', [$this, 'maybe_upgrade']);
        add_action('admin_init', [$this, 'maybe_rebuild_index'], 20);
    }

    public function maybe_upgrade(): void {
        if (!current_user_can(\BusinessMapLocator\WordPress\Capabilities::MANAGE_SETTINGS) || !BML_Database::needs_upgrade()) {
            return;
        }

        $this->run();
    }

    public function run(): bool {
        return BML_Database::install();
    }

    public function maybe_rebuild_index(): void {
        if (!current_user_can(\BusinessMapLocator\WordPress\Capabilities::MANAGE_SETTINGS) || !BML_Database::rebuild_required()) {
            return;
        }

        if (!BML_Database::table_exists(BML_Database::locations_index_table())) {
            return;
        }

        $offset = max(0, (int) get_option(BML_Database::REBUILD_OFFSET_OPTION, 0));
        $result = (new BML_Location_Index())->rebuild($offset, self::BATCH_SIZE);

        if (!empty($result['done'])) {
            BML_Database::mark_rebuild_complete();
            return;
        }

        update_option(
            BML_Database::REBUILD_OFFSET_OPTION,
            $offset + (int) $result['processed'],
            false
        );
    }
}
