<?php
declare(strict_types=1);

use BusinessMapLocator\Import\Dto\ImportJob;
use BusinessMapLocator\Import\Mapping\ImportMapper;
use BusinessMapLocator\Import\Processing\LocationImporter;
use PHPUnit\Framework\TestCase;

final class LocationImporterVisibilityContractTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['bml_test_posts'] = [];
        $GLOBALS['bml_test_meta'] = [];
        $GLOBALS['bml_test_indexed'] = [];
    }

    public function testCreateVisibleZeroStoresCanonicalHiddenState(): void
    {
        $id = $this->import(['external_id', 'title', 'lat', 'lng', 'visible'], ['STORE-1', 'Store', '53.9', '27.56', '0']);

        self::assertSame('hidden', $GLOBALS['bml_test_meta'][$id]['bml_operational_status']);
        self::assertSame('0', $GLOBALS['bml_test_meta'][$id]['bml_visible']);
        self::assertSame('publish', $GLOBALS['bml_test_posts'][$id]->post_status);
    }

    /** @dataProvider createVisibilityCases */
    public function testCreateSynchronizesCanonicalAndLegacyVisibility(array $headers, array $row, string $status, string $visible): void
    {
        $id = $this->import($headers, $row);

        self::assertSame($status, $GLOBALS['bml_test_meta'][$id]['bml_operational_status']);
        self::assertSame($visible, $GLOBALS['bml_test_meta'][$id]['bml_visible']);
    }

    /** @return array<string, array{array<int, string>, array<int, string>, string, string}> */
    public static function createVisibilityCases(): array
    {
        return [
            'explicit active' => [['external_id', 'title', 'lat', 'lng', 'operational_status'], ['STORE-1', 'Store', '53.9', '27.56', 'active'], 'active', '1'],
            'explicit temporarily closed' => [['external_id', 'title', 'lat', 'lng', 'operational_status'], ['STORE-1', 'Store', '53.9', '27.56', 'temporarily_closed'], 'temporarily_closed', '1'],
            'explicit hidden' => [['external_id', 'title', 'lat', 'lng', 'operational_status'], ['STORE-1', 'Store', '53.9', '27.56', 'hidden'], 'hidden', '0'],
            'visible one' => [['external_id', 'title', 'lat', 'lng', 'visible'], ['STORE-1', 'Store', '53.9', '27.56', '1'], 'active', '1'],
            'canonical hidden wins visible one' => [['external_id', 'title', 'lat', 'lng', 'operational_status', 'visible'], ['STORE-1', 'Store', '53.9', '27.56', 'hidden', '1'], 'hidden', '0'],
            'canonical temporary closed wins visible zero' => [['external_id', 'title', 'lat', 'lng', 'operational_status', 'visible'], ['STORE-1', 'Store', '53.9', '27.56', 'temporarily_closed', '0'], 'temporarily_closed', '1'],
            'default' => [['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Store', '53.9', '27.56'], 'active', '1'],
        ];
    }

    /** @dataProvider updateVisibilityCases */
    public function testUpdateAppliesLegacyVisibleWithoutErasingTemporaryClosure(string $existing, string $visible, string $expected): void
    {
        $this->existingLocation('STORE-1', 'draft', $existing, $existing === 'hidden' ? '0' : '1');
        $id = $this->import(['external_id', 'title', 'lat', 'lng', 'visible'], ['STORE-1', 'Store changed', '53.9', '27.56', $visible]);

        self::assertSame(10, $id);
        self::assertSame($expected, $GLOBALS['bml_test_meta'][$id]['bml_operational_status']);
        self::assertSame($expected === 'hidden' ? '0' : '1', $GLOBALS['bml_test_meta'][$id]['bml_visible']);
    }

    /** @return array<string, array{string, string, string}> */
    public static function updateVisibilityCases(): array
    {
        return [
            'active remains active with visible one' => ['active', '1', 'active'],
            'temporarily closed remains temporarily closed with visible one' => ['temporarily_closed', '1', 'temporarily_closed'],
            'hidden becomes active with visible one' => ['hidden', '1', 'active'],
            'active becomes hidden with visible zero' => ['active', '0', 'hidden'],
            'temporarily closed becomes hidden with visible zero' => ['temporarily_closed', '0', 'hidden'],
        ];
    }

    public function testUpdateWithoutVisibilityFieldsPreservesOperationalAndPublicationStates(): void
    {
        $this->existingLocation('STORE-1', 'draft', 'temporarily_closed', '1');
        $id = $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Store changed', '53.9', '27.56']);

        self::assertSame('temporarily_closed', $GLOBALS['bml_test_meta'][$id]['bml_operational_status']);
        self::assertSame('draft', $GLOBALS['bml_test_posts'][$id]->post_status);
    }

    public function testUpdateWithExplicitPublicationStatusChangesIt(): void
    {
        $this->existingLocation('STORE-1', 'draft', 'active', '1');
        $id = $this->import(['external_id', 'title', 'lat', 'lng', 'status'], ['STORE-1', 'Store changed', '53.9', '27.56', 'publish']);

        self::assertSame('publish', $GLOBALS['bml_test_posts'][$id]->post_status);
    }

    public function testUpdateWithExplicitDraftPublicationStatusChangesIt(): void
    {
        $this->existingLocation('STORE-1', 'publish', 'active', '1');
        $id = $this->import(['external_id', 'title', 'lat', 'lng', 'status'], ['STORE-1', 'Store changed', '53.9', '27.56', 'draft']);

        self::assertSame('draft', $GLOBALS['bml_test_posts'][$id]->post_status);
    }

    public function testDryRunRejectsInvalidExplicitOperationalStatus(): void
    {
        $job = new ImportJob([
            'headers' => ['external_id', 'title', 'lat', 'lng', 'operational_status'],
            'duplicateExternalIds' => [],
            'dryRun' => true,
            'id' => 1,
            'processingErrors' => 0,
            'wouldFail' => 0,
        ]);
        $result = (new LocationImporter(new ImportMapper()))->importRow(['STORE-1', 'Store', '53.9', '27.56', 'banana'], $job);

        self::assertSame('invalid_location_row', $result['code']);
        self::assertSame(1, $result['job']['wouldFail']);
        self::assertSame([], $GLOBALS['bml_test_posts']);
    }

    private function existingLocation(string $externalId, string $postStatus, string $operationalStatus, string $visible): void
    {
        $post = new WP_Post();
        $post->ID = 10;
        $post->post_type = 'bml_location';
        $post->post_title = 'Existing store';
        $post->post_status = $postStatus;
        $GLOBALS['bml_test_posts'][10] = $post;
        $GLOBALS['bml_test_meta'][10] = [
            'bml_external_id' => $externalId,
            'bml_operational_status' => $operationalStatus,
            'bml_visible' => $visible,
        ];
    }

    /** @param array<int, string> $headers @param array<int, string> $row */
    private function import(array $headers, array $row): int
    {
        $job = new ImportJob(['headers' => $headers, 'duplicateExternalIds' => [], 'dryRun' => false, 'id' => 1]);
        $result = (new LocationImporter(new ImportMapper()))->importRow($row, $job);

        self::assertArrayNotHasKey('error', $result);

        return (int) $result['locationId'];
    }
}
