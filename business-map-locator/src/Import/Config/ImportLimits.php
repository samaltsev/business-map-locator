<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Config;

final class ImportLimits
{
    private const DEFAULT_BATCH_SIZE = 100;
    private const DEFAULT_JOB_TTL = 7200;
    private const DEFAULT_HISTORY_TTL = 604800;
    private const DEFAULT_MAX_FILE_SIZE = 10485760;
    private const DEFAULT_MAX_ROWS = 50000;
    private const DEFAULT_MAX_RECORD_BYTES = 1048576;

    public static function batchSize(): int
    {
        return self::filteredInt('bml_import_batch_size', self::DEFAULT_BATCH_SIZE, 1, 1000);
    }

    public static function jobTtl(): int
    {
        return self::filteredInt('bml_import_job_ttl', self::DEFAULT_JOB_TTL, 300, 86400);
    }

    public static function historyTtl(): int
    {
        return self::filteredInt('bml_import_history_ttl', self::DEFAULT_HISTORY_TTL, 3600, 2592000);
    }

    public static function maxFileSize(): int
    {
        return self::filteredInt('bml_import_max_file_size', self::DEFAULT_MAX_FILE_SIZE, 1024, 104857600);
    }

    public static function maxRows(): int
    {
        return self::filteredInt('bml_import_max_rows', self::DEFAULT_MAX_ROWS, 1, 250000);
    }

    public static function maxRecordBytes(): int
    {
        return self::filteredInt('bml_import_max_record_bytes', self::DEFAULT_MAX_RECORD_BYTES, 1024, 5242880);
    }

    private static function filteredInt(string $hook, int $default, int $minimum, int $maximum): int
    {
        $value = function_exists('apply_filters') ? (int) apply_filters($hook, $default) : $default;
        return max($minimum, min($maximum, $value));
    }
}
