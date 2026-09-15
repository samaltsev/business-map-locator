<?php
declare(strict_types=1);

use BusinessMapLocator\Import\Dto\ImportJob;
use BusinessMapLocator\Import\Mapping\ImportMapper;
use BusinessMapLocator\Import\Processing\LocationImporter;
use PHPUnit\Framework\TestCase;

final class LocationImporterUpdateSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['bml_test_posts'] = [];
        $GLOBALS['bml_test_meta'] = [];
        $GLOBALS['bml_test_object_terms'] = [];
        $GLOBALS['bml_test_indexed'] = [];
    }

    public function testMapperKeepsAbsentAndEmptyColumnsDistinct(): void
    {
        $mapper = new ImportMapper();

        self::assertArrayNotHasKey('phone', $mapper->map(['title', 'lat', 'lng'], ['Store', '53.9', '27.56']));
        self::assertSame('', $mapper->map(['title', 'lat', 'lng', 'phone'], ['Store', '53.9', '27.56', ''])['phone']);
    }

    public function testDefaultUpdatePreservesOmittedOptionalMetadataAndProMetadata(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Changed', '54.0', '27.6']);

        self::assertSame('Saved address', $GLOBALS['bml_test_meta'][10]['bml_address']);
        self::assertSame('123', $GLOBALS['bml_test_meta'][10]['bml_phone']);
        self::assertSame('pro-value', $GLOBALS['bml_test_meta'][10]['bml_pro_meta']);
    }

    public function testDefaultUpdateWritesMappedNonEmptyOptionalMetadata(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng', 'phone', 'email'], ['STORE-1', 'Changed', '54.0', '27.6', '456', 'new@example.test']);

        self::assertSame('456', $GLOBALS['bml_test_meta'][10]['bml_phone']);
        self::assertSame('new@example.test', $GLOBALS['bml_test_meta'][10]['bml_email']);
    }

    public function testDefaultUpdatePreservesMappedEmptyOptionalMetadata(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng', 'phone'], ['STORE-1', 'Changed', '54.0', '27.6', '']);

        self::assertSame('123', $GLOBALS['bml_test_meta'][10]['bml_phone']);
    }

    public function testOverwriteMappedClearsMappedEmptyOptionalMetadata(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng', 'phone'], ['STORE-1', 'Changed', '54.0', '27.6', ''], 'overwrite_mapped');

        self::assertSame('', $GLOBALS['bml_test_meta'][10]['bml_phone']);
    }

    public function testOverwriteMappedStillPreservesOmittedColumns(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Changed', '54.0', '27.6'], 'overwrite_mapped');

        self::assertSame('123', $GLOBALS['bml_test_meta'][10]['bml_phone']);
    }

    public function testUpdatePreservesExternalIdWhenTheColumnIsOmitted(): void
    {
        $this->existingLocation();
        $GLOBALS['bml_test_meta'][10]['bml_import_fingerprint'] = (new ImportMapper())->fingerprint('Existing', '', '53.9', '27.56');
        $this->import(['title', 'lat', 'lng'], ['Existing', '53.9', '27.56']);

        self::assertSame('STORE-1', $GLOBALS['bml_test_meta'][10]['bml_external_id']);
    }

    public function testUpdateWithoutStatusFieldsPreservesPublicationAndOperationalStatus(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Changed', '54.0', '27.6']);

        self::assertSame('draft', $GLOBALS['bml_test_posts'][10]->post_status);
        self::assertSame('temporarily_closed', $GLOBALS['bml_test_meta'][10]['bml_operational_status']);
    }

    public function testDefaultUpdatePreservesOperationalStatusForAnEmptyVisibleCell(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng', 'visible'], ['STORE-1', 'Changed', '54.0', '27.6', '']);

        self::assertSame('temporarily_closed', $GLOBALS['bml_test_meta'][10]['bml_operational_status']);
        self::assertSame('1', $GLOBALS['bml_test_meta'][10]['bml_visible']);
    }

    public function testOpenOperationalStatusIsNormalizedWhenExplicitlyMapped(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng', 'operational_status'], ['STORE-1', 'Changed', '54.0', '27.6', 'open']);

        self::assertSame('active', $GLOBALS['bml_test_meta'][10]['bml_operational_status']);
        self::assertSame('1', $GLOBALS['bml_test_meta'][10]['bml_visible']);
    }

    public function testUpdateWithoutTaxonomyColumnsPreservesAssignedTerms(): void
    {
        $this->existingLocation();
        $GLOBALS['bml_test_object_terms'][10] = ['bml_category' => [7], 'bml_city' => [8]];
        $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Changed', '54.0', '27.6']);

        self::assertSame([7], $GLOBALS['bml_test_object_terms'][10]['bml_category']);
        self::assertSame([8], $GLOBALS['bml_test_object_terms'][10]['bml_city']);
    }

    public function testCreateOnlySkipsMatchingLocation(): void
    {
        $this->existingLocation();
        $result = $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Changed', '54.0', '27.6'], 'create_only');

        self::assertSame('skipped', $result['action']);
        self::assertSame('Existing', $GLOBALS['bml_test_posts'][10]->post_title);
    }

    public function testCreateOnlyCreatesNewLocation(): void
    {
        $result = $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-2', 'New', '54.0', '27.6'], 'create_only');

        self::assertSame('created', $result['action']);
        self::assertSame('New', $GLOBALS['bml_test_posts'][1]->post_title);
    }

    public function testUpdateOnlyUpdatesMatchingLocation(): void
    {
        $this->existingLocation();
        $result = $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Changed', '54.0', '27.6'], 'update_only');

        self::assertSame('updated', $result['action']);
        self::assertSame('Changed', $GLOBALS['bml_test_posts'][10]->post_title);
    }

    public function testUpdateOnlySkipsNewLocation(): void
    {
        $result = $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-2', 'New', '54.0', '27.6'], 'update_only');

        self::assertSame('skipped', $result['action']);
        self::assertSame([], $GLOBALS['bml_test_posts']);
    }

    public function testDryRunUsesTheSameCreateOnlySkipDecision(): void
    {
        $this->existingLocation();
        $result = $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Changed', '54.0', '27.6'], 'create_only', true);

        self::assertSame('would_skip', $result['action']);
        self::assertSame(1, $result['job']['wouldSkip']);
        self::assertSame('Existing', $GLOBALS['bml_test_posts'][10]->post_title);
    }

    public function testIndexUpdatesForWritesButNotPolicySkips(): void
    {
        $this->existingLocation();
        $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Changed', '54.0', '27.6']);
        $this->import(['external_id', 'title', 'lat', 'lng'], ['STORE-1', 'Ignored', '54.0', '27.6'], 'create_only');

        self::assertSame([10], $GLOBALS['bml_test_indexed']);
    }

    private function existingLocation(): void
    {
        $post = new WP_Post();
        $post->ID = 10;
        $post->post_type = 'bml_location';
        $post->post_title = 'Existing';
        $post->post_status = 'draft';
        $GLOBALS['bml_test_posts'][10] = $post;
        $GLOBALS['bml_test_meta'][10] = [
            'bml_external_id' => 'STORE-1',
            'bml_import_fingerprint' => (new ImportMapper())->fingerprint('Existing', 'Saved address', '53.9', '27.56'),
            'bml_address' => 'Saved address',
            'bml_phone' => '123',
            'bml_email' => 'old@example.test',
            'bml_website' => 'https://old.example.test',
            'bml_operational_status' => 'temporarily_closed',
            'bml_visible' => '1',
            'bml_pro_meta' => 'pro-value',
        ];
    }

    /** @param list<string> $headers @param list<string> $row */
    private function import(array $headers, array $row, string $policy = 'non_empty_only', bool $dryRun = false): array
    {
        $job = new ImportJob([
            'headers' => $headers,
            'duplicateExternalIds' => [],
            'dryRun' => $dryRun,
            'updatePolicy' => $policy,
            'id' => 1,
            'skipped' => 0,
            'wouldSkip' => 0,
        ]);

        return (new LocationImporter(new ImportMapper()))->importRow($row, $job);
    }
}
