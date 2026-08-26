<?php
declare(strict_types=1);

namespace BusinessMapLocator\Lifecycle;

use BusinessMapLocator\Import\ImportCleanupScheduler;
use BusinessMapLocator\Settings\Settings;
use BusinessMapLocator\WordPress\ContentTypes;

final readonly class Activator
{
    public function __construct(
        private Settings $settings,
        private ContentTypes $contentTypes
    ) {
    }

    public function run(): void
    {
        $this->settings->installDefaults();
        $this->contentTypes->register();

        \BML_Database::install();
        \BML_Capabilities::install();
        ImportCleanupScheduler::schedule();

        flush_rewrite_rules();
    }
}
