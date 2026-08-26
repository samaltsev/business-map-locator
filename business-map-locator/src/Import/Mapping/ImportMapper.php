<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Mapping;
final class ImportMapper {
    public const ALLOWED_COLUMNS=['external_id','title','name','address','city','category','region','country','postcode','lat','lng','phone','email','website','hours','status','visible'];
    public function headers(array $headers): array {return array_map(static function(mixed $value):string{$clean=preg_replace('/^\xEF\xBB\xBF/','',(string)$value);$clean=strtolower(trim((string)$clean));$clean=preg_replace('/[^a-z0-9]+/','_',(string)$clean);return trim(sanitize_key((string)$clean),'_');},$headers);}
    public function validateHeaders(array $headers): array {
        $errors=[];$counts=array_count_values($headers);foreach($counts as $header=>$count){if($header===''||$count>1){$errors[]=['code'=>'duplicate_headers','message'=>'CSV contains empty or duplicate headers.'];break;}}
        if(!in_array('title',$headers,true)&&!in_array('name',$headers,true)){$errors[]=['code'=>'required_column_missing','message'=>'Required column title (or name) is missing.'];}
        foreach(['lat','lng'] as $required){if(!in_array($required,$headers,true)){$errors[]=['code'=>'required_column_missing','message'=>'Required column '.$required.' is missing.'];}}
        $unknown=array_values(array_diff($headers,self::ALLOWED_COLUMNS));$policy=(string)apply_filters('bml_import_unknown_columns_policy','reject',$unknown);
        if($unknown!==[] && $policy!=='ignore'){$errors[]=['code'=>'unknown_columns','message'=>'Unknown CSV columns: '.implode(', ',$unknown).'.'];}
        return $errors;
    }
    public function map(array $headers,array $row): array {$data=array_combine($headers,$row);if(!is_array($data)){return [];}if((string)apply_filters('bml_import_unknown_columns_policy','reject',[])==='ignore'){$data=array_intersect_key($data,array_flip(self::ALLOWED_COLUMNS));}if(isset($data['status'])){$data['status']=$this->status($data['status']);}if(isset($data['visible'])){$data['visible']=$this->boolean($data['visible']);}return $data;}
    public function validate(array $data): array {$title=sanitize_text_field((string)($data['title']??$data['name']??''));$lat=$this->coordinate($data['lat']??null,-90,90);$lng=$this->coordinate($data['lng']??null,-180,180);if($title===''||$lat===null||$lng===null){return ['valid'=>false,'error'=>'Title or coordinates are invalid. Coordinates must use a dot as decimal separator.'];}return ['valid'=>true,'title'=>$title,'lat'=>$lat,'lng'=>$lng];}
    public function duplicateKey(array $data): string {$externalId=sanitize_text_field((string)($data['external_id']??''));if($externalId!==''){return 'external:'.$externalId;}return $this->fingerprint((string)($data['title']??$data['name']??''),(string)($data['address']??''),(string)($data['lat']??''),(string)($data['lng']??''));}
    public function fingerprint(string $title,string $address,string $lat,string $lng): string {$title=$this->normalise($title);$address=$this->normalise($address);$la=$this->coordinate($lat,-90,90);$lo=$this->coordinate($lng,-180,180);if($title===''||$la===null||$lo===null){return '';}return hash('sha256',implode('|',[$title,$address,number_format($la,6,'.',''),number_format($lo,6,'.','')]));}
    private function coordinate(mixed $value,float $min,float $max): ?float {$raw=trim((string)$value);if(!preg_match('/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)$/',$raw)){return null;}$n=(float)$raw;return $n>=$min&&$n<=$max?$n:null;}
    private function boolean(mixed $value): string {$v=strtolower(trim((string)$value));return in_array($v,['1','true','yes','y','on','visible','publish'],true)?'1':'0';}
    private function status(mixed $value): string {$v=strtolower(trim((string)$value));return in_array($v,['draft','0','false','no','inactive','disabled'],true)?'draft':'publish';}
    private function normalise(string $value): string {$value=preg_replace('/\s+/u',' ',trim($value));return function_exists('mb_strtolower')?mb_strtolower((string)$value,'UTF-8'):strtolower((string)$value);}
}
