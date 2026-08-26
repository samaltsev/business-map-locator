<?php
declare(strict_types=1);

namespace BusinessMapLocator\Infrastructure\Cache;

final readonly class LocationCache
{
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|false
     */
    public function get(string $bucket, array $params): array|false
    {
        return \BML_Location_Cache::get($bucket, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $value
     */
    public function set(string $bucket, array $params, array $value): void
    {
        \BML_Location_Cache::set($bucket, $params, $value);
    }
}
