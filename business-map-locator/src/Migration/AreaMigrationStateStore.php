<?php
declare(strict_types=1);
namespace BusinessMapLocator\Migration;
use LogicException;
final class AreaMigrationStateStore
{
    public const NEW='NEW', INSPECTED='INSPECTED', SNAPSHOTTED='SNAPSHOTTED', SIMULATED='SIMULATED', READY='READY', BLOCKED='BLOCKED';
    private const OPTION='bml_area_migration_planning_runs';
    /** @return array<string,mixed> */ public function create(): array { $id=function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : bin2hex(random_bytes(16)); $run=['run_id'=>$id,'state'=>self::NEW,'created_at'=>gmdate('c'),'updated_at'=>gmdate('c')]; $all=$this->all(); $all[$id]=$run; update_option(self::OPTION,$all,false); return $run; }
    /** @return array<string,mixed>|null */ public function get(string $id): ?array { $all=$this->all(); return isset($all[$id])&&is_array($all[$id])?$all[$id]:null; }
    /** @param array<string,mixed> $evidence @return array<string,mixed> */ public function transition(string $id,string $next,array $evidence=[]): array { $run=$this->get($id); if($run===null) throw new LogicException('Planning run not found.'); $allowed=[self::NEW=>[self::INSPECTED],self::INSPECTED=>[self::SNAPSHOTTED],self::SNAPSHOTTED=>[self::SIMULATED],self::SIMULATED=>[self::READY,self::BLOCKED]]; if(!in_array($next,$allowed[$run['state']]??[],true)) throw new LogicException('Invalid planning state transition.'); $run=array_merge($run,$evidence,['state'=>$next,'updated_at'=>gmdate('c')]); $all=$this->all();$all[$id]=$run;update_option(self::OPTION,$all,false);return $run; }
    /** @return array<string,mixed> */ private function all(): array { $runs=get_option(self::OPTION,[]);return is_array($runs)?$runs:[]; }
}
