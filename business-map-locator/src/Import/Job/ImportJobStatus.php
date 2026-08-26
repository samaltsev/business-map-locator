<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Job;

enum ImportJobStatus: string
{
    case PREPARED = 'prepared';
    case PROCESSING = 'processing';
    case RUNNING = 'running';
    case PAUSED = 'paused';
    case RETRYING = 'retrying';
    case COMPLETE = 'complete';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETE, self::CANCELLED, self::EXPIRED], true);
    }

    public function requiresFile(): bool
    {
        return in_array($this, [
            self::PREPARED,
            self::PROCESSING,
            self::RUNNING,
            self::PAUSED,
            self::RETRYING,
            self::FAILED,
        ], true);
    }

    public function keepsFileActive(): bool
    {
        return $this->requiresFile();
    }
}
