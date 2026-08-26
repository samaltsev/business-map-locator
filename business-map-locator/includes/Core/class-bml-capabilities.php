<?php
if (!defined('ABSPATH')) {
    exit;
}

final class BML_Capabilities {
    private const VERSION = '1.3.0';
    private const OPTION = 'bml_capabilities_version';
    private const DEPRECATED_CAPABILITIES = [
        'read_bml_locations',
    ];

    public static function maybeInstall(): void {
        if (get_option(self::OPTION, '') === self::VERSION) {
            return;
        }

        self::install();
    }

    public static function install(): void {
        $role = get_role('administrator');
        if (!$role) {
            return;
        }

        foreach (\BusinessMapLocator\WordPress\Capabilities::administratorCaps() as $capability) {
            if ($role->has_cap($capability)) {
                continue;
            }

            $role->add_cap($capability);
        }

        update_option(self::OPTION, self::VERSION, false);
    }

    public static function uninstall(): void {
        $role = get_role('administrator');
        if ($role) {
            foreach (array_merge(\BusinessMapLocator\WordPress\Capabilities::administratorCaps(), self::DEPRECATED_CAPABILITIES) as $capability) {
                $role->remove_cap($capability);
            }
        }

        delete_option(self::OPTION);
    }
}
