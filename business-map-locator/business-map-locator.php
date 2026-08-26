<?php
declare(strict_types=1);

/**
 * Plugin Name: Business Map Locator
 * Description: Modern business and store locator with OpenStreetMap, location search, filters, CSV import, REST API, shortcode and Gutenberg block.
 * Version: 1.3.2-beta40.7
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Business Map Locator
 * Text Domain: business-map-locator
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

if (version_compare(PHP_VERSION, '8.1', '<')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Business Map Locator requires PHP 8.1 or newer.',
            'business-map-locator'
        );
        echo '</p></div>';
    });

    return;
}

define('BML_VERSION', '1.3.2-beta40.7');
define('BML_FILE', __FILE__);
define('BML_DIR', plugin_dir_path(__FILE__));
define('BML_URL', plugin_dir_url(__FILE__));

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'BusinessMapLocator\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_readable($file)) {
            require_once $file;
        }
    });

    require_once __DIR__ . '/src/Legacy/LegacyClassLoader.php';
    require_once __DIR__ . '/src/Plugin.php';
}

register_activation_hook(__FILE__, [BusinessMapLocator\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [BusinessMapLocator\Plugin::class, 'deactivate']);

add_action(
    'plugins_loaded',
    static function (): void {
        BusinessMapLocator\Plugin::instance()->boot();
    }
);
