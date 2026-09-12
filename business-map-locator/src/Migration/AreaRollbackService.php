<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

final class AreaRollbackService implements RollbackInterface
{
    public function __construct(private readonly MigrationSnapshotStore $snapshots, private readonly ?AreaMigrationStateStore $state = null, private readonly ?AreaMigrationLock $lock = null, private readonly ?AreaMigrationJournal $journal = null, private readonly ?AreaMigrationRevalidator $revalidator = null)
    {
    }

    public function detectSnapshots(): array
    {
        return $this->snapshots->list();
    }

    public function validateSnapshot(string $path): array
    {
        $snapshot = $this->snapshots->read($path);
        if ($snapshot === null) {
            return ['valid' => false, 'errors' => ['Snapshot is unavailable or unreadable.']];
        }

        return $this->snapshots->validate($snapshot);
    }

    public function reportStatus(): array
    {
        $snapshots = $this->detectSnapshots();
        $valid = 0;
        foreach ($snapshots as $snapshot) {
            if ($this->validateSnapshot($snapshot)['valid']) {
                $valid++;
            }
        }

        return [
            'snapshots' => $snapshots,
            'valid_snapshots' => $valid,
            'invalid_snapshots' => count($snapshots) - $valid,
            'restoration_supported' => false,
        ];
    }

    /** Read-only rollback preflight. A later slice owns source restoration. @return array<string,mixed> */
    public function inspectEligibility(string $runId): array
    {
        $run = $this->state?->get($runId);
        if ($run === null) return ['eligible' => false, 'blocked' => true, 'code' => 'RUN_NOT_FOUND'];
        $snapshot = isset($run['snapshot_path']) && is_string($run['snapshot_path']) ? $this->snapshots->read($run['snapshot_path']) : null;
        if ($snapshot === null) return ['eligible' => false, 'blocked' => true, 'code' => 'SNAPSHOT_UNAVAILABLE', 'run_state' => $run['state']];
        if (($snapshot['schema_version'] ?? null) !== 2) return ['eligible' => false, 'blocked' => true, 'code' => 'SNAPSHOT_VERSION_UNSUPPORTED', 'run_state' => $run['state'], 'snapshot_version' => $snapshot['schema_version'] ?? null];
        if (!in_array($run['state'] ?? null, AreaMigrationStateStore::rollbackEligibleStates(), true)) return ['eligible' => false, 'blocked' => true, 'code' => 'RUN_NOT_ROLLBACK_ELIGIBLE', 'run_state' => $run['state'], 'snapshot_version' => 2];
        $owner = $this->lock?->owner();
        if ($this->lock === null || !$this->lock->isLocked() || $owner === null || (string) $owner['run_id'] !== $runId) return ['eligible' => false, 'blocked' => true, 'code' => 'LOCK_NOT_OWNED', 'run_state' => $run['state'], 'snapshot_version' => 2];
        $operations = $this->journal?->listRunOperations($runId) ?? [];
        if ($operations === []) return ['eligible' => false, 'blocked' => true, 'code' => 'EXECUTION_EVIDENCE_MISSING', 'run_state' => $run['state'], 'snapshot_version' => 2];
        $drift = $this->revalidator?->inspect($snapshot) ?? ['valid' => false, 'drift' => [], 'summary' => ['count' => 0]];
        if (!$drift['valid']) return ['eligible' => false, 'blocked' => true, 'code' => 'DRIFT_DETECTED', 'run_state' => $run['state'], 'snapshot_version' => 2, 'drift' => $drift];
        return ['eligible' => true, 'blocked' => false, 'code' => 'ELIGIBLE', 'run_state' => $run['state'], 'snapshot_version' => 2, 'lock' => ['owned_by_run' => true], 'journal' => ['available' => true, 'operations' => count($operations)], 'drift' => $drift];
    }

    /** State-only entry gate; no source restoration occurs in this slice. @return array<string,mixed> */
    public function beginRollback(string $runId): array
    {
        $result = $this->inspectEligibility($runId);
        if (!$result['eligible']) return $result;
        $run = $this->state->transition($runId, AreaMigrationStateStore::ROLLBACK_RUNNING);
        $result['code'] = 'ROLLBACK_PHASE_ENTERED'; $result['run_state'] = $run['state'];
        return $result;
    }
}
