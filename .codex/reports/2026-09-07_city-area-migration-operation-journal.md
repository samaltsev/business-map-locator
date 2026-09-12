# City to Area migration operation journal foundation

Slice 2B was blocked because the planning snapshot/state could not prove partial mutation progress. This slice adds `AreaMigrationJournal`, a separate filesystem-backed per-operation evidence store. It stores no source taxonomy data and performs no WordPress taxonomy, relationship, provenance, or index mutation.

Each record has journal version 1, a SHA-256 deterministic operation key derived from run ID, operation type, and canonical identity; v2 snapshot compatibility is mandatory. Each operation is an individual JSON file below a hash-isolated run directory. Creation is exclusive; state changes take an exclusive file lock and reject invalid transitions. This avoids a large option payload and permits bounded record rewrites.

The record includes planned identity/preconditions, state, attempts, concrete result values, structured failure, and timestamps. `STARTED` means the mutation result is unknown and must be revalidated against WordPress state by a future executor; it never means retry is automatically safe. `CREATE_AREA` can record resulting Area ID/created-by-run evidence. `ADD_LOCATION_AREA` records location and target Area identity, while snapshot pre-state proves the relationship was absent before the run.

No executor, rollback writer, public trigger, or active-stand deployment was added. Backup: `.codex/backups/2026-09-07_17-29-19_city-area-migration-operation-journal`. Active source data was not invoked or mutated.

Initial tooling blocker: OSPanel's configured PHP temp directory was absent; after creating it, the generated Composer PHPUnit proxy ran normally. The remaining journal-test errors were fixture-only: `sys_get_temp_dir()` pointed at the OSPanel temp root, where the fixture could not create its nested test directory. The fixture now creates a unique exact-path directory under the writable plugin root and removes only that directory.

Verification passed reproducibly: targeted `AreaMigrationJournalTest` twice (4 tests, 10 assertions each) and the complete suite twice (113 tests, 438 assertions each). PHP lint passed for all Slice 2B-0 PHP files; PHPStan reported no errors for `AreaMigrationJournal.php`. The Composer bin proxy remains generated tooling and was not edited.
