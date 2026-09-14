<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

use RuntimeException;

final class MigrationSnapshotStore
{
    private const SCHEMA_VERSION = 2;

    public function __construct(private readonly ?string $baseDirectory = null)
    {
    }

    public function write(array $snapshot): string
    {
        $directory = $this->directory();
        if (!is_dir($directory) && !wp_mkdir_p($directory)) {
            throw new RuntimeException('Unable to create the migration snapshot directory.');
        }

        $validation = $this->validate($snapshot);
        if (!$validation['valid']) {
            throw new RuntimeException('Refusing to write an invalid migration snapshot.');
        }

        $path = trailingslashit($directory) . 'snapshot-' . gmdate('Ymd-His') . '-' . $this->uniqueSuffix() . '.json';
        $handle = @fopen($path, 'x');
        if ($handle === false) {
            throw new RuntimeException('A migration snapshot already exists for this second.');
        }

        try {
            $json = wp_json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (fwrite($handle, $json . "\n") === false) {
                throw new RuntimeException('Unable to write the migration snapshot.');
            }
        } finally {
            fclose($handle);
        }

        return $path;
    }

    /** @return list<string> */
    public function list(): array
    {
        $directory = $this->directory();
        if (!is_dir($directory)) {
            return [];
        }

        $paths = array_merge(
            glob(trailingslashit($directory) . 'snapshot-????????-??????-*.json') ?: [],
            glob(trailingslashit($directory) . 'snapshot-????????-??????.json') ?: []
        );
        rsort($paths, SORT_STRING);

        return array_values(array_unique($paths));
    }

    /** @return array<string, mixed>|null */
    public function read(string $path): ?array
    {
        if (!in_array($path, $this->list(), true) || !is_readable($path)) {
            return null;
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    /** @return array{valid: bool, errors: list<string>} */
    public function validate(array $snapshot): array
    {
        $errors = [];
        foreach (['schema_version', 'migration', 'created_at', 'created_by_user_id', 'ownership', 'taxonomies', 'terms'] as $key) {
            if (!array_key_exists($key, $snapshot)) {
                $errors[] = sprintf('Missing required snapshot field: %s.', $key);
            }
        }

        if (!in_array(($snapshot['schema_version'] ?? null), [1, self::SCHEMA_VERSION], true)) {
            $errors[] = 'Unsupported snapshot schema version.';
        }
        if (($snapshot['migration'] ?? null) !== 'bml_city_to_area_v1') {
            $errors[] = 'Unexpected migration identifier.';
        }
        if (!is_array($snapshot['ownership'] ?? null)) {
            $errors[] = 'Snapshot ownership must be an object.';
        } else {
            foreach (['site_url', 'plugin_version', 'wp_version', 'php_version', 'created_at', 'created_by_user_id'] as $key) {
                if (!array_key_exists($key, $snapshot['ownership'])) {
                    $errors[] = sprintf('Missing ownership field: %s.', $key);
                }
            }
        }
        if (!is_array($snapshot['taxonomies'] ?? null) || !is_array($snapshot['terms'] ?? null)) {
            $errors[] = 'Snapshot taxonomies and terms must be arrays.';
        }
        if (($snapshot['schema_version'] ?? null) === self::SCHEMA_VERSION && (!is_array($snapshot['locations'] ?? null) || !is_array($snapshot['plan'] ?? null))) {
            $errors[] = 'Version 2 snapshots require locations and plan evidence.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    private function uniqueSuffix(): string
    {
        return function_exists('wp_generate_uuid4')
            ? str_replace('-', '', wp_generate_uuid4())
            : bin2hex(random_bytes(16));
    }

    private function directory(): string
    {
        if ($this->baseDirectory !== null) {
            return $this->baseDirectory;
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error']) || empty($uploads['basedir'])) {
            throw new RuntimeException('WordPress uploads directory is unavailable.');
        }

        return trailingslashit((string) $uploads['basedir']) . 'business-map-locator/migrations';
    }
}
