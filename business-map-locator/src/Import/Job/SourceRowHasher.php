<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Job;

final class SourceRowHasher
{
    public function hash(array $headers, array $row): string
    {
        $normalized = [];
        foreach ($headers as $index => $header) {
            $value = (string) ($row[$index] ?? '');
            $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
            $normalized[(string) $header] = $value;
        }
        ksort($normalized, SORT_STRING);
        return hash('sha256', (string) wp_json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
