<?php
declare(strict_types=1);
namespace BusinessMapLocator\Migration;
/** Control-plane owner for the City-to-Area execution phases. */
final class AreaMigrationExecutor
{
    /** @var null|callable(string):void */ private $failureInjector;
    public function __construct(private readonly MigrationSnapshotStore $snapshots, private readonly AreaMigrationStateStore $state, private readonly AreaMigrationLock $lock, private readonly AreaMigrationJournal $journal, private readonly AreaMigrationRevalidator $revalidator, ?callable $failureInjector = null) {$this->failureInjector=$failureInjector;}
    /** @return array<string,mixed> */ public function inspectEligibility(string $runId): array { $run=$this->state->get($runId);if($run===null)return $this->blocked('RUN_NOT_FOUND');$snapshot=$this->snapshot($run);if($snapshot===null)return $this->blocked('SNAPSHOT_UNAVAILABLE',$run);if(($snapshot['schema_version']??null)!==2)return $this->blocked('SNAPSHOT_VERSION_UNSUPPORTED',$run,$snapshot);if(($run['state']??null)!==AreaMigrationStateStore::READY)return $this->blocked('RUN_NOT_READY',$run,$snapshot);if(!$this->hasPlanEvidence($snapshot))return $this->blocked('PLANNING_EVIDENCE_MISSING',$run,$snapshot);if($this->hasBlockers($snapshot))return $this->blocked('PLANNER_BLOCKERS_PRESENT',$run,$snapshot);if(!$this->ownsLock($runId))return $this->blocked('LOCK_NOT_OWNED',$run,$snapshot);$drift=$this->revalidator->inspect($snapshot);if(!$drift['valid'])return $this->blocked('DRIFT_DETECTED',$run,$snapshot,$drift);return $this->result(true,'ELIGIBLE',$run,$snapshot,$drift); }
    /** @return array<string,mixed> */ public function beginTermPhase(string $runId): array { $result=$this->inspectEligibility($runId);if(!$result['eligible'])return $result;$run=$this->state->transition($runId,AreaMigrationStateStore::RUNNING_TERMS,['execution_phase'=>'terms_entered_at_'.gmdate('c')]);return $this->result(true,'TERM_PHASE_ENTERED',$run,$this->snapshot($run)??[],$result['drift']); }
    /** @return array<string,mixed> */ public function beginRelationshipPhase(string $runId): array { $run=$this->state->get($runId);if($run===null||($run['state']??null)!==AreaMigrationStateStore::RUNNING_TERMS||!$this->ownsLock($runId))return $this->blocked('RELATIONSHIP_PHASE_NOT_ALLOWED',$run??[]);return $this->result(true,'RELATIONSHIP_PHASE_ENTERED',$this->state->transition($runId,AreaMigrationStateStore::RUNNING_RELATIONSHIPS),$this->snapshot($run)??[],['valid'=>true,'drift'=>[],'summary'=>['count'=>0]]); }
    /** Same-run, term-phase-only resume for journal-aware PARTIAL work. @return array<string,mixed> */ public function resumePartialTermPhase(string $runId): array { $run=$this->state->get($runId);$snapshot=$run===null?null:$this->snapshot($run);if($run===null||($run['state']??null)!==AreaMigrationStateStore::PARTIAL||$snapshot===null||($snapshot['schema_version']??null)!==2||!$this->hasPlanEvidence($snapshot)||$this->hasBlockers($snapshot)||!$this->ownsLock($runId)||!$this->revalidator->inspect($snapshot)['valid'])return $this->blocked('PARTIAL_RESUME_NOT_ALLOWED',$run??[],$snapshot??[]);$this->journal->listRunOperations($runId);$this->state->transition($runId,AreaMigrationStateStore::RUNNING_TERMS,['term_phase_resumed_at'=>gmdate('c')]);return $this->executeTermPhase($runId); }
    /** Same-run, relationship-phase-only resume for a journal-aware PARTIAL batch. @return array<string,mixed> */
    public function resumePartialRelationshipPhase(string $runId): array
    {
        $run = $this->state->get($runId); $snapshot = $run === null ? null : $this->snapshot($run);
        if ($run === null || ($run['state'] ?? null) !== AreaMigrationStateStore::PARTIAL || $snapshot === null || ($snapshot['schema_version'] ?? null) !== 2 || !$this->hasPlanEvidence($snapshot) || $this->hasBlockers($snapshot) || !$this->ownsLock($runId) || empty($run['relationship_failures']) || !$this->completedRelationshipsRemainValid($runId, $snapshot)) {
            return $this->blocked('PARTIAL_RELATIONSHIP_RESUME_NOT_ALLOWED', $run ?? [], $snapshot ?? []);
        }
        $this->journal->listRunOperations($runId);
        $this->state->transition($runId, AreaMigrationStateStore::RUNNING_RELATIONSHIPS, ['relationship_phase_resumed_at' => gmdate('c')]);
        return $this->executeRelationshipPhase($runId);
    }
    /** Executes only CREATE_AREA and reciprocal provenance operations. @return array<string,mixed> */ public function executeTermPhase(string $runId): array { $run=$this->state->get($runId);if(($run['state']??null)===AreaMigrationStateStore::READY){$entry=$this->beginTermPhase($runId);if(!$entry['eligible'])return $entry;$run=$this->state->get($runId);}if($run===null||($run['state']??null)!==AreaMigrationStateStore::RUNNING_TERMS||!$this->ownsLock($runId))return $this->blocked('TERM_PHASE_NOT_ALLOWED',$run??[]);$snapshot=$this->snapshot($run);if($snapshot===null)return $this->blocked('SNAPSHOT_UNAVAILABLE',$run);$completed=0;$failed=[];foreach((array)($snapshot['plan']['city_decisions']??[]) as $decision){if(($decision['status']??null)!=='CREATE')continue;try{if($this->hasPreCreateCityConflict($runId,$decision))throw new \RuntimeException('CITY_PROVENANCE_CONFLICT');$this->executeCity($runId,$decision,$snapshot);$completed++;}catch(\RuntimeException $e){$failed[]=['city_id'=>$decision['city_id']??0,'reason'=>$e->getMessage()];}}if($failed!==[]){$next=$completed>0?AreaMigrationStateStore::PARTIAL:AreaMigrationStateStore::BLOCKED;$this->state->transition($runId,$next,['term_failures'=>$failed]);return ['eligible'=>false,'blocked'=>true,'code'=>$next,'completed'=>$completed,'failures'=>$failed];}return ['eligible'=>true,'blocked'=>false,'code'=>'TERM_PHASE_COMPLETE','completed'=>$completed,'run_state'=>AreaMigrationStateStore::RUNNING_TERMS]; }
    /** Executes only planned ADD_LOCATION_AREA source relationships. @return array<string,mixed> */
    public function executeRelationshipPhase(string $runId): array
    {
        $run = $this->state->get($runId);
        if (($run['state'] ?? null) === AreaMigrationStateStore::COMPLETED && $this->ownsLock($runId)) {
            $snapshot = $this->snapshot($run);
            if ($snapshot === null || !$this->completedRelationshipsRemainValid($runId, $snapshot, true)) {
                return $this->blocked('COMPLETED_RELATIONSHIP_DRIFT', $run, $snapshot ?? []);
            }
            return ['eligible' => true, 'blocked' => false, 'code' => 'RELATIONSHIP_PHASE_ALREADY_COMPLETE', 'completed' => 0, 'run_state' => AreaMigrationStateStore::COMPLETED];
        }
        if (($run['state'] ?? null) === AreaMigrationStateStore::RUNNING_TERMS) {
            $entry = $this->beginRelationshipPhase($runId);
            if (!$entry['eligible']) { return $entry; }
            $run = $this->state->get($runId);
        }
        if ($run === null || ($run['state'] ?? null) !== AreaMigrationStateStore::RUNNING_RELATIONSHIPS || !$this->ownsLock($runId)) {
            return $this->blocked('RELATIONSHIP_PHASE_NOT_ALLOWED', $run ?? []);
        }
        $snapshot = $this->snapshot($run);
        if ($snapshot === null || ($snapshot['schema_version'] ?? null) !== 2) { return $this->blocked('SNAPSHOT_UNAVAILABLE', $run); }

        $completed = 0; $failures = [];
        foreach ((array) ($snapshot['plan']['location_decisions'] ?? []) as $decision) {
            if (($decision['status'] ?? null) !== 'ADD_AREA') { continue; }
            try { $this->executeLocationArea($runId, $decision, $snapshot); $completed++; }
            catch (\RuntimeException $error) { $failures[] = ['location_id' => (int) ($decision['location_id'] ?? 0), 'reason' => $error->getMessage()]; }
        }
        if ($failures !== []) {
            $next = $completed > 0 ? AreaMigrationStateStore::PARTIAL : AreaMigrationStateStore::BLOCKED;
            $this->state->transition($runId, $next, ['relationship_failures' => $failures]);
            return ['eligible' => false, 'blocked' => true, 'code' => $next, 'completed' => $completed, 'failures' => $failures];
        }
        $this->state->transition($runId, AreaMigrationStateStore::COMPLETED, ['relationship_phase_completed_at' => gmdate('c')]);
        return ['eligible' => true, 'blocked' => false, 'code' => 'RELATIONSHIP_PHASE_COMPLETE', 'completed' => $completed, 'run_state' => AreaMigrationStateStore::COMPLETED];
    }

    /** @param array<string,mixed> $decision @param array<string,mixed> $snapshot */
    private function executeLocationArea(string $runId, array $decision, array $snapshot): void
    {
        $locationId = (int) ($decision['location_id'] ?? 0);
        $expectedCities = $this->ids($decision['city_ids'] ?? []);
        if ($locationId <= 0 || get_post($locationId) === null) { throw new \RuntimeException('LOCATION_MISSING'); }
        if (count($expectedCities) !== 1) { throw new \RuntimeException('PLANNED_CITY_INVALID'); }
        $cityId = $expectedCities[0]; $currentCities = $this->postTermIds($locationId, 'bml_city');
        if ($currentCities !== [$cityId]) { throw new \RuntimeException(count($currentCities) > 1 ? 'LOCATION_SECOND_CITY' : 'LOCATION_CITY_DRIFT'); }

        $area = $this->expectedArea($decision, $snapshot, $cityId);
        $areaId = $area['id'];
        if ((int) get_term_meta($cityId, '_bml_area_term_id', true) !== $areaId || (int) get_term_meta($areaId, '_bml_migrated_from_city_term_id', true) !== $cityId) {
            throw new \RuntimeException('AREA_PROVENANCE_DRIFT');
        }
        $currentAreas = $this->postTermIds($locationId, 'bml_area');
        if ($currentAreas !== [] && $currentAreas !== [$areaId]) { throw new \RuntimeException('LOCATION_AREA_CONFLICT'); }

        $identity = ['location_id' => $locationId, 'city_term_id' => $cityId, 'area_term_id' => $areaId];
        $op = $this->journal->planOperation($runId, 2, 'ADD_LOCATION_AREA', $identity, ['city_ids_before' => $currentCities, 'area_ids_before' => $currentAreas]);
        if (($op['state'] ?? null) === AreaMigrationJournal::COMPLETED) { return; }
        if ($currentAreas === []) {
            $op = $this->start($runId, $op);
            $written = wp_set_object_terms($locationId, [$areaId], 'bml_area', true);
            if (is_wp_error($written)) { $this->journal->failOperation($runId, $op['operation_key'], ['reason' => 'LOCATION_AREA_WRITE_FAILED']); throw new \RuntimeException('LOCATION_AREA_WRITE_FAILED'); }
            $this->checkpoint('AFTER_LOCATION_AREA_ADDED');
            $op = $this->journal->markApplied($runId, $op['operation_key'], ['location_id' => $locationId, 'city_term_id' => $cityId, 'area_term_id' => $areaId, 'relationship_added_by_run' => true]);
        } elseif (($op['state'] ?? null) === AreaMigrationJournal::PLANNED || ($op['state'] ?? null) === AreaMigrationJournal::STARTED) {
            if (($op['state'] ?? null) === AreaMigrationJournal::PLANNED) { $op = $this->start($runId, $op); }
            $op = $this->journal->markApplied($runId, $op['operation_key'], ['location_id' => $locationId, 'city_term_id' => $cityId, 'area_term_id' => $areaId, 'relationship_added_by_run' => ((array) ($op['precondition']['area_ids_before'] ?? [])) === [], 'reconciled' => true]);
        }
        if ($this->postTermIds($locationId, 'bml_area') !== [$areaId] || $this->postTermIds($locationId, 'bml_city') !== [$cityId]) {
            if (($op['state'] ?? null) === AreaMigrationJournal::APPLIED) { $this->journal->failOperation($runId, $op['operation_key'], ['reason' => 'LOCATION_AREA_VERIFICATION_FAILED']); }
            throw new \RuntimeException('LOCATION_AREA_VERIFICATION_FAILED');
        }
        $this->finish($runId, $op);
    }

    /** @param array<string,mixed> $decision @param array<string,mixed> $snapshot @return array{id:int,name:string,slug:string,parent:int} */
    private function expectedArea(array $decision, array $snapshot, int $cityId): array
    {
        $areaId = (int) ($decision['target_area_id'] ?? get_term_meta($cityId, '_bml_area_term_id', true));
        $area = $this->term($areaId, 'bml_area');
        if ($area === null) { throw new \RuntimeException('AREA_MISSING'); }
        $planned = (array) ($decision['planned_area'] ?? []);
        if ($planned !== [] && ($area['name'] !== (string) ($planned['name'] ?? '') || $area['slug'] !== (string) ($planned['slug'] ?? '') || $area['parent'] !== (int) ($planned['parent'] ?? 0))) {
            throw new \RuntimeException('AREA_IDENTITY_DRIFT');
        }
        foreach ((array) ($snapshot['terms']['bml_area'] ?? []) as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $areaId && ($area['name'] !== (string) $candidate['name'] || $area['slug'] !== (string) $candidate['slug'] || $area['parent'] !== (int) $candidate['parent'])) {
                throw new \RuntimeException('AREA_IDENTITY_DRIFT');
            }
        }
        return $area;
    }

    /** @param array<string,mixed> $snapshot */
    private function completedRelationshipsRemainValid(string $runId, array $snapshot, bool $requireAll = false): bool
    {
        foreach ((array) ($snapshot['plan']['location_decisions'] ?? []) as $decision) {
            if (($decision['status'] ?? null) !== 'ADD_AREA') { continue; }
            $locationId = (int) ($decision['location_id'] ?? 0); $cities = $this->ids($decision['city_ids'] ?? []);
            if (count($cities) !== 1) { return false; }
            try {
                $area = $this->expectedArea($decision, $snapshot, $cities[0]);
                $op = $this->journal->getOperation($runId, 'ADD_LOCATION_AREA', ['location_id' => $locationId, 'city_term_id' => $cities[0], 'area_term_id' => $area['id']]);
                if (($op['state'] ?? null) !== AreaMigrationJournal::COMPLETED) { if ($requireAll) { return false; } continue; }
                if (get_post($locationId) === null || $this->postTermIds($locationId, 'bml_city') !== [$cities[0]] || $this->postTermIds($locationId, 'bml_area') !== [$area['id']] || (int) get_term_meta($cities[0], '_bml_area_term_id', true) !== $area['id'] || (int) get_term_meta($area['id'], '_bml_migrated_from_city_term_id', true) !== $cities[0]) { return false; }
            } catch (\RuntimeException) { return false; }
        }
        return true;
    }

    /** @param mixed $ids @return list<int> */ private function ids(mixed $ids): array { $ids = is_array($ids) ? array_map('intval', $ids) : []; sort($ids, SORT_NUMERIC); return array_values(array_unique($ids)); }
    /** @return list<int> */ private function postTermIds(int $locationId, string $taxonomy): array { $terms = wp_get_post_terms($locationId, $taxonomy, ['fields' => 'ids']); if (is_wp_error($terms)) { throw new \RuntimeException('LOCATION_TERM_READ_FAILED'); } return $this->ids($terms); }
    /** @param array<string,mixed> $decision */ private function hasPreCreateCityConflict(string $runId,array $decision): bool { $cityId=(int)$decision['city_id'];$current=(int)get_term_meta($cityId,'_bml_area_term_id',true);$ownedArea=null;foreach($this->journal->listRunOperations($runId)as $op)if(($op['operation_type']??'')==='CREATE_AREA'&&(int)($op['identity']['city_term_id']??0)===$cityId){$plan=(array)($op['identity']['planned_area']??[]);$area=$this->findArea($plan);if($area!==null&&((int)($op['result']['area_term_id']??0)===$area['id']||($op['state']??'')===AreaMigrationJournal::STARTED))$ownedArea=$area['id'];}if($ownedArea!==null&&!in_array((int)get_term_meta($ownedArea,'_bml_migrated_from_city_term_id',true),[$cityId,0],true))return true;return $current!==0&&$current!==$ownedArea; }
    /** @param array<string,mixed> $decision @param array<string,mixed> $snapshot */ private function executeCity(string $runId,array $decision,array $snapshot): void { $cityId=(int)$decision['city_id'];$plan=(array)$decision['planned_area'];$city=$this->term($cityId,'bml_city');if($city===null)throw new \RuntimeException('CITY_MISSING');$snapCity=null;foreach((array)($snapshot['terms']['bml_city']??[]) as $candidate)if((int)($candidate['id']??0)===$cityId)$snapCity=$candidate;if($snapCity===null||(string)$snapCity['name']!==$city['name']||(string)$snapCity['slug']!==$city['slug'])throw new \RuntimeException('CITY_DRIFT');$identity=['city_term_id'=>$cityId,'planned_area'=>$plan];$area=$this->findArea($plan);$create=$this->journal->planOperation($runId,2,'CREATE_AREA',$identity,['area_absent_before'=>$area===null]);if($area===null){$create=$this->start($runId,$create);$created=wp_insert_term((string)$plan['name'],'bml_area',['slug'=>(string)$plan['slug'],'parent'=>0]);if(is_wp_error($created))throw new \RuntimeException('AREA_CREATE_FAILED');$area=$this->term((int)$created['term_id'],'bml_area');if($area===null)throw new \RuntimeException('AREA_CREATE_UNVERIFIABLE');$this->checkpoint('AFTER_AREA_CREATED');$create=$this->journal->markApplied($runId,$create['operation_key'],['city_term_id'=>$cityId,'area_term_id'=>$area['id'],'area_name'=>$area['name'],'area_slug'=>$area['slug'],'parent'=>$area['parent'],'created_by_run'=>true,'ownership_resolution'=>'observed_create']);}else{if($area['name']!==(string)$plan['name']||$area['slug']!==(string)$plan['slug']||$area['parent']!==0)throw new \RuntimeException('AREA_COLLISION');if(($create['state']??'')===AreaMigrationJournal::PLANNED)throw new \RuntimeException('AREA_COLLISION');if(($create['state']??'')===AreaMigrationJournal::STARTED)$create=$this->journal->markApplied($runId,$create['operation_key'],['city_term_id'=>$cityId,'area_term_id'=>$area['id'],'area_name'=>$area['name'],'area_slug'=>$area['slug'],'parent'=>0,'created_by_run'=>null,'ownership_resolution'=>'recovered_unknown_outcome']);}$create=$this->finish($runId,$create);$this->writeProvenance($runId,'WRITE_CITY_PROVENANCE',['city_term_id'=>$cityId,'area_term_id'=>$area['id']],$cityId,'_bml_area_term_id',$area['id']);$this->writeProvenance($runId,'WRITE_AREA_PROVENANCE',['area_term_id'=>$area['id'],'city_term_id'=>$cityId],$area['id'],'_bml_migrated_from_city_term_id',$cityId);if((int)get_term_meta($cityId,'_bml_area_term_id',true)!==$area['id']||(int)get_term_meta($area['id'],'_bml_migrated_from_city_term_id',true)!==$cityId)throw new \RuntimeException('RECIPROCAL_VERIFICATION_FAILED'); }
    /** @param array<string,mixed> $identity */
    private function writeProvenance(string $runId, string $type, array $identity, int $termId, string $key, int $expected): void
    {
        $current = (int) get_term_meta($termId, $key, true);
        if ($current !== 0 && $current !== $expected) { throw new \RuntimeException('PROVENANCE_CONFLICT'); }
        $op = $this->journal->planOperation($runId, 2, $type, $identity, ['previous_value' => $current, 'expected_value' => $expected]);
        if (($op['state'] ?? '') === AreaMigrationJournal::PLANNED) {
            $op = $this->start($runId, $op);
            if ($current === $expected) {
                $op = $this->journal->markApplied($runId, $op['operation_key'], ['term_id' => $termId, 'previous_value' => $current, 'written_value' => $expected, 'written_by_run' => false, 'reconciled_preexisting' => true, 'ownership_resolution' => 'preexisting']);
            } else {
                update_term_meta($termId, $key, $expected);
                $this->checkpoint($type === 'WRITE_CITY_PROVENANCE' ? 'AFTER_CITY_PROVENANCE_WRITTEN' : 'AFTER_AREA_PROVENANCE_WRITTEN');
                $op = $this->journal->markApplied($runId, $op['operation_key'], ['term_id' => $termId, 'previous_value' => $current, 'written_value' => $expected, 'written_by_run' => true, 'reconciled_preexisting' => false, 'ownership_resolution' => 'observed_write']);
            }
        } elseif (($op['state'] ?? '') === AreaMigrationJournal::STARTED) {
            if ($current === $expected) {
                $op = $this->journal->markApplied($runId, $op['operation_key'], ['term_id' => $termId, 'previous_value' => (int) ($op['precondition']['previous_value'] ?? 0), 'written_value' => $expected, 'written_by_run' => null, 'reconciled_preexisting' => false, 'ownership_resolution' => 'recovered_unknown_outcome']);
            } else {
                update_term_meta($termId, $key, $expected);
                $op = $this->journal->markApplied($runId, $op['operation_key'], ['term_id' => $termId, 'previous_value' => (int) ($op['precondition']['previous_value'] ?? 0), 'written_value' => $expected, 'written_by_run' => true, 'reconciled_preexisting' => false, 'ownership_resolution' => 'observed_write']);
            }
        }
        $this->finish($runId, $op);
    }
    private function checkpoint(string $point): void { if($this->failureInjector!==null)($this->failureInjector)($point); }
    /** @param array<string,mixed> $op @return array<string,mixed> */ private function start(string $runId,array $op): array { return ($op['state']??'')===AreaMigrationJournal::PLANNED?$this->journal->beginOperation($runId,$op['operation_key']):$op; }
    /** @param array<string,mixed> $op @return array<string,mixed> */ private function finish(string $runId,array $op): array { if(($op['state']??'')===AreaMigrationJournal::APPLIED)$op=$this->journal->markVerified($runId,$op['operation_key'],[]);if(($op['state']??'')===AreaMigrationJournal::VERIFIED)$op=$this->journal->completeOperation($runId,$op['operation_key']);return $op; }
    /** @return array{id:int,name:string,slug:string,parent:int}|null */ private function term(int $id,string $taxonomy): ?array { foreach((array)get_terms(['taxonomy'=>$taxonomy,'hide_empty'=>false,'number'=>0]) as $term)if((int)$term->term_id===$id)return ['id'=>$id,'name'=>(string)$term->name,'slug'=>(string)$term->slug,'parent'=>(int)$term->parent];return null; }
    /** @param array<string,mixed> $plan @return array{id:int,name:string,slug:string,parent:int}|null */ private function findArea(array $plan): ?array { $found=[];foreach((array)get_terms(['taxonomy'=>'bml_area','hide_empty'=>false,'number'=>0]) as $term)if((string)$term->slug===(string)$plan['slug']||(string)$term->name===(string)$plan['name'])$found[]=['id'=>(int)$term->term_id,'name'=>(string)$term->name,'slug'=>(string)$term->slug,'parent'=>(int)$term->parent];return count($found)===1?$found[0]:null; }
    /** @param array<string,mixed> $run @return array<string,mixed>|null */ private function snapshot(array $run): ?array { return isset($run['snapshot_path'])&&is_string($run['snapshot_path'])?$this->snapshots->read($run['snapshot_path']):null; }
    /** @param array<string,mixed> $snapshot */ private function hasPlanEvidence(array $snapshot): bool { return is_array($snapshot['plan']??null)&&is_array($snapshot['plan']['counts']??null); }
    /** @param array<string,mixed> $snapshot */ private function hasBlockers(array $snapshot): bool { $plan=(array)($snapshot['plan']??[]);return !empty($plan['collision_list'])||!empty($plan['ambiguous_list'])||!empty($plan['decision_required_list']); }
    private function ownsLock(string $runId): bool { $owner=$this->lock->owner();return $this->lock->isLocked()&&$owner!==null&&(string)$owner['run_id']===$runId; }
    /** @param array<string,mixed> $run @param array<string,mixed> $snapshot @param array<string,mixed>|null $drift @return array<string,mixed> */ private function blocked(string $code,array $run=[],array $snapshot=[],?array $drift=null): array { return $this->result(false,$code,$run,$snapshot,$drift??['valid'=>null,'drift'=>[],'summary'=>['count'=>0]]); }
    /** @param array<string,mixed> $run @param array<string,mixed> $snapshot @param array<string,mixed> $drift @return array<string,mixed> */ private function result(bool $eligible,string $code,array $run,array $snapshot,array $drift): array { $runId=(string)($run['run_id']??'');$operations=$runId===''?[]:$this->journal->listRunOperations($runId);return ['eligible'=>$eligible,'blocked'=>!$eligible,'code'=>$code,'run_state'=>$run['state']??null,'snapshot_version'=>$snapshot['schema_version']??null,'lock'=>['owned_by_run'=>$runId!==''&&$this->ownsLock($runId),'owner'=>$this->lock->owner()],'journal'=>['available'=>true,'operations'=>count($operations),'incomplete'=>count($runId===''?[]:$this->journal->listIncompleteOperations($runId))],'plan_summary'=>$snapshot['plan']['counts']??[],'drift'=>$drift]; }
}
