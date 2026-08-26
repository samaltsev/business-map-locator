<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

final class AreaRollbackService implements RollbackInterface
{
    public function __construct(private readonly MigrationSnapshotStore $snapshots)
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
}
