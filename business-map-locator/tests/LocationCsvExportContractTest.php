<?php
declare(strict_types=1);

use BusinessMapLocator\Export\LocationCsvExporter;
use PHPUnit\Framework\TestCase;

final class LocationCsvExportContractTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['bml_test_posts'] = [];
        $GLOBALS['bml_test_meta'] = [];
    }

    /** @dataProvider statuses */
    public function testExportDerivesConsistentOperationalAndLegacyVisibility(string $canonical, string $legacyVisible, string $expected, string $visible): void
    {
        $post = new WP_Post();
        $post->ID = 9;
        $post->post_type = 'bml_location';
        $post->post_status = 'draft';
        $post->post_title = 'Store';
        $GLOBALS['bml_test_posts'][9] = $post;
        $GLOBALS['bml_test_meta'][9] = ['bml_operational_status' => $canonical, 'bml_visible' => $legacyVisible];

        $method = new ReflectionMethod(LocationCsvExporter::class, 'row');
        $method->setAccessible(true);
        $row = $method->invoke(new LocationCsvExporter(), 9);

        self::assertSame('draft', $row['status']);
        self::assertSame($expected, $row['operational_status']);
        self::assertSame($visible, $row['visible']);
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function statuses(): array
    {
        return [
            'active' => ['active', '1', 'active', '1'],
            'temporarily closed' => ['temporarily_closed', '0', 'temporarily_closed', '1'],
            'hidden' => ['hidden', '1', 'hidden', '0'],
            'legacy hidden' => ['', '0', 'hidden', '0'],
        ];
    }

    public function testFieldsContainTheRoundTripContract(): void
    {
        self::assertContains('status', LocationCsvExporter::FIELDS);
        self::assertContains('operational_status', LocationCsvExporter::FIELDS);
        self::assertContains('visible', LocationCsvExporter::FIELDS);
    }
}
