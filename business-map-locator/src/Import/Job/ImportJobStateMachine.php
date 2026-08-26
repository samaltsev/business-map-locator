<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Job;

use BusinessMapLocator\Import\Exception\ImportJobException;

final class ImportJobStateMachine
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'prepared' => ['processing', 'cancelled'],
        'processing' => ['running', 'paused', 'complete', 'cancelled', 'failed'],
        'running' => ['processing', 'paused', 'complete', 'cancelled', 'failed'],
        'paused' => ['running', 'cancelled'],
        'retrying' => ['processing', 'cancelled'],
        'failed' => ['retrying', 'cancelled'],
        'complete' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    public function canTransition(ImportJobStatus $from, ImportJobStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true);
    }

    public function assertCanTransition(ImportJobStatus $from, ImportJobStatus $to): void
    {
        if ($this->canTransition($from, $to)) {
            return;
        }

        throw ImportJobException::invalidTransition($from, $to);
    }

    /** @return list<ImportJobStatus> */
    public function allowedTransitions(ImportJobStatus $from): array
    {
        return array_map(
            static fn (string $status): ImportJobStatus => ImportJobStatus::from($status),
            self::TRANSITIONS[$from->value] ?? []
        );
    }
}
