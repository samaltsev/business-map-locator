<?php
declare(strict_types=1);

namespace BusinessMapLocator\WordPress;

final class TextDomain
{
    public function load(): void
    {
        load_plugin_textdomain(
            'business-map-locator',
            false,
            dirname(plugin_basename(BML_FILE)) . '/languages'
        );
    }
}
