<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Exception;

use BusinessMapLocator\Import\Job\ImportJobStatus;
use RuntimeException;
use Throwable;

final class ImportJobException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $publicMessage,
        private readonly int $httpStatus = 400,
        ?Throwable $previous = null
    ) {
        parent::__construct($publicMessage, 0, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public static function conflict(): self
    {
        return new self('import_job_conflict', 'Another request is processing this import.', 409);
    }

    public static function invalidTransition(ImportJobStatus $from, ImportJobStatus $to): self
    {
        return new self(
            'import_job_invalid_transition',
            sprintf('The import cannot move from %s to %s.', $from->value, $to->value),
            409
        );
    }

    public static function expired(): self
    {
        return new self('import_job_expired', 'Import session expired.', 410);
    }

    public static function forbidden(): self
    {
        return new self('import_job_forbidden', 'You do not own this import session.', 403);
    }

    public static function missingFile(): self
    {
        return new self('import_file_missing', 'The import file is no longer available.', 410);
    }

    public static function unrecoverable(): self
    {
        return new self(
            'import_job_unrecoverable',
            'This failed import cannot be retried safely. Cancel it and start a new import.',
            409
        );
    }
}
