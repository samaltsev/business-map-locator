<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use BusinessMapLocator\Migration\{AreaMigrationJournal,AreaMigrationLock,AreaMigrationRevalidator,AreaMigrationStateStore,AreaRollbackService,MigrationSnapshotStore};
final class AreaMigrationRollbackExecutionTest extends TestCase
{
    private string $dir;
    protected function setUp(): void
    {
        $this->dir=dirname(__DIR__).'/.rollback-'.bin2hex(random_bytes(4)); mkdir($this->dir,0777,true);
        $GLOBALS['bml_test_options']=$GLOBALS['bml_test_term_meta']=$GLOBALS['bml_test_post_terms']=[];$GLOBALS['bml_test_wp_delete_term_calls']=$GLOBALS['bml_test_delete_term_meta_calls']=$GLOBALS['bml_test_wp_remove_object_terms_calls']=0;
        $GLOBALS['bml_test_terms']=['bml_city'=>[1=>(object)['term_id'=>1,'name'=>'Minsk','slug'=>'minsk','parent'=>0]],'bml_area'=>[2=>(object)['term_id'=>2,'name'=>'Minsk','slug'=>'minsk','parent'=>0]]];
        $post=new WP_Post(); $post->ID=7; $post->post_type='bml_location'; $GLOBALS['bml_test_posts']=[7=>$post]; $GLOBALS['bml_test_post_terms']=[7=>['bml_city'=>[1],'bml_area'=>[2]]]; $GLOBALS['bml_test_term_meta']=[1=>['_bml_area_term_id'=>2],2=>['_bml_migrated_from_city_term_id'=>1]];
    }
    protected function tearDown(): void {$i=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($i as $x)$x->isDir()?rmdir($x->getPathname()):unlink($x->getPathname());rmdir($this->dir);}
    public function testHappyPathRemovesOnlyOwnedEffects(): void
    {
        [$service,$journal,$id]=$this->fixture();
        $this->forward($journal,$id,'ADD_LOCATION_AREA',['location_id'=>7,'city_term_id'=>1,'area_term_id'=>2],['location_id'=>7,'city_term_id'=>1,'area_term_id'=>2,'relationship_added_by_run'=>true]);
        $this->forward($journal,$id,'WRITE_CITY_PROVENANCE',['city_term_id'=>1,'area_term_id'=>2],['term_id'=>1,'written_value'=>2,'written_by_run'=>true]);
        $this->forward($journal,$id,'WRITE_AREA_PROVENANCE',['area_term_id'=>2,'city_term_id'=>1],['term_id'=>2,'written_value'=>1,'written_by_run'=>true]);
        $this->forward($journal,$id,'CREATE_AREA',['city_term_id'=>1],['area_term_id'=>2,'area_name'=>'Minsk','area_slug'=>'minsk','parent'=>0,'created_by_run'=>true],['area_absent_before'=>true]);
        $this->assertSame('ROLLED_BACK',$service->executeRollback($id)['code']);
        $this->assertSame([1],wp_get_post_terms(7,'bml_city',['fields'=>'ids']));
        $this->assertSame([],wp_get_post_terms(7,'bml_area',['fields'=>'ids']));
        $this->assertSame(0,(int)get_term_meta(1,'_bml_area_term_id',true));
        $this->assertFalse(isset($GLOBALS['bml_test_terms']['bml_area'][2]));
    }
    public function testUnknownOwnershipIsPreserved(): void
    {
        [$service,$journal,$id]=$this->fixture();
        $this->forward($journal,$id,'WRITE_CITY_PROVENANCE',['city_term_id'=>1,'area_term_id'=>2],['term_id'=>1,'written_value'=>2,'written_by_run'=>null]);
        $this->forward($journal,$id,'CREATE_AREA',['city_term_id'=>1],['area_term_id'=>2,'area_name'=>'Minsk','area_slug'=>'minsk','parent'=>0,'created_by_run'=>null],['area_absent_before'=>true]);
        $this->assertSame('ROLLED_BACK',$service->executeRollback($id)['code']);
        $this->assertSame(2,(int)get_term_meta(1,'_bml_area_term_id',true));
        $this->assertTrue(isset($GLOBALS['bml_test_terms']['bml_area'][2]));
    }
    public function testDriftIsPartialAndSameRunResumeCompletes(): void
    {
        [$service,$journal,$id]=$this->fixture();
        $this->forward($journal,$id,'WRITE_CITY_PROVENANCE',['city_term_id'=>1,'area_term_id'=>2],['term_id'=>1,'written_value'=>2,'written_by_run'=>true]);
        update_term_meta(1,'_bml_area_term_id',9);
        $this->assertSame('ROLLBACK_PARTIAL',$service->executeRollback($id)['code']);
        $this->assertSame(9,(int)get_term_meta(1,'_bml_area_term_id',true));
        update_term_meta(1,'_bml_area_term_id',2);
        $this->assertSame('ROLLED_BACK',$service->resumePartialRollback($id)['code']);
        $this->assertSame(0,(int)get_term_meta(1,'_bml_area_term_id',true));
    }
    /** @dataProvider crashFixtures */
    public function testCrashRecoveryDoesNotRepeatDestructiveMutation(string $type,string $checkpoint,string $counter): void
    {
        [$service,$journal,$id]=$this->fixture(static function (string $point) use ($checkpoint): void { if ($point===$checkpoint) throw new LogicException('crash'); });
        if($type==='ADD_LOCATION_AREA')$this->forward($journal,$id,$type,['location_id'=>7,'city_term_id'=>1,'area_term_id'=>2],['location_id'=>7,'city_term_id'=>1,'area_term_id'=>2,'relationship_added_by_run'=>true]);
        elseif($type==='CREATE_AREA'){$GLOBALS['bml_test_post_terms'][7]['bml_area']=[];unset($GLOBALS['bml_test_term_meta'][1]['_bml_area_term_id'],$GLOBALS['bml_test_term_meta'][2]['_bml_migrated_from_city_term_id']);$this->forward($journal,$id,$type,['city_term_id'=>1],['area_term_id'=>2,'area_name'=>'Minsk','area_slug'=>'minsk','parent'=>0,'created_by_run'=>true],['area_absent_before'=>true]);}
        else $this->forward($journal,$id,$type,$type==='WRITE_CITY_PROVENANCE'?['city_term_id'=>1,'area_term_id'=>2]:['area_term_id'=>2,'city_term_id'=>1],['term_id'=>$type==='WRITE_CITY_PROVENANCE'?1:2,'written_value'=>$type==='WRITE_CITY_PROVENANCE'?2:1,'written_by_run'=>true]);
        try{$service->executeRollback($id);$this->fail('Expected crash.');}catch(LogicException){}
        $calls=(int)($GLOBALS[$counter]??0);$this->assertSame('ROLLED_BACK',$service->executeRollback($id)['code']);$this->assertSame($calls,(int)($GLOBALS[$counter]??0));
        $reverse=array_values(array_filter($journal->listRunOperations($id),static fn(array $op):bool=>str_starts_with((string)$op['operation_type'],'REMOVE_')||($op['operation_type']??'')==='DELETE_RUN_CREATED_AREA'));
        $this->assertCount(1,$reverse);$this->assertSame(AreaMigrationJournal::ROLLED_BACK,$reverse[0]['state']);
    }
    public static function crashFixtures(): array {return [['ADD_LOCATION_AREA','AFTER_LOCATION_AREA_REMOVED','bml_test_wp_remove_object_terms_calls'],['WRITE_CITY_PROVENANCE','AFTER_CITY_PROVENANCE_REMOVED','bml_test_delete_term_meta_calls'],['WRITE_AREA_PROVENANCE','AFTER_AREA_PROVENANCE_REMOVED','bml_test_delete_term_meta_calls'],['CREATE_AREA','AFTER_AREA_DELETED','bml_test_wp_delete_term_calls']];}
    /** @dataProvider areaBlockers */
    public function testRunCreatedAreaSafetyBlockersPreserveArea(string $kind): void
    {
        [$service,$journal,$id,$state]=$this->fixture(); unset($GLOBALS['bml_test_term_meta'][2]['_bml_migrated_from_city_term_id']);
        $this->forward($journal,$id,'CREATE_AREA',['city_term_id'=>1],['area_term_id'=>2,'area_name'=>'Minsk','area_slug'=>'minsk','parent'=>0,'created_by_run'=>true],['area_absent_before'=>true]);
        if($kind==='modified')$GLOBALS['bml_test_terms']['bml_area'][2]->slug='changed';
        if($kind==='child')$GLOBALS['bml_test_terms']['bml_area'][3]=(object)['term_id'=>3,'name'=>'Child','slug'=>'child','parent'=>2];
        if($kind==='external'){$post=new WP_Post();$post->ID=8;$post->post_type='bml_location';$GLOBALS['bml_test_posts'][8]=$post;$GLOBALS['bml_test_post_terms'][8]=['bml_city'=>[1],'bml_area'=>[2]];}
        $this->assertSame('ROLLBACK_PARTIAL',$service->executeRollback($id)['code']);
        $this->assertSame(0,(int)($GLOBALS['bml_test_wp_delete_term_calls']??0));$this->assertTrue(isset($GLOBALS['bml_test_terms']['bml_area'][2]));$this->assertSame(AreaMigrationStateStore::ROLLBACK_PARTIAL,$state->get($id)['state']);
        if($kind==='child')$this->assertTrue(isset($GLOBALS['bml_test_terms']['bml_area'][3]));
        if($kind==='external')$this->assertSame([2],wp_get_post_terms(8,'bml_area',['fields'=>'ids']));
    }
    public static function areaBlockers(): array {return [['modified'],['child'],['external']];}
    public function testInvalidLocksPreventInitialAndResumeMutation(): void
    {
        [$service,$journal,$id,$state,$lock]=$this->fixture();
        $this->forward($journal,$id,'WRITE_CITY_PROVENANCE',['city_term_id'=>1,'area_term_id'=>2],['term_id'=>1,'written_value'=>2,'written_by_run'=>true]);
        $lock->release($id);
        $this->assertSame('LOCK_NOT_OWNED',$service->executeRollback($id)['code']);
        $this->assertSame(AreaMigrationStateStore::COMPLETED,$state->get($id)['state']);
        $this->assertSame(0,(int)($GLOBALS['bml_test_delete_term_meta_calls']??0));
        $lock->acquire($id);
        $state->transition($id,AreaMigrationStateStore::ROLLBACK_RUNNING);
        $state->transition($id,AreaMigrationStateStore::ROLLBACK_PARTIAL);
        $lock->release($id);
        $this->assertSame('PARTIAL_ROLLBACK_RESUME_NOT_ALLOWED',$service->resumePartialRollback($id)['code']);
        $this->assertSame(AreaMigrationStateStore::ROLLBACK_PARTIAL,$state->get($id)['state']);
        $this->assertSame(0,(int)($GLOBALS['bml_test_delete_term_meta_calls']??0));
    }
    private function fixture(?callable $injector=null): array
    {
        $state=new AreaMigrationStateStore();$store=new MigrationSnapshotStore($this->dir.'/snap');$lock=new AreaMigrationLock();$journal=new AreaMigrationJournal($this->dir.'/journal');
        $snapshot=['schema_version'=>2,'migration'=>'bml_city_to_area_v1','created_at'=>'x','created_by_user_id'=>1,'ownership'=>['site_url'=>'x','plugin_version'=>'x','wp_version'=>'x','php_version'=>'x','created_at'=>'x','created_by_user_id'=>1],'taxonomies'=>['bml_city','bml_area'],'terms'=>['bml_city'=>[['id'=>1,'name'=>'Minsk','slug'=>'minsk','parent'=>0]],'bml_area'=>[]],'locations'=>[['location_id'=>7,'city_ids'=>[1],'area_ids'=>[]]],'plan'=>['counts'=>[],'city_decisions'=>[],'location_decisions'=>[],'collision_list'=>[],'ambiguous_list'=>[],'decision_required_list'=>[]]];
        $path=$store->write($snapshot);$run=$state->create();foreach([AreaMigrationStateStore::INSPECTED,AreaMigrationStateStore::SNAPSHOTTED,AreaMigrationStateStore::SIMULATED,AreaMigrationStateStore::READY,AreaMigrationStateStore::RUNNING_TERMS,AreaMigrationStateStore::RUNNING_RELATIONSHIPS,AreaMigrationStateStore::COMPLETED]as $next)$run=$state->transition($run['run_id'],$next,$next===AreaMigrationStateStore::SNAPSHOTTED?['snapshot_path'=>$path]:[]);$lock->acquire($run['run_id']);
        return [new AreaRollbackService($store,$state,$lock,$journal,new AreaMigrationRevalidator(),$injector),$journal,$run['run_id'],$state,$lock];
    }
    private function forward(AreaMigrationJournal $journal,string $id,string $type,array $identity,array $result,array $pre=[]): void {$op=$journal->planOperation($id,2,$type,$identity,$pre);$op=$journal->beginOperation($id,$op['operation_key']);$op=$journal->markApplied($id,$op['operation_key'],$result);$op=$journal->markVerified($id,$op['operation_key'],[]);$journal->completeOperation($id,$op['operation_key']);}
}
