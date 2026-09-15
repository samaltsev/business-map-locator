<?php
declare(strict_types=1);

use BusinessMapLocator\Migration\{AreaMigrationExecutor, AreaMigrationJournal, AreaMigrationLock, AreaMigrationRevalidator, AreaMigrationStateStore, MigrationSnapshotStore};
use PHPUnit\Framework\TestCase;

final class AreaMigrationOwnershipEvidenceTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = dirname(__DIR__) . '/.ownership-fixture-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0777, true);
        $GLOBALS['bml_test_options'] = $GLOBALS['bml_test_term_meta'] = $GLOBALS['bml_test_posts'] = $GLOBALS['bml_test_post_terms'] = [];
        $GLOBALS['bml_test_terms'] = ['bml_city' => [1 => (object) ['term_id' => 1, 'name' => 'Minsk', 'slug' => 'minsk', 'parent' => 0, 'count' => 0]], 'bml_area' => []];
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) { $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname()); }
        rmdir($this->dir);
    }

    public function testObservedEffectsAreOwnedAndOperationIdentityIsStable(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $identity = ['city_term_id' => 1, 'planned_area' => ['name' => 'Minsk', 'slug' => 'minsk', 'parent' => 0]];
        $planned = $journal->planOperation($run['run_id'], 2, 'CREATE_AREA', $identity);
        $this->assertSame('TERM_PHASE_COMPLETE', $executor->executeTermPhase($run['run_id'])['code']);
        $create = $journal->getOperation($run['run_id'], 'CREATE_AREA', $identity);
        $city = $journal->getOperation($run['run_id'], 'WRITE_CITY_PROVENANCE', ['city_term_id' => 1, 'area_term_id' => 1]);
        $area = $journal->getOperation($run['run_id'], 'WRITE_AREA_PROVENANCE', ['area_term_id' => 1, 'city_term_id' => 1]);
        $this->assertSame($planned['operation_key'], $create['operation_key']);
        $this->assertSame(AreaMigrationJournal::COMPLETED, $create['state']);
        $this->assertTrue($create['result']['created_by_run']);
        $this->assertSame('observed_create', $create['result']['ownership_resolution']);
        $this->assertTrue($city['result']['written_by_run']);
        $this->assertFalse($city['result']['reconciled_preexisting']);
        $this->assertTrue($area['result']['written_by_run']);
        $this->assertFalse($area['result']['reconciled_preexisting']);
        $this->assertCount(3, $journal->listRunOperations($run['run_id']));
    }

    public function testStartedCreateRecoveryIsUnknownAndDoesNotDuplicate(): void
    {
        [$executor, $state, $journal, $run] = $this->fixture(static function (string $point): void { if ($point === 'AFTER_AREA_CREATED') { throw new LogicException('crash'); } });
        try { $executor->executeTermPhase($run['run_id']); $this->fail('Expected crash.'); } catch (LogicException) { }
        $this->assertCount(1, $GLOBALS['bml_test_terms']['bml_area']);
        $this->assertSame('TERM_PHASE_COMPLETE', $this->resume($state, $journal)->executeTermPhase($run['run_id'])['code']);
        $create = $journal->getOperation($run['run_id'], 'CREATE_AREA', $this->createIdentity());
        $this->assertCount(1, $GLOBALS['bml_test_terms']['bml_area']);
        $this->assertNull($create['result']['created_by_run']);
        $this->assertSame('recovered_unknown_outcome', $create['result']['ownership_resolution']);
        $this->assertSame(1, $create['result']['area_term_id']);
    }

    public function testExactUnownedAreaIsBlockedAndNotAdopted(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        wp_insert_term('Minsk', 'bml_area', ['slug' => 'minsk', 'parent' => 0]);
        $result = $executor->executeTermPhase($run['run_id']);
        $this->assertSame('BLOCKED', $result['code']);
        $this->assertCount(1, $GLOBALS['bml_test_terms']['bml_area']);
        $this->assertSame('', get_term_meta(1, '_bml_area_term_id', true));
        $this->assertNull($journal->getOperation($run['run_id'], 'WRITE_CITY_PROVENANCE', ['city_term_id' => 1, 'area_term_id' => 1]));
        $create = $journal->getOperation($run['run_id'], 'CREATE_AREA', $this->createIdentity());
        $this->assertSame(AreaMigrationJournal::PLANNED, $create['state']);
        $this->assertArrayNotHasKey('created_by_run', $create['result']);
    }

    /** @dataProvider provenanceFixtures */
    public function testProvenanceOwnershipStates(string $checkpoint, string $operationType, string $metaKey, mixed $expectedOwnership, bool $preexisting): void
    {
        [$executor, $state, $journal, $run] = $this->fixture($checkpoint === '' ? null : static function (string $point) use ($checkpoint): void { if ($point === $checkpoint) { throw new LogicException('crash'); } });
        if ($preexisting) {
            try { $this->fixtureCrashAfterCreate($executor, $run); } catch (LogicException) { }
            update_term_meta(1, '_bml_area_term_id', 1); update_term_meta(1, '_bml_migrated_from_city_term_id', 1);
            $this->assertSame('TERM_PHASE_COMPLETE', $this->resume($state, $journal)->executeTermPhase($run['run_id'])['code']);
        } elseif ($checkpoint !== '') {
            try { $executor->executeTermPhase($run['run_id']); $this->fail('Expected crash.'); } catch (LogicException) { }
            $this->assertSame(1, (int) get_term_meta(1, $metaKey, true));
            $this->assertSame('TERM_PHASE_COMPLETE', $this->resume($state, $journal)->executeTermPhase($run['run_id'])['code']);
        } else { $this->assertSame('TERM_PHASE_COMPLETE', $executor->executeTermPhase($run['run_id'])['code']); }
        $identity = $operationType === 'WRITE_CITY_PROVENANCE' ? ['city_term_id' => 1, 'area_term_id' => 1] : ['area_term_id' => 1, 'city_term_id' => 1];
        $operation = $journal->getOperation($run['run_id'], $operationType, $identity);
        $this->assertSame($expectedOwnership, $operation['result']['written_by_run']);
        $this->assertSame($preexisting, $operation['result']['reconciled_preexisting']);
        $this->assertSame(AreaMigrationJournal::COMPLETED, $operation['state']);
    }

    public static function provenanceFixtures(): array
    {
        return [
            'city observed' => ['', 'WRITE_CITY_PROVENANCE', '_bml_area_term_id', true, false],
            'city unknown' => ['AFTER_CITY_PROVENANCE_WRITTEN', 'WRITE_CITY_PROVENANCE', '_bml_area_term_id', null, false],
            'area observed' => ['', 'WRITE_AREA_PROVENANCE', '_bml_migrated_from_city_term_id', true, false],
            'area unknown' => ['AFTER_AREA_PROVENANCE_WRITTEN', 'WRITE_AREA_PROVENANCE', '_bml_migrated_from_city_term_id', null, false],
        ];
    }

    public function testPreexistingReciprocalProvenanceIsNotPromotedToOwnership(): void
    {
        [$executor, $state, $journal, $run] = $this->fixture(static function (string $point): void { if ($point === 'AFTER_AREA_CREATED') { throw new LogicException('crash'); } });
        try { $executor->executeTermPhase($run['run_id']); $this->fail('Expected crash.'); } catch (LogicException) { }
        update_term_meta(1, '_bml_area_term_id', 1);
        update_term_meta(1, '_bml_migrated_from_city_term_id', 1);
        $this->assertSame('TERM_PHASE_COMPLETE', $this->resume($state, $journal)->executeTermPhase($run['run_id'])['code']);
        $city = $journal->getOperation($run['run_id'], 'WRITE_CITY_PROVENANCE', ['city_term_id' => 1, 'area_term_id' => 1]);
        $area = $journal->getOperation($run['run_id'], 'WRITE_AREA_PROVENANCE', ['area_term_id' => 1, 'city_term_id' => 1]);
        $this->assertFalse($city['result']['written_by_run']);
        $this->assertTrue($city['result']['reconciled_preexisting']);
        $this->assertFalse($area['result']['written_by_run']);
        $this->assertTrue($area['result']['reconciled_preexisting']);
    }

    public function testLegacyMissingEvidenceIsNotOwnershipAndCompletedEvidenceIsMonotonic(): void
    {
        [$executor,, $journal, $run] = $this->fixture();
        $legacy = $journal->planOperation($run['run_id'], 2, 'ADD_LOCATION_AREA', ['location_id' => 9, 'city_term_id' => 1, 'area_term_id' => 1]);
        $this->assertArrayNotHasKey('relationship_added_by_run', $legacy['result']);
        $this->assertSame('TERM_PHASE_COMPLETE', $executor->executeTermPhase($run['run_id'])['code']);
        $create = $journal->getOperation($run['run_id'], 'CREATE_AREA', $this->createIdentity());
        $this->assertTrue($create['result']['created_by_run']);
        $this->assertSame('TERM_PHASE_COMPLETE', $executor->executeTermPhase($run['run_id'])['code']);
        $again = $journal->getOperation($run['run_id'], 'CREATE_AREA', $this->createIdentity());
        $this->assertTrue($again['result']['created_by_run']);
        $this->assertSame($create['operation_key'], $again['operation_key']);
    }

    private function fixtureCrashAfterCreate(AreaMigrationExecutor $executor, array $run): void { $executor->executeTermPhase($run['run_id']); }
    private function createIdentity(): array { return ['city_term_id' => 1, 'planned_area' => ['name' => 'Minsk', 'slug' => 'minsk', 'parent' => 0]]; }
    private function resume(AreaMigrationStateStore $state, AreaMigrationJournal $journal): AreaMigrationExecutor { $run = array_values($GLOBALS['bml_test_options']['bml_area_migration_planning_runs'])[0]; return new AreaMigrationExecutor(new MigrationSnapshotStore(dirname((string) $run['snapshot_path'])), $state, new AreaMigrationLock(), $journal, new AreaMigrationRevalidator()); }
    private function fixture(?callable $injector = null): array
    {
        $store = new MigrationSnapshotStore($this->dir . '/snap-' . bin2hex(random_bytes(2))); $state = new AreaMigrationStateStore(); $lock = new AreaMigrationLock(); $journal = new AreaMigrationJournal($this->dir . '/journal-' . bin2hex(random_bytes(2)));
        $snapshot = ['schema_version' => 2, 'migration' => 'bml_city_to_area_v1', 'created_at' => 'x', 'created_by_user_id' => 1, 'ownership' => ['site_url'=>'x','plugin_version'=>'x','wp_version'=>'x','php_version'=>'x','created_at'=>'x','created_by_user_id'=>1], 'taxonomies' => ['bml_city','bml_area'], 'terms' => ['bml_city' => [['id'=>1,'name'=>'Minsk','slug'=>'minsk','parent'=>0]], 'bml_area'=>[]], 'locations'=>[], 'plan'=>['counts'=>[],'city_decisions'=>[['status'=>'CREATE','city_id'=>1,'planned_area'=>['name'=>'Minsk','slug'=>'minsk','parent'=>0]]],'collision_list'=>[],'ambiguous_list'=>[],'decision_required_list'=>[]]];
        $path = $store->write($snapshot); $run = $state->create(); foreach ([AreaMigrationStateStore::INSPECTED, AreaMigrationStateStore::SNAPSHOTTED, AreaMigrationStateStore::SIMULATED, AreaMigrationStateStore::READY] as $next) { $run = $state->transition($run['run_id'], $next, $next === AreaMigrationStateStore::SNAPSHOTTED ? ['snapshot_path' => $path] : []); } $lock->acquire($run['run_id']);
        return [new AreaMigrationExecutor($store, $state, $lock, $journal, new AreaMigrationRevalidator(), $injector), $state, $journal, $run];
    }
}
