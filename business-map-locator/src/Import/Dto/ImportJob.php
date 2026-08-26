<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Dto;
use ArrayAccess;
use JsonSerializable;
/** @implements ArrayAccess<string,mixed> */
final class ImportJob implements ArrayAccess, JsonSerializable {
    private array $data;
    public function __construct(array $data) { $this->data=$data; }
    public static function fromArray(array $data): self { return new self($data); }
    public function toArray(): array { return $this->data; }
    public function counters(): ImportJobCounters { return ImportJobCounters::fromArray($this->data); }
    public function errors(): array { return array_map(static fn(array $e): ImportJobError => ImportJobError::fromArray($e), array_values(array_filter((array)($this->data['errorDetails']??[]),'is_array'))); }
    public function offsetExists(mixed $offset): bool { return array_key_exists((string)$offset,$this->data); }
    public function &offsetGet(mixed $offset): mixed { $key=(string)$offset; if(!array_key_exists($key,$this->data)){$this->data[$key]=null;} return $this->data[$key]; }
    public function offsetSet(mixed $offset,mixed $value): void { if($offset===null){throw new \LogicException('ImportJob requires named fields.');}$this->data[(string)$offset]=$value; }
    public function offsetUnset(mixed $offset): void { unset($this->data[(string)$offset]); }
    public function jsonSerialize(): array { return $this->data; }
}
