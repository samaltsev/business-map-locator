<?php
declare(strict_types=1);

namespace BusinessMapLocator\Legacy;

final class LegacyClassLoader
{
    private static bool $registered = false;

    public static function register(string $pluginDir): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        $pluginDir = rtrim($pluginDir, '/\\') . DIRECTORY_SEPARATOR;

        spl_autoload_register(static function (string $class) use ($pluginDir): void {
            if (!str_starts_with($class, 'BML_')) {
                return;
            }

            $map = self::classMap();
            if (!isset($map[$class])) {
                return;
            }

            require_once $pluginDir . $map[$class];
        });
    }

    /**
     * @return array<string, string>
     */
    private static function classMap(): array
    {
        return [
            'BML_Plugin' => 'includes/Core/class-bml-plugin.php',
            'BML_REST' => 'includes/REST/class-bml-rest.php',
            'BML_Location_Cache' => 'includes/Cache/class-bml-location-cache.php',
            'BML_Cache_Invalidator' => 'includes/Cache/class-bml-cache-invalidator.php',
            'BML_Frontend' => 'includes/Frontend/class-bml-frontend.php',
            'BML_Assets' => 'includes/Core/class-bml-assets.php',
            'BML_Diagnostics' => 'includes/Core/class-bml-diagnostics.php',
            'BML_Capabilities' => 'includes/Core/class-bml-capabilities.php',
            'BML_Shortcode' => 'includes/Shortcodes/class-bml-shortcode.php',
            'BML_Locator_Renderer' => 'includes/Frontend/class-bml-locator-renderer.php',
            'BML_Map_Provider_Interface' => 'includes/Providers/interface-bml-map-provider.php',
            'BML_Provider_Registry' => 'includes/Providers/class-bml-provider-registry.php',
            'BML_OpenStreetMap_Provider' => 'includes/Providers/class-bml-openstreetmap-provider.php',
            'BML_GoogleMaps_Provider' => 'includes/Providers/class-bml-google-maps-provider.php',
            'BML_Database' => 'includes/Database/class-bml-database.php',
            'BML_Schema' => 'includes/Database/class-bml-schema.php',
            'BML_Migrator' => 'includes/Database/class-bml-migrator.php',
            'BML_Location_Index' => 'includes/Database/class-bml-location-index.php',
            'BML_Location_Indexer' => 'includes/Database/class-bml-location-indexer.php',
        ];
    }
}
