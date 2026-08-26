<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Job;

use RuntimeException;

final class ImportJobRowRepository
{
    private string $table;

    public function __construct(?string $table = null)
    {
        global $wpdb;
        $this->table = $table ?? $wpdb->prefix . 'bml_import_job_rows';
    }

    public function findCommitted(int $jobId, int $rowNumber, string $rowHash): ?array
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE job_id = %d AND source_row_number = %d AND row_hash = %s AND status = 'committed' LIMIT 1",
            $jobId,
            $rowNumber,
            $rowHash
        );
        $row = $wpdb->get_row($sql, ARRAY_A);
        return is_array($row) ? $this->normalize($row) : null;
    }

    public function find(int $jobId, int $rowNumber): ?array
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$this->table} WHERE job_id = %d AND source_row_number = %d LIMIT 1", $jobId, $rowNumber);
        $row = $wpdb->get_row($sql, ARRAY_A);
        return is_array($row) ? $this->normalize($row) : null;
    }

    public function begin(int $jobId, int $rowNumber, string $rowHash): array
    {
        global $wpdb;
        $existing = $this->find($jobId, $rowNumber);
        if ($existing !== null) {
            if ((string) $existing['row_hash'] !== $rowHash) {
                throw new RuntimeException('The import row identity changed for an existing journal entry.');
            }
            return $existing;
        }

        $ok = $wpdb->insert($this->table, [
            'job_id' => $jobId,
            'source_row_number' => $rowNumber,
            'row_hash' => $rowHash,
            'action' => '',
            'location_id' => 0,
            'status' => 'processing',
            'error_code' => '',
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true),
        ], ['%d','%d','%s','%s','%d','%s','%s','%s','%s']);

        if ($ok === false) {
            $existing = $this->find($jobId, $rowNumber);
            if ($existing !== null && (string) $existing['row_hash'] === $rowHash) {
                return $existing;
            }
            throw new RuntimeException('Unable to create an import row journal entry.');
        }

        return [
            'id' => (int) $wpdb->insert_id,
            'job_id' => $jobId,
            'source_row_number' => $rowNumber,
            'row_number' => $rowNumber,
            'row_hash' => $rowHash,
            'action' => '',
            'location_id' => 0,
            'status' => 'processing',
            'error_code' => '',
        ];
    }

    public function commit(int $id, string $action, int $locationId = 0, string $errorCode = ''): void
    {
        global $wpdb;
        $updated = $wpdb->update($this->table, [
            'action' => $action,
            'location_id' => $locationId,
            'status' => 'committed',
            'error_code' => $errorCode,
            'updated_at' => current_time('mysql', true),
        ], ['id' => $id], ['%s','%d','%s','%s','%s'], ['%d']);
        if ($updated === false) {
            throw new RuntimeException('Unable to commit an import row journal entry.');
        }
    }

    public function deleteByJobId(int $jobId): void
    {
        global $wpdb;
        $wpdb->delete($this->table, ['job_id' => $jobId], ['%d']);
    }

    private function normalize(array $row): array
    {
        if (!isset($row['row_number']) && isset($row['source_row_number'])) {
            $row['row_number'] = $row['source_row_number'];
        }
        foreach (['id','job_id','source_row_number','row_number','location_id'] as $key) {
            $row[$key] = (int) ($row[$key] ?? 0);
        }
        return $row;
    }
}
