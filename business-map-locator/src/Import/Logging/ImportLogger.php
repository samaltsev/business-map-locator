<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Logging;
use BusinessMapLocator\Import\Dto\ImportJob;
use BusinessMapLocator\Import\Event\ImportJobEventRepository;
final class ImportLogger {
    public function __construct(private ?ImportJobEventRepository $events=null){$this->events??=new ImportJobEventRepository();}
    public function info(ImportJob $job,string $message): void { $this->append($job,'info','import_info',$message); }
    public function error(ImportJob $job,array $error): void { $row=isset($error['row'])?(int)$error['row']:null;$message=(string)($error['message']??'Import error.');$code=sanitize_key((string)($error['code']??'import_error'));$this->append($job,'error',$code,$message,$row,$error); }
    private function append(ImportJob $job,string $level,string $code,string $message,?int $row=null,array $context=[]): void { $jobId=(int)($job['id']??0);if($jobId>0){$this->events->append($jobId,$level,$code,$message,$row,$context);} if($level==='error'){$job['lastErrorCode']=$code;$job['lastErrorMessage']=$message;} }
}
