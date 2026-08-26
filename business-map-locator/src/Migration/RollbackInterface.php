<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

interface RollbackInterface
{
    /** @return list<string> */
    public function detectSnapshots(): array;

    /** @return array{valid: bool, errors: list<string>} */
    public function validateSnapshot(string $path): array;

    /** @return array{snapshots: list<string>, valid_snapshots: int, invalid_snapshots: int, restoration_supported: bool} */
    public function reportStatus(): array;
}
