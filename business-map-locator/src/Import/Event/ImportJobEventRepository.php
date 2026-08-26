<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Event;
final class ImportJobEventRepository {
    private string $table;
    public function __construct(?string $table=null){global $wpdb;$this->table=$table??$wpdb->prefix.'bml_import_job_events';}
    public function append(int $jobId,string $level,string $code,string $message,?int $rowNumber=null,array $context=[]): void { global $wpdb; $message=mb_substr($message,0,1000); $contextJson=wp_json_encode($context); if(!is_string($contextJson)||strlen($contextJson)>4096){$contextJson='{}';} $wpdb->insert($this->table,['job_id'=>$jobId,'level'=>substr(sanitize_key($level),0,20),'event_code'=>substr(sanitize_key($code),0,64),'source_row_number'=>$rowNumber,'message'=>$message,'context_json'=>$contextJson,'created_at'=>current_time('mysql',true)],['%d','%s','%s','%d','%s','%s','%s']); }
    public function deleteByJobId(int $jobId): void { global $wpdb;$wpdb->delete($this->table,['job_id'=>$jobId],['%d']); }
}
