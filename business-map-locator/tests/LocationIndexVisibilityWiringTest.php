<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LocationIndexVisibilityWiringTest extends TestCase
{
    public function testIndexResolvesLegacyVisibilityAndIndexerInvalidatesIt(): void
    {
        $root = dirname(__DIR__);
        $index = (string) file_get_contents($root . '/includes/Database/class-bml-location-index.php');
        $indexer = (string) file_get_contents($root . '/includes/Database/class-bml-location-indexer.php');

        self::assertStringContainsString('OperationalStatusResolver::resolve', $index);
        self::assertStringContainsString("get_post_meta(\$post_id, 'bml_visible', true)", $index);
        self::assertStringContainsString("'bml_visible'", $indexer);
    }
}
