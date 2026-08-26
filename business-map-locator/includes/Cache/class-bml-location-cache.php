<?php
if (!defined('ABSPATH')) { exit; }

final class BML_Location_Cache {
    private const VERSION_OPTION = 'bml_rest_cache_version';

    public static function enabled(): bool {
        $settings = BML_Plugin::settings();
        return !empty($settings['rest_cache']);
    }

    public static function get(string $bucket, array $params) {
        if (!self::enabled()) {
            return false;
        }
        return get_transient(self::key($bucket, $params));
    }

    public static function set(string $bucket, array $params, array $value): void {
        if (!self::enabled()) {
            return;
        }
        $settings = BML_Plugin::settings();
        $ttl = max(MINUTE_IN_SECONDS, (int) ($settings['cache_ttl'] ?? HOUR_IN_SECONDS));
        set_transient(self::key($bucket, $params), $value, $ttl);
    }

    public static function invalidate(): void {
        update_option(self::VERSION_OPTION, self::version() + 1, false);
    }

    private static function version(): int {
        return max(1, (int) get_option(self::VERSION_OPTION, 1));
    }

    private static function key(string $bucket, array $params): string {
        ksort($params);
        return 'bml_rest_' . md5($bucket . '|' . self::version() . '|' . wp_json_encode($params));
    }
}
