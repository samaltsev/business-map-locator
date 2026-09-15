<?php
declare(strict_types=1);

use BusinessMapLocator\Admin\Location\LocationWriteService;
use PHPUnit\Framework\TestCase;

final class LocationAreaWriteTest extends TestCase
{
    private LocationWriteService $writer;

    protected function setUp(): void
    {
        $post = new WP_Post();
        $post->ID = 42; $post->post_type = 'bml_location'; $post->post_status = 'draft'; $post->post_title = 'Location';
        $GLOBALS['bml_test_posts'] = [42 => $post];
        $GLOBALS['bml_test_meta'] = [42 => []];
        $GLOBALS['bml_test_terms'] = [
            'bml_category' => [7 => $this->term(7, 'Category')],
            'bml_city' => [8 => $this->term(8, 'City')],
            'bml_area' => [10 => $this->term(10, 'Region'), 11 => $this->term(11, 'Area A', 10), 12 => $this->term(12, 'Area B'), 13 => $this->term(13, 'Area C')],
        ];
        $GLOBALS['bml_test_term_meta'] = [12 => ['bml_area_active' => '0']];
        $GLOBALS['bml_test_post_terms'] = [42 => ['bml_category' => [7], 'bml_city' => [8], 'bml_area' => []]];
        $GLOBALS['bml_test_indexed'] = []; $GLOBALS['bml_test_relation_index_rows'] = []; $GLOBALS['bml_test_cache_invalidations'] = 0;
        $GLOBALS['bml_test_options'] = []; $GLOBALS['bml_test_wp_set_object_terms_calls'] = 0;
        $this->writer = new LocationWriteService(new BML_Location_Index());
    }

    public function testAssignsLeafAndSynchronizesDerivedRowsWithoutMigrationEvidence(): void
    {
        self::assertSame(42, $this->save(['area_id' => '11']));
        self::assertSame([11], $this->areas()); self::assertSame([8], $this->cities());
        self::assertSame(['bml_category' => [7], 'bml_city' => [8], 'bml_area' => [11]], $GLOBALS['bml_test_relation_index_rows'][42]);
        self::assertArrayNotHasKey('bml_area_migration_run', $GLOBALS['bml_test_options']);
        self::assertSame([], $GLOBALS['bml_test_meta'][42]);
    }

    public function testReplaceNormalizesMultiplicityAndReplacesDerivedAreaRow(): void
    {
        $GLOBALS['bml_test_post_terms'][42]['bml_area'] = [11, 12];
        self::assertSame(42, $this->save(['area_id' => '13']));
        self::assertSame([13], $this->areas()); self::assertSame([13], $GLOBALS['bml_test_relation_index_rows'][42]['bml_area']);
        self::assertSame([8], $GLOBALS['bml_test_relation_index_rows'][42]['bml_city']); self::assertSame([7], $GLOBALS['bml_test_relation_index_rows'][42]['bml_category']);
    }

    public function testExplicitClearRemovesOnlyAreaAndDerivedAreaRow(): void
    {
        $GLOBALS['bml_test_post_terms'][42]['bml_area'] = [11];
        self::assertSame(42, $this->save(['area_id' => '0']));
        self::assertSame([], $this->areas()); self::assertSame([], $GLOBALS['bml_test_relation_index_rows'][42]['bml_area']);
        self::assertSame([8], $this->cities()); self::assertSame([7], $GLOBALS['bml_test_relation_index_rows'][42]['bml_category']);
    }

    public function testMissingAreaFieldPreservesExistingInactiveAssignment(): void
    {
        $GLOBALS['bml_test_post_terms'][42]['bml_area'] = [12];
        self::assertSame(42, $this->save());
        self::assertSame([12], $this->areas()); self::assertSame([8], $this->cities());
    }

    public function testExistingInactiveAreaMayBeExplicitlyClearedOrReplacedButNotNewlyAssigned(): void
    {
        self::assertInstanceOf(WP_Error::class, $this->save(['area_id' => '12'])); self::assertSame([], $this->areas());
        $GLOBALS['bml_test_post_terms'][42]['bml_area'] = [12];
        self::assertSame(42, $this->save(['area_id' => '0'])); self::assertSame([], $this->areas());
        $GLOBALS['bml_test_post_terms'][42]['bml_area'] = [12];
        self::assertSame(42, $this->save(['area_id' => '11'])); self::assertSame([11], $this->areas());
    }

    public function testRejectsMultipleWrongTaxonomyNonLeafAndMalformedAreaWithoutMutation(): void
    {
        $GLOBALS['bml_test_post_terms'][42]['bml_area'] = [11];
        foreach ([[11, 13], '8', '10', 'invalid'] as $input) {
            $result = $this->save(['area_id' => $input]);
            self::assertInstanceOf(WP_Error::class, $result); self::assertSame([11], $this->areas()); self::assertSame([8], $this->cities());
        }
    }

    public function testEmptyAreaDatasetAllowsNoAreaAndDoesNotCreateTerms(): void
    {
        $GLOBALS['bml_test_terms']['bml_area'] = [];
        self::assertSame(42, $this->save(['area_id' => '0']));
        self::assertSame([], $this->areas()); self::assertSame([], $GLOBALS['bml_test_terms']['bml_area']);
    }

    public function testProductionEntryPointsWhitelistAreaForCanonicalWriter(): void
    {
        $admin = (string) file_get_contents(dirname(__DIR__) . '/src/Admin/Location/Action/SaveLocationAction.php');
        $ajax = (string) file_get_contents(dirname(__DIR__) . '/src/Admin/Ajax/LocationEditorAjaxController.php');
        self::assertStringContainsString("'area_id'", $admin); self::assertStringContainsString('$this->writer->save($id, $input)', $admin);
        self::assertStringContainsString("'area_id'", $ajax); self::assertStringContainsString('$this->writer->save($id, $input)', $ajax);
        self::assertStringNotContainsString('wp_set_object_terms($id, $input[\'area_id\']', $ajax);
    }

    public function testFreeEditorDefersAreaAssignmentWithoutChangingWriterContract(): void
    {
        $view = (string) file_get_contents(dirname(__DIR__) . '/src/Admin/Location/View/location-editor.php');

        self::assertStringNotContainsString('name="area_id"', $view);
        self::assertStringNotContainsString('No Area assigned', $view);
        self::assertSame(42, $this->save());
        self::assertSame([], $this->areas());
    }

    /** @param array<string, mixed> $input */
    private function save(array $input = []): int|WP_Error { return $this->writer->save(42, ['title' => 'Location', 'status' => 'draft'] + $input); }
    /** @return list<int> */
    private function areas(): array { return array_map('intval', $GLOBALS['bml_test_post_terms'][42]['bml_area'] ?? []); }
    /** @return list<int> */
    private function cities(): array { return array_map('intval', $GLOBALS['bml_test_post_terms'][42]['bml_city'] ?? []); }
    private function term(int $id, string $name, int $parent = 0): object { return (object) ['term_id' => $id, 'name' => $name, 'slug' => strtolower(str_replace(' ', '-', $name)), 'parent' => $parent, 'count' => 0]; }
}
