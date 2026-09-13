<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

use LogicException;

final class AreaMigrationStateStore
{
    public const NEW = 'NEW'; public const INSPECTED = 'INSPECTED'; public const SNAPSHOTTED = 'SNAPSHOTTED'; public const SIMULATED = 'SIMULATED'; public const READY = 'READY'; public const RUNNING_TERMS = 'RUNNING_TERMS'; public const RUNNING_RELATIONSHIPS = 'RUNNING_RELATIONSHIPS'; public const COMPLETED = 'COMPLETED'; public const PARTIAL = 'PARTIAL'; public const FAILED = 'FAILED'; public const BLOCKED = 'BLOCKED'; public const ROLLBACK_RUNNING = 'ROLLBACK_RUNNING'; public const ROLLED_BACK = 'ROLLED_BACK'; public const ROLLBACK_PARTIAL = 'ROLLBACK_PARTIAL';
    private const OPTION = 'bml_area_migration_planning_runs';

    /** @return array<string,mixed> */ public function create(): array { $id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : bin2hex(random_bytes(16)); $run = ['run_id' => $id, 'state' => self::NEW, 'created_at' => gmdate('c'), 'updated_at' => gmdate('c')]; $all = $this->all(); $all[$id] = $run; update_option(self::OPTION, $all, false); return $run; }
    /** @return array<string,mixed>|null */ public function get(string $id): ?array { $all = $this->all(); return isset($all[$id]) && is_array($all[$id]) ? $all[$id] : null; }
    /** @param array<string,mixed> $evidence @return array<string,mixed> */ public function transition(string $id, string $next, array $evidence = []): array { $run = $this->get($id); if ($run === null) throw new LogicException('Migration run not found.'); if (!in_array($next, self::transitions()[$run['state']] ?? [], true)) throw new LogicException('Invalid migration state transition.'); $run = array_merge($run, $evidence, ['state' => $next, 'updated_at' => gmdate('c')]); $all = $this->all(); $all[$id] = $run; update_option(self::OPTION, $all, false); return $run; }
    /** @return list<string> */ public static function rollbackEligibleStates(): array { return [self::COMPLETED, self::PARTIAL, self::FAILED]; }
    /** @return array<string,list<string>> */ private static function transitions(): array { return [self::NEW => [self::INSPECTED], self::INSPECTED => [self::SNAPSHOTTED], self::SNAPSHOTTED => [self::SIMULATED], self::SIMULATED => [self::READY, self::BLOCKED], self::READY => [self::RUNNING_TERMS], self::RUNNING_TERMS => [self::RUNNING_RELATIONSHIPS, self::PARTIAL, self::FAILED, self::BLOCKED], self::RUNNING_RELATIONSHIPS => [self::COMPLETED, self::PARTIAL, self::FAILED, self::BLOCKED], self::COMPLETED => [self::ROLLBACK_RUNNING], self::PARTIAL => [self::RUNNING_TERMS, self::RUNNING_RELATIONSHIPS, self::ROLLBACK_RUNNING], self::FAILED => [self::ROLLBACK_RUNNING], self::ROLLBACK_RUNNING => [self::ROLLED_BACK, self::ROLLBACK_PARTIAL, self::FAILED]]; }
    /** @return array<string,mixed> */ private function all(): array { $runs = get_option(self::OPTION, []); return is_array($runs) ? $runs : []; }
}
