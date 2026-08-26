<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Csv;
use BusinessMapLocator\Import\Mapping\ImportMapper;
use RuntimeException;
final class CsvReader {
    /** @var array<int,string> */ private array $delimiters=[];
    public function __construct(private readonly int $maxRows, private readonly int $maxRecordBytes) {}
    public function open(string $path) {
        $this->assertUtf8File($path);
        $handle=fopen($path,'rb');
        if(!$handle){throw new RuntimeException('Unable to read import file.');}
        $this->delimiters[(int)$handle]=$this->detectDelimiter($path);
        return $handle;
    }
    public function readRow($handle): array|false {
        $delimiter=$this->delimiters[(int)$handle]??',';
        do {
            $start=(int)ftell($handle);
            $row=fgetcsv($handle,$this->maxRecordBytes+1,$delimiter,'"','\\');
            if($row===false){return false;}
            $end=(int)ftell($handle);
            if(($end-$start)>$this->maxRecordBytes){throw new CsvException('csv_record_too_large','A CSV record exceeds the allowed length.');}
            foreach($row as $value){if(!$this->isUtf8((string)$value)){throw new CsvException('invalid_encoding','CSV must be valid UTF-8. Convert the file to UTF-8 and try again.');}}
        } while($this->isBlank($row));
        return $row;
    }
    public function inspect(string $path, ImportMapper $mapper): array {
        $handle=$this->open($path);
        try {
            $headers=$this->readRow($handle);
            if(!is_array($headers)){throw new CsvException('csv_empty','CSV is empty.');}
            $headers=$mapper->headers($headers);
            $headerErrors=$mapper->validateHeaders($headers);
            if($headerErrors!==[]){$first=$headerErrors[0];throw new CsvException((string)$first['code'],(string)$first['message']);}
            $total=$duplicates=$errors=0; $errorDetails=[]; $seen=[]; $duplicateExternalIds=[];
            while(($row=$this->readRow($handle))!==false){
                $total++; if($total>$this->maxRows){throw new CsvException('csv_too_many_rows','CSV contains more rows than allowed.');}
                if(count($row)!==count($headers)){$errors++;$errorDetails[]=['row'=>$total+1,'column'=>null,'code'=>'invalid_column_count','message'=>'The number of columns does not match the CSV header.'];continue;}
                $mapped=$mapper->map($headers,$row);
                $externalId=sanitize_text_field((string)($mapped['external_id']??''));
                if($externalId!=='' && isset($seen['external:'.$externalId])){$duplicates++;$errors++;$duplicateExternalIds[$externalId]=true;$errorDetails[]=['row'=>$total+1,'column'=>'external_id','code'=>'duplicate_external_id_in_file','message'=>'The external_id is repeated in this CSV file.'];}
                $key=$mapper->duplicateKey($mapped); if($key!=='' && isset($seen[$key]) && !str_starts_with($key,'external:')){$duplicates++;}
                if($key!==''){$seen[$key]=true;}
            }
        } finally { unset($this->delimiters[(int)$handle]); fclose($handle); }
        return ['headers'=>$headers,'delimiter'=>$this->detectDelimiter($path),'total'=>$total,'duplicates'=>$duplicates,'errors'=>$errors,'errorDetails'=>array_slice($errorDetails,0,100),'duplicateExternalIds'=>array_keys($duplicateExternalIds)];
    }
    public function dataStartPosition(string $path): int {$h=$this->open($path);try{$this->readRow($h);return (int)ftell($h);}finally{unset($this->delimiters[(int)$h]);fclose($h);}}
    private function detectDelimiter(string $path): string {
        $h=fopen($path,'rb'); if(!$h){throw new RuntimeException('Unable to read import file.');}
        $line=''; while(!feof($h) && trim($line)===''){$line=(string)fgets($h,65536);} fclose($h);
        $line=preg_replace('/^\xEF\xBB\xBF/','',$line)??$line;
        $best=',';$max=0; foreach([',',';',"\t"] as $d){$parsed=str_getcsv($line,$d,'"','\\');$count=count($parsed);if($count>$max){$max=$count;$best=$d;}}
        return $best;
    }
    private function assertUtf8File(string $path): void {$h=fopen($path,'rb');if(!$h){throw new RuntimeException('Unable to read import file.');}$carry='';while(!feof($h)){$chunk=fread($h,65536);if($chunk===false){break;}$data=$carry.$chunk;if($this->isUtf8($data)){$carry='';continue;}$ok=false;for($i=1;$i<=3 && strlen($data)>$i;$i++){if($this->isUtf8(substr($data,0,-$i))){$carry=substr($data,-$i);$ok=true;break;}}if(!$ok){fclose($h);throw new CsvException('invalid_encoding','CSV must be valid UTF-8. Convert the file to UTF-8 and try again.');}}fclose($h);if($carry!==''&&!$this->isUtf8($carry)){throw new CsvException('invalid_encoding','CSV must be valid UTF-8. Convert the file to UTF-8 and try again.');}}
    private function isUtf8(string $v): bool {return function_exists('mb_check_encoding')?mb_check_encoding($v,'UTF-8'):preg_match('//u',$v)===1;}
    private function isBlank(array $row): bool {foreach($row as $v){if(trim((string)$v)!==''){return false;}}return true;}
}
