<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import;

final class ImportCleanupScheduler
{
    public const HOOK = 'bml_cleanup_expired_imports';

    public function register(): void
    {
        add_action(self::HOOK, [ImportManager::class, 'cleanupExpiredJobs']);
        self::schedule();
    }

    public static function schedule(): void
    {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::HOOK);
        }
    }

    public static function unschedule(): void
    {
        wp_clear_scheduled_hook(self::HOOK);
    }
}
