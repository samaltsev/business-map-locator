<?php
if (!defined('ABSPATH')) {
    exit;
}

use BusinessMapLocator\Plugin;
use BusinessMapLocator\Settings\Settings;

/**
 * Backward-compatibility facade.
 *
 * New bootstrapping, lifecycle and WordPress registrations live in src/.
 * This class remains temporarily available for extensions and legacy modules
 * that still call BML_Plugin::settings() or sanitize_tile_url().
 */
final class BML_Plugin
{
    public const DEFAULT_TILE_URL = Settings::DEFAULT_TILE_URL;

    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        Plugin::instance()->boot();
    }

    public static function activate(): void
    {
        Plugin::activate();
    }

    public static function deactivate(): void
    {
        Plugin::deactivate();
    }

    /** @return array<string, mixed> */
    public static function settings(): array
    {
        /** @var Settings $settings */
        $settings = Plugin::instance()->container()->get(Settings::class);

        return $settings->all();
    }

    public static function sanitize_tile_url(mixed $value): string
    {
        /** @var Settings $settings */
        $settings = Plugin::instance()->container()->get(Settings::class);

        return $settings->sanitizeTileUrl($value);
    }

    /**
     * Compatibility methods retained for third-party code during Sprint 1.
     */
    public function register_content_types(): void
    {
        Plugin::instance()->container()->get(BusinessMapLocator\WordPress\ContentTypes::class)->register();
    }

    public function register_meta(): void
    {
        Plugin::instance()->container()->get(BusinessMapLocator\WordPress\MetaRegistrar::class)->register();
    }

    public function register_block(): void
    {
        Plugin::instance()->container()->get(BusinessMapLocator\WordPress\BlockRegistrar::class)->register();
    }

    public function load_textdomain(): void
    {
        Plugin::instance()->container()->get(BusinessMapLocator\WordPress\TextDomain::class)->load();
    }

    public function privacy_policy_content(): void
    {
        Plugin::instance()->container()->get(BusinessMapLocator\WordPress\PrivacyPolicy::class)->register();
    }
}
