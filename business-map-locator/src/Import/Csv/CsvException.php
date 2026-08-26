<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Csv;
use RuntimeException;
final class CsvException extends RuntimeException {
    public function __construct(private readonly string $errorCode, string $message) { parent::__construct($message); }
    public function errorCode(): string { return $this->errorCode; }
}
