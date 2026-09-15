<?php
declare(strict_types=1);
namespace BusinessMapLocator\Migration;
final class AreaMigrationLock
{
    private const OPTION = 'bml_area_migration_planning_lock';

    public function acquire(string $runId, int $ttl = 300): bool
    {
        $lock = $this->newLock($runId, $ttl);
        if (add_option(self::OPTION, $lock, '', false)) {
            return true;
        }
        $existing = $this->owner();
        if ($existing === null || (int) $existing['expires_at'] > time()) {
            return false;
        }
        if (!$this->deleteUnchanged($existing)) {
            return false;
        }
        add_option(self::OPTION, $lock, '', false);
        $acquired = $this->owner();
        return $acquired !== null && (string) $acquired['run_id'] === $runId;
    }

    public function release(string $runId): bool
    {
        $lock = $this->owner();
        return $lock !== null && (string) $lock['run_id'] === $runId && $this->deleteUnchanged($lock);
    }

    public function isLocked(): bool
    {
        $lock = $this->owner();
        return $lock !== null && (int) $lock['expires_at'] > time();
    }

    /** @return array<string,mixed>|null */
    public function owner(): ?array
    {
        $lock = get_option(self::OPTION, null);
        return is_array($lock) ? $lock : null;
    }

    /** @return array<string,int|string> */
    private function newLock(string $runId, int $ttl): array
    {
        $now = time();
        return ['run_id' => $runId, 'acquired_at' => $now, 'expires_at' => $now + max(1, $ttl)];
    }

    /** @param array<string,mixed> $expected */
    private function deleteUnchanged(array $expected): bool
    {
        global $wpdb;
        if (isset($wpdb) && is_object($wpdb) && isset($wpdb->options) && method_exists($wpdb, 'prepare') && method_exists($wpdb, 'query')) {
            $sql = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::OPTION, maybe_serialize($expected));
            return $wpdb->query($sql) === 1;
        }
        if ($this->owner() !== $expected) {
            return false;
        }
        return delete_option(self::OPTION);
    }
}
