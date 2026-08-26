<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Dto;
final class ImportJobError {
    public function __construct(public string $code, public string $message, public ?int $row=null, public ?string $column=null) {}
    public static function fromArray(array $d): self { return new self((string)($d['code']??'import_error'),(string)($d['message']??'Import error.'),isset($d['row'])?(int)$d['row']:null,isset($d['column'])?(string)$d['column']:null); }
    public function toArray(): array { return ['row'=>$this->row,'column'=>$this->column,'code'=>$this->code,'message'=>$this->message]; }
}
