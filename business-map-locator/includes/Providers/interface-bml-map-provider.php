<?php
if (!defined('ABSPATH')) { exit; }

interface BML_Map_Provider_Interface {
    public function get_id(): string;

    public function is_configured(array $settings): bool;

    public function register_assets(array $settings): void;

    public function enqueue_assets(array $settings): void;

    public function get_client_config(array $settings): array;
}
