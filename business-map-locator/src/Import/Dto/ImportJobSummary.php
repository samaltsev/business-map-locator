<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Dto;
final class ImportJobSummary {
    public function __construct(private array $data) {}
    public function toArray(): array { return $this->data; }
}
