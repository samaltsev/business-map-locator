<?php
declare(strict_types=1);

use BusinessMapLocator\Migration\{AreaMigrationExecutor, AreaMigrationJournal, AreaMigrationLock, AreaMigrationRevalidator, AreaMigrationStateStore, MigrationSnapshotStore};
use PHPUnit\Framework\TestCase;

final class AreaMigrationLocationAreaExecutionTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = dirname(__DIR__) . '/.location-area-fixture-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0777, true);
        $GLOBALS['bml_test_options'] = $GLOBALS['bml_test_term_meta'] = $GLOBALS['bml_test_posts'] = $GLOBALS['bml_test_post_terms'] = [];
        $GLOBALS['bml_test_wp_set_object_terms_calls'] = 0;
        $GLOBALS['bml_test_wp_set_object_terms_override'] = null;
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) { $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname()); }
        rmdir($this->dir);
    }

    public function testAddsAreaWithAppendAndPreservesCityAndCategory(): void
    {
        [$executor,, $journal, $run] = $this->fixture(['category_ids' => [9]]);
        $result = $this->execute($executor, $run['run_id']);
        $operation = $journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2]);

        $this->assertSame('RELATIONSHIP_PHASE_COMPLETE', $result['code']);
        $this->assertSame([2], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids']));
        $this->assertSame([1], wp_get_post_terms(7, 'bml_city', ['fields' => 'ids']));
        $this->assertSame([9], wp_get_post_terms(7, 'bml_category', ['fields' => 'ids']));
        $this->assertSame(AreaMigrationJournal::COMPLETED, $operation['state']);
        $this->assertSame([], $operation['precondition']['area_ids_before']);
        $this->assertTrue($operation['result']['relationship_added_by_run']);
    }

    public function testExistingExpectedAreaReconcilesWithoutWriterCall(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $this->enterRelationships($executor, $run['run_id']);
        $GLOBALS['bml_test_post_terms'][7]['bml_area'] = [2];
        $this->assertSame('RELATIONSHIP_PHASE_COMPLETE', $executor->executeRelationshipPhase($run['run_id'])['code']);
        $this->assertSame(0, $GLOBALS['bml_test_wp_set_object_terms_calls']);
        $this->assertSame(AreaMigrationJournal::COMPLETED, $journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2])['state']);
        $this->assertFalse($journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2])['result']['relationship_added_by_run']);
    }

    public function testStartedOperationWithPresentAreaRecoversWithoutWriterCall(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $this->enterRelationships($executor, $run['run_id']);
        $GLOBALS['bml_test_post_terms'][7]['bml_area'] = [2];
        $operation = $journal->planOperation($run['run_id'], 2, 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2]);
        $journal->beginOperation($run['run_id'], $operation['operation_key']);

        $this->assertSame('RELATIONSHIP_PHASE_COMPLETE', $executor->executeRelationshipPhase($run['run_id'])['code']);
        $this->assertSame(0, $GLOBALS['bml_test_wp_set_object_terms_calls']);
        $this->assertSame(AreaMigrationJournal::COMPLETED, $journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2])['state']);
    }

    public function testStartedOperationWithAbsentAreaRetriesWriter(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $this->enterRelationships($executor, $run['run_id']);
        $operation = $journal->planOperation($run['run_id'], 2, 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2]);
        $journal->beginOperation($run['run_id'], $operation['operation_key']);

        $this->assertSame('RELATIONSHIP_PHASE_COMPLETE', $executor->executeRelationshipPhase($run['run_id'])['code']);
        $this->assertSame(1, $GLOBALS['bml_test_wp_set_object_terms_calls']);
        $this->assertSame([2], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids']));
    }

    public function testCrashAfterSourceWriteRecoversFromStartedWithoutDuplicateAppend(): void
    {
        [$executor, $state, $journal, $run] = $this->fixture([], static function (string $point): void {
            if ($point === 'AFTER_LOCATION_AREA_ADDED') { throw new LogicException('simulated crash'); }
        });
        $this->assertSame('TERM_PHASE_COMPLETE', $executor->executeTermPhase($run['run_id'])['code']);
        try { $executor->executeRelationshipPhase($run['run_id']); $this->fail('Expected crash checkpoint.'); }
        catch (LogicException) { $this->assertSame([2], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids'])); }

        $resume = new AreaMigrationExecutor(new MigrationSnapshotStore(dirname((string) $state->get($run['run_id'])['snapshot_path'])), $state, new AreaMigrationLock(), $journal, new AreaMigrationRevalidator());
        $this->assertSame('RELATIONSHIP_PHASE_COMPLETE', $resume->executeRelationshipPhase($run['run_id'])['code']);
        $this->assertSame(1, $GLOBALS['bml_test_wp_set_object_terms_calls']);
        $this->assertSame(AreaMigrationJournal::COMPLETED, $journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2])['state']);
    }

    public function testCompletedRerunCreatesNoRelationshipOrJournalWork(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $this->execute($executor, $run['run_id']);
        $calls = $GLOBALS['bml_test_wp_set_object_terms_calls'];
        $operations = count($journal->listRunOperations($run['run_id']));

        $this->assertSame('RELATIONSHIP_PHASE_ALREADY_COMPLETE', $executor->executeRelationshipPhase($run['run_id'])['code']);
        $this->assertSame($calls, $GLOBALS['bml_test_wp_set_object_terms_calls']);
        $this->assertSame($operations, count($journal->listRunOperations($run['run_id'])));
    }

    public function testCompletedRerunBlocksWhenCanonicalRelationshipDrifts(): void
    {
        [$executor,, , $run] = $this->fixture();
        $this->execute($executor, $run['run_id']);
        $GLOBALS['bml_test_post_terms'][7]['bml_city'] = [];

        $this->assertSame('COMPLETED_RELATIONSHIP_DRIFT', $executor->executeRelationshipPhase($run['run_id'])['code']);
        $this->assertSame(1, $GLOBALS['bml_test_wp_set_object_terms_calls']);
    }

    public function testPartialRelationshipBatchResumesSameRunWithoutReplayingCompletedLocation(): void
    {
        [$executor, $state, $journal, $run] = $this->multiFixture();
        $this->assertSame('TERM_PHASE_COMPLETE', $executor->executeTermPhase($run['run_id'])['code']);
        $GLOBALS['bml_test_post_terms'][8]['bml_city'] = [3, 99];

        $partial = $executor->executeRelationshipPhase($run['run_id']);
        $this->assertSame('PARTIAL', $partial['code']);
        $this->assertSame([2], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids']));
        $this->assertSame([1], wp_get_post_terms(7, 'bml_city', ['fields' => 'ids']));
        $this->assertSame([], wp_get_post_terms(8, 'bml_area', ['fields' => 'ids']));
        $this->assertSame(AreaMigrationJournal::COMPLETED, $journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2])['state']);

        $unresolved = $executor->resumePartialRelationshipPhase($run['run_id']);
        $this->assertSame('PARTIAL', $unresolved['code']);
        $this->assertSame(1, $GLOBALS['bml_test_wp_set_object_terms_calls']);
        $this->assertCount(1, $journal->listRunOperations($run['run_id']));

        $GLOBALS['bml_test_post_terms'][8]['bml_city'] = [3];
        $resolved = $executor->resumePartialRelationshipPhase($run['run_id']);
        $this->assertSame('RELATIONSHIP_PHASE_COMPLETE', $resolved['code']);
        $this->assertSame(AreaMigrationStateStore::COMPLETED, $state->get($run['run_id'])['state']);
        $this->assertSame([4], wp_get_post_terms(8, 'bml_area', ['fields' => 'ids']));
        $this->assertSame(2, $GLOBALS['bml_test_wp_set_object_terms_calls']);
        $this->assertSame(AreaMigrationJournal::COMPLETED, $journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 8, 'city_term_id' => 3, 'area_term_id' => 4])['state']);
    }

    public function testRelationshipResumeRefusesInvalidLockAndCompletedLocationDrift(): void
    {
        [$executor, $state,, $run] = $this->multiFixture();
        $executor->executeTermPhase($run['run_id']); $GLOBALS['bml_test_post_terms'][8]['bml_city'] = [3, 99]; $executor->executeRelationshipPhase($run['run_id']);
        $lock = new AreaMigrationLock(); $this->assertTrue($lock->release($run['run_id']));
        $refused = $executor->resumePartialRelationshipPhase($run['run_id']);
        $this->assertSame('PARTIAL_RELATIONSHIP_RESUME_NOT_ALLOWED', $refused['code']);
        $this->assertSame(AreaMigrationStateStore::PARTIAL, $state->get($run['run_id'])['state']);
        $this->assertSame(1, $GLOBALS['bml_test_wp_set_object_terms_calls']);

        $this->assertTrue($lock->acquire($run['run_id'])); $GLOBALS['bml_test_post_terms'][7]['bml_city'] = [];
        $drift = $executor->resumePartialRelationshipPhase($run['run_id']);
        $this->assertSame('PARTIAL_RELATIONSHIP_RESUME_NOT_ALLOWED', $drift['code']);
        $this->assertSame(AreaMigrationStateStore::PARTIAL, $state->get($run['run_id'])['state']);
    }

    public function testIndexLifecycleIsHookDrivenAndCannotRollbackSourceMutationSynchronously(): void
    {
        $root = dirname(__DIR__);
        $indexer = (string) file_get_contents($root . '/includes/Database/class-bml-location-indexer.php');
        $executor = (string) file_get_contents($root . '/src/Migration/AreaMigrationExecutor.php');

        $this->assertStringContainsString("add_action('set_object_terms', [\$this, 'sync_terms'], 20, 6)", $indexer);
        $this->assertStringContainsString('$this->mark_dirty($object_id)', $indexer);
        $this->assertStringContainsString('$this->index->upsert((int) $post_id)', $indexer);
        $this->assertStringContainsString('wp_set_object_terms($locationId, [$areaId], \'bml_area\', true)', $executor);
        $this->assertStringNotContainsString('BML_Location_Index', $executor);
        $this->assertStringNotContainsString('bml_location_terms', $executor);
    }

    /** @dataProvider conflictFixtures */
    public function testRelationshipConflictsBlockBeforeMutation(string $kind, array $options): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $this->enterRelationships($executor, $run['run_id']);
        if (isset($options['city_ids'])) { $GLOBALS['bml_test_post_terms'][7]['bml_city'] = $options['city_ids']; }
        if (isset($options['area_ids'])) { $GLOBALS['bml_test_post_terms'][7]['bml_area'] = $options['area_ids']; }
        if (isset($options['city_area_meta'])) { $GLOBALS['bml_test_term_meta'][1]['_bml_area_term_id'] = $options['city_area_meta']; }
        if (isset($options['area_slug'])) { $GLOBALS['bml_test_terms']['bml_area'][2]->slug = $options['area_slug']; }
        $result = $executor->executeRelationshipPhase($run['run_id']);

        $this->assertSame('BLOCKED', $result['code'], $kind);
        $this->assertSame(0, $GLOBALS['bml_test_wp_set_object_terms_calls'], $kind);
        $this->assertNull($journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2]), $kind);
    }

    public static function conflictFixtures(): array
    {
        return [
            'missing city' => ['missing city', ['city_ids' => []]],
            'second city' => ['second city', ['city_ids' => [1, 3]]],
            'unrelated area' => ['unrelated area', ['area_ids' => [4]]],
            'expected and unrelated area' => ['expected and unrelated area', ['area_ids' => [2, 4]]],
            'provenance drift' => ['provenance drift', ['city_area_meta' => 4]],
            'area identity drift' => ['area identity drift', ['area_slug' => 'changed']],
        ];
    }

    public function testDraftLocationIsMigratedWithoutPublicationChange(): void
    {
        [$executor,, , $run] = $this->fixture(['post_status' => 'draft']);
        $this->execute($executor, $run['run_id']);

        $this->assertSame([2], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids']));
        $this->assertSame([1], wp_get_post_terms(7, 'bml_city', ['fields' => 'ids']));
        $this->assertSame('draft', get_post(7)->post_status);
    }

    public function testWriterFailureCannotCompleteOperation(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $GLOBALS['bml_test_wp_set_object_terms_override'] = static fn (): WP_Error => new WP_Error('write_failed', 'write failed');
        $result = $this->execute($executor, $run['run_id']);
        $operation = $journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2]);

        $this->assertSame('BLOCKED', $result['code']);
        $this->assertSame([], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids']));
        $this->assertSame(AreaMigrationJournal::FAILED, $operation['state']);
    }

    public function testVerificationFailureCannotCompleteOperation(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $GLOBALS['bml_test_wp_set_object_terms_override'] = static fn (): array => [];
        $result = $this->execute($executor, $run['run_id']);
        $operation = $journal->getOperation($run['run_id'], 'ADD_LOCATION_AREA', ['location_id' => 7, 'city_term_id' => 1, 'area_term_id' => 2]);

        $this->assertSame('BLOCKED', $result['code']);
        $this->assertSame(AreaMigrationJournal::FAILED, $operation['state']);
        $this->assertNotSame(AreaMigrationJournal::COMPLETED, $operation['state']);
    }

    /** @return array{0:AreaMigrationExecutor,1:AreaMigrationStateStore,2:AreaMigrationJournal,3:array<string,mixed>} */
    private function fixture(array $options = [], ?callable $injector = null): array
    {
        $city = (object) ['term_id' => 1, 'name' => 'Minsk', 'slug' => 'minsk', 'parent' => 0];
        $area = (object) ['term_id' => 2, 'name' => 'Minsk Area', 'slug' => $options['area_slug'] ?? 'minsk-area', 'parent' => 0];
        $GLOBALS['bml_test_terms'] = ['bml_city' => [1 => $city], 'bml_area' => [2 => $area]];
        $GLOBALS['bml_test_term_meta'] = [1 => ['_bml_area_term_id' => $options['city_area_meta'] ?? 2], 2 => ['_bml_migrated_from_city_term_id' => $options['area_city_meta'] ?? 1]];
        $post = new WP_Post(); $post->ID = 7; $post->post_type = 'bml_location'; $post->post_status = $options['post_status'] ?? 'publish';
        $GLOBALS['bml_test_posts'] = [7 => $post];
        $GLOBALS['bml_test_post_terms'] = [7 => ['bml_city' => $options['city_ids'] ?? [1], 'bml_area' => $options['area_ids'] ?? [], 'bml_category' => $options['category_ids'] ?? []]];
        $snapshot = ['schema_version' => 2, 'migration' => 'bml_city_to_area_v1', 'created_at' => 'x', 'created_by_user_id' => 1, 'ownership' => ['site_url' => 'x', 'plugin_version' => 'x', 'wp_version' => 'x', 'php_version' => 'x', 'created_at' => 'x', 'created_by_user_id' => 1], 'taxonomies' => ['bml_city', 'bml_area'], 'terms' => ['bml_city' => [['id' => 1, 'name' => 'Minsk', 'slug' => 'minsk', 'parent' => 0]], 'bml_area' => [['id' => 2, 'name' => 'Minsk Area', 'slug' => 'minsk-area', 'parent' => 0, 'migrated_from_city_term_id' => 1]]], 'locations' => [['location_id' => 7, 'post_status' => $post->post_status, 'city_ids' => [1], 'area_ids' => []]], 'plan' => ['counts' => [], 'city_decisions' => [], 'location_decisions' => [['location_id' => 7, 'post_status' => $post->post_status, 'city_ids' => [1], 'area_ids' => [], 'status' => 'ADD_AREA', 'target_area_id' => 2]], 'collision_list' => [], 'ambiguous_list' => [], 'decision_required_list' => []]];
        $store = new MigrationSnapshotStore($this->dir . '/snap-' . bin2hex(random_bytes(2))); $path = $store->write($snapshot);
        $state = new AreaMigrationStateStore(); $run = $state->create();
        foreach ([AreaMigrationStateStore::INSPECTED, AreaMigrationStateStore::SNAPSHOTTED, AreaMigrationStateStore::SIMULATED, AreaMigrationStateStore::READY] as $next) { $run = $state->transition($run['run_id'], $next, $next === AreaMigrationStateStore::SNAPSHOTTED ? ['snapshot_path' => $path] : []); }
        $lock = new AreaMigrationLock(); $lock->acquire($run['run_id']); $journal = new AreaMigrationJournal($this->dir . '/journal-' . bin2hex(random_bytes(2)));
        return [new AreaMigrationExecutor($store, $state, $lock, $journal, new AreaMigrationRevalidator(), $injector), $state, $journal, $run];
    }

    /** @return array{0:AreaMigrationExecutor,1:AreaMigrationStateStore,2:AreaMigrationJournal,3:array<string,mixed>} */
    private function multiFixture(): array
    {
        $GLOBALS['bml_test_terms'] = ['bml_city' => [1 => (object) ['term_id' => 1, 'name' => 'Minsk', 'slug' => 'minsk', 'parent' => 0], 3 => (object) ['term_id' => 3, 'name' => 'Brest', 'slug' => 'brest', 'parent' => 0]], 'bml_area' => [2 => (object) ['term_id' => 2, 'name' => 'Minsk Area', 'slug' => 'minsk-area', 'parent' => 0], 4 => (object) ['term_id' => 4, 'name' => 'Brest Area', 'slug' => 'brest-area', 'parent' => 0]]];
        $GLOBALS['bml_test_term_meta'] = [1 => ['_bml_area_term_id' => 2], 2 => ['_bml_migrated_from_city_term_id' => 1], 3 => ['_bml_area_term_id' => 4], 4 => ['_bml_migrated_from_city_term_id' => 3]];
        foreach ([7, 8] as $id) { $post = new WP_Post(); $post->ID = $id; $post->post_type = 'bml_location'; $post->post_status = 'publish'; $GLOBALS['bml_test_posts'][$id] = $post; }
        $GLOBALS['bml_test_post_terms'] = [7 => ['bml_city' => [1], 'bml_area' => []], 8 => ['bml_city' => [3], 'bml_area' => []]];
        $snapshot = ['schema_version' => 2, 'migration' => 'bml_city_to_area_v1', 'created_at' => 'x', 'created_by_user_id' => 1, 'ownership' => ['site_url' => 'x', 'plugin_version' => 'x', 'wp_version' => 'x', 'php_version' => 'x', 'created_at' => 'x', 'created_by_user_id' => 1], 'taxonomies' => ['bml_city', 'bml_area'], 'terms' => ['bml_city' => [['id' => 1, 'name' => 'Minsk', 'slug' => 'minsk', 'parent' => 0], ['id' => 3, 'name' => 'Brest', 'slug' => 'brest', 'parent' => 0]], 'bml_area' => [['id' => 2, 'name' => 'Minsk Area', 'slug' => 'minsk-area', 'parent' => 0, 'migrated_from_city_term_id' => 1], ['id' => 4, 'name' => 'Brest Area', 'slug' => 'brest-area', 'parent' => 0, 'migrated_from_city_term_id' => 3]]], 'locations' => [['location_id' => 7, 'post_status' => 'publish', 'city_ids' => [1], 'area_ids' => []], ['location_id' => 8, 'post_status' => 'publish', 'city_ids' => [3], 'area_ids' => []]], 'plan' => ['counts' => [], 'city_decisions' => [], 'location_decisions' => [['location_id' => 7, 'post_status' => 'publish', 'city_ids' => [1], 'area_ids' => [], 'status' => 'ADD_AREA', 'target_area_id' => 2], ['location_id' => 8, 'post_status' => 'publish', 'city_ids' => [3], 'area_ids' => [], 'status' => 'ADD_AREA', 'target_area_id' => 4]], 'collision_list' => [], 'ambiguous_list' => [], 'decision_required_list' => []]];
        $store = new MigrationSnapshotStore($this->dir . '/multi-snap'); $path = $store->write($snapshot); $state = new AreaMigrationStateStore(); $run = $state->create();
        foreach ([AreaMigrationStateStore::INSPECTED, AreaMigrationStateStore::SNAPSHOTTED, AreaMigrationStateStore::SIMULATED, AreaMigrationStateStore::READY] as $next) { $run = $state->transition($run['run_id'], $next, $next === AreaMigrationStateStore::SNAPSHOTTED ? ['snapshot_path' => $path] : []); }
        $lock = new AreaMigrationLock(); $lock->acquire($run['run_id']); $journal = new AreaMigrationJournal($this->dir . '/multi-journal');
        return [new AreaMigrationExecutor($store, $state, $lock, $journal, new AreaMigrationRevalidator()), $state, $journal, $run];
    }

    private function execute(AreaMigrationExecutor $executor, string $runId): array
    {
        $this->assertSame('TERM_PHASE_COMPLETE', $executor->executeTermPhase($runId)['code']);
        return $executor->executeRelationshipPhase($runId);
    }

    private function enterRelationships(AreaMigrationExecutor $executor, string $runId): void
    {
        $this->assertSame('TERM_PHASE_COMPLETE', $executor->executeTermPhase($runId)['code']);
        $this->assertSame('RELATIONSHIP_PHASE_ENTERED', $executor->beginRelationshipPhase($runId)['code']);
    }
}
