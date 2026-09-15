<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

use LogicException;
use RuntimeException;

/** Durable, non-mutating evidence for a future executor and rollback engine. */
final class AreaMigrationJournal
{
    public const VERSION = 1;
    public const PLANNED = 'PLANNED', STARTED = 'STARTED', APPLIED = 'APPLIED', VERIFIED = 'VERIFIED', COMPLETED = 'COMPLETED', FAILED = 'FAILED', ROLLBACK_PLANNED = 'ROLLBACK_PLANNED', ROLLBACK_STARTED = 'ROLLBACK_STARTED', ROLLED_BACK = 'ROLLED_BACK', ROLLBACK_FAILED = 'ROLLBACK_FAILED';
    private const TYPES = ['CREATE_AREA','WRITE_CITY_PROVENANCE','WRITE_AREA_PROVENANCE','ADD_LOCATION_AREA','REFRESH_LOCATION_INDEX','REMOVE_LOCATION_AREA','REMOVE_CITY_PROVENANCE','REMOVE_AREA_PROVENANCE','DELETE_RUN_CREATED_AREA'];

    /** @var null|callable(string):void */
    private $failureInjector;

    public function __construct(private readonly ?string $baseDirectory = null, ?callable $failureInjector = null)
    {
        $this->failureInjector = $failureInjector;
    }

    /** @param array<string,mixed> $identity @param array<string,mixed> $precondition @return array<string,mixed> */
    public function planOperation(string $runId, int $snapshotVersion, string $type, array $identity, array $precondition = []): array
    {
        if ($snapshotVersion !== 2) { throw new LogicException('Only v2 snapshots may have execution journal evidence.'); }
        if (!in_array($type, self::TYPES, true)) { throw new LogicException('Unknown journal operation type.'); }
        $key = $this->operationKey($runId, $type, $identity); $path = $this->path($runId, $key);
        if (is_file($path)) { $record = $this->readPath($path); if ($record !== null) { return $record; } throw new RuntimeException('Journal record is corrupt.'); }
        return $this->writeNew($path, ['journal_version' => self::VERSION, 'run_id' => $runId, 'operation_key' => $key, 'operation_type' => $type, 'identity' => $this->canonical($identity), 'precondition' => $precondition, 'state' => self::PLANNED, 'attempt' => 0, 'result' => [], 'failure' => null, 'created_at' => gmdate('c'), 'updated_at' => gmdate('c')]);
    }

    /** @param array<string,mixed> $identity */
    public function getOperation(string $runId, string $type, array $identity): ?array { $path=$this->path($runId, $this->operationKey($runId, $type, $identity)); return $this->readPath($path) ?? $this->readPath($path.'.prev'); }
    public function beginOperation(string $runId, string $key): array { return $this->transition($runId, $key, self::STARTED, [], true); }
    /** @param array<string,mixed> $result */
    public function markApplied(string $runId, string $key, array $result): array { return $this->transition($runId, $key, self::APPLIED, $result); }
    /** @param array<string,mixed> $result */
    public function markVerified(string $runId, string $key, array $result): array { return $this->transition($runId, $key, self::VERIFIED, $result); }
    /** @param array<string,mixed> $result */
    public function completeOperation(string $runId, string $key, array $result = []): array { return $this->transition($runId, $key, self::COMPLETED, $result); }
    /** @param array<string,mixed> $failure */
    public function failOperation(string $runId, string $key, array $failure): array { return $this->transition($runId, $key, self::FAILED, [], false, $failure); }
    public function planRollback(string $runId, string $key): array { return $this->transition($runId, $key, self::ROLLBACK_PLANNED); }
    public function beginRollback(string $runId, string $key): array { return $this->transition($runId, $key, self::ROLLBACK_STARTED, [], true); }
    public function markRolledBack(string $runId, string $key, array $result = []): array { return $this->transition($runId, $key, self::ROLLED_BACK, $result); }
    /** @param array<string,mixed> $failure */
    public function failRollback(string $runId, string $key, array $failure): array { return $this->transition($runId, $key, self::ROLLBACK_FAILED, [], false, $failure); }

    /** @return list<array<string,mixed>> */
    public function listRunOperations(string $runId): array { $files = glob($this->runDirectory($runId) . '/*.json') ?: []; sort($files, SORT_STRING); return array_values(array_filter(array_map(fn(string $p): ?array => $this->readPath($p), $files))); }
    /** @return list<array<string,mixed>> */
    public function listIncompleteOperations(string $runId): array { return array_values(array_filter($this->listRunOperations($runId), static fn(array $r): bool => !in_array($r['state'], [self::COMPLETED, self::ROLLED_BACK], true))); }
    /** @return array<string,int> */
    public function progress(string $runId): array { $summary = ['planned'=>0,'completed'=>0,'failed'=>0,'incomplete'=>0,'rollback'=>0]; foreach ($this->listRunOperations($runId) as $r) { $state=$r['state']; $summary['planned']++; if ($state===self::COMPLETED) $summary['completed']++; elseif (in_array($state,[self::FAILED,self::ROLLBACK_FAILED],true)) $summary['failed']++; elseif (str_starts_with($state,'ROLLBACK_')||$state===self::ROLLED_BACK) $summary['rollback']++; else $summary['incomplete']++; } return $summary; }

    /** @param array<string,mixed> $identity */
    private function operationKey(string $runId, string $type, array $identity): string { return hash('sha256', $runId . '|' . $type . '|' . wp_json_encode($this->canonical($identity))); }
    /** @param array<string,mixed> $result @param array<string,mixed>|null $failure @return array<string,mixed> */
    private function transition(string $runId, string $key, string $next, array $result = [], bool $incrementAttempt = false, ?array $failure = null): array
    {
        $path=$this->path($runId,$key); $lockPath=$path.'.lock'; $handle=@fopen($lockPath,'c+'); if($handle===false||!flock($handle,LOCK_EX)) throw new RuntimeException('Unable to lock journal operation.');
        try { $record=$this->readPath($path)??$this->readPath($path.'.prev'); if(!is_array($record)||($record['run_id']??'')!==$runId||($record['operation_key']??'')!==$key) throw new RuntimeException('Journal operation is invalid.'); $allowed=[self::PLANNED=>[self::STARTED],self::STARTED=>[self::APPLIED,self::FAILED],self::APPLIED=>[self::VERIFIED,self::FAILED],self::VERIFIED=>[self::COMPLETED,self::FAILED],self::FAILED=>[self::STARTED,self::ROLLBACK_PLANNED],self::COMPLETED=>[self::ROLLBACK_PLANNED],self::ROLLBACK_PLANNED=>[self::ROLLBACK_STARTED],self::ROLLBACK_STARTED=>[self::ROLLED_BACK,self::ROLLBACK_FAILED],self::ROLLBACK_FAILED=>[self::ROLLBACK_STARTED]]; if(!in_array($next,$allowed[$record['state']]??[],true)) throw new LogicException('Invalid journal state transition.'); $record['state']=$next;$record['result']=array_merge((array)$record['result'],$result);$record['failure']=$failure;$record['attempt']=(int)$record['attempt']+($incrementAttempt?1:0);$record['updated_at']=gmdate('c'); $tmp=$path.'.tmp-'.bin2hex(random_bytes(8));$json=wp_json_encode($record,JSON_THROW_ON_ERROR);$out=@fopen($tmp,'x');if($out===false)throw new RuntimeException('Unable to write journal candidate.');try{if(fwrite($out,$json)===false||!fflush($out))throw new RuntimeException('Unable to flush journal candidate.');if(function_exists('fsync'))fsync($out);}finally{fclose($out);}if($this->readPath($tmp)===null){@unlink($tmp);throw new RuntimeException('Journal candidate is invalid.');}$this->checkpoint('AFTER_TEMP_VALIDATED');$prev=$path.'.prev';if(is_file($prev))@unlink($prev);if(is_file($path)&&!rename($path,$prev)){@unlink($tmp);throw new RuntimeException('Unable to preserve prior journal record.');}$this->checkpoint('AFTER_CURRENT_MOVED_TO_PREVIOUS');if(!rename($tmp,$path)){throw new RuntimeException('Unable to promote journal candidate.');}$this->checkpoint('AFTER_TEMP_PROMOTED_TO_CURRENT');if($this->readPath($path)===null)throw new RuntimeException('Promoted journal record is invalid.');$this->checkpoint('BEFORE_PREVIOUS_CLEANUP');@unlink($prev);return $record; } finally { flock($handle,LOCK_UN);fclose($handle); }
    }
    /** @param array<string,mixed> $record @return array<string,mixed> */ private function writeNew(string $path,array $record): array { $dir=dirname($path);if(!is_dir($dir)&&!wp_mkdir_p($dir))throw new RuntimeException('Unable to create journal directory.');$h=@fopen($path,'x');if($h===false){$existing=$this->readPath($path);if($existing!==null)return $existing;throw new RuntimeException('Unable to create journal operation.');}try{fwrite($h,wp_json_encode($record,JSON_THROW_ON_ERROR));}finally{fclose($h);}return $record; }
    private function path(string $runId,string $key): string { return $this->runDirectory($runId).'/'.$key.'.json'; }
    private function runDirectory(string $runId): string { return rtrim($this->baseDirectory??(trailingslashit((string)wp_upload_dir()['basedir']).'business-map-locator/migration-journal'),'/\\').'/'.hash('sha256',$runId); }
    /** @return array<string,mixed>|null */ private function readPath(string $path): ?array { if(!is_readable($path))return null;try{$d=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);return is_array($d)?$d:null;}catch(\JsonException){return null;} }
    /** @param array<string,mixed> $value @return array<string,mixed> */ private function canonical(array $value): array { ksort($value);foreach($value as $k=>$v){if(is_array($v))$value[$k]=$this->canonical($v);}return $value; }
    private function checkpoint(string $point): void { if ($this->failureInjector !== null) { ($this->failureInjector)($point); } }
}
