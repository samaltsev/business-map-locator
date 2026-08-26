<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Dto;
final class ImportJobCounters {
    public function __construct(public int $added=0, public int $updated=0, public int $skipped=0, public int $processingErrors=0, public int $inspectionErrors=0, public int $wouldCreate=0, public int $wouldUpdate=0, public int $wouldSkip=0, public int $wouldFail=0) {}
    public static function fromArray(array $d): self { return new self((int)($d['added']??0),(int)($d['updated']??0),(int)($d['skipped']??0),(int)($d['processingErrors']??0),(int)($d['inspectionErrors']??0),(int)($d['wouldCreate']??0),(int)($d['wouldUpdate']??0),(int)($d['wouldSkip']??0),(int)($d['wouldFail']??0)); }
    public function toArray(): array { return get_object_vars($this); }
}
