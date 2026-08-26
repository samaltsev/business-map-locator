<?php
declare(strict_types=1);

namespace BusinessMapLocator\Lifecycle;

use BusinessMapLocator\Import\ImportCleanupScheduler;

final class Deactivator
{
    public function run(): void
    {
        ImportCleanupScheduler::unschedule();
        flush_rewrite_rules();
    }
}
