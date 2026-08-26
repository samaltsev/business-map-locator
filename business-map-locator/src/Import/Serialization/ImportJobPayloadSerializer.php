<?php
declare(strict_types=1);
namespace BusinessMapLocator\Import\Serialization;
use RuntimeException;
final class ImportJobPayloadSerializer {
    public const SCHEMA_VERSION=2;
    private const DEFAULT_MAX_BYTES=16777216;
    private const MIN_MAX_BYTES=65535;
    private const HARD_MAX_BYTES=67108864;
    public function __construct(private int $maxBytes=0) {
        if($this->maxBytes<=0){$this->maxBytes=self::DEFAULT_MAX_BYTES;}
        if(function_exists('apply_filters')){$this->maxBytes=(int)apply_filters('bml_import_max_payload_bytes',$this->maxBytes);}
        $this->maxBytes=max(self::MIN_MAX_BYTES,min(self::HARD_MAX_BYTES,$this->maxBytes));
    }
    public function encode(array $payload): string {
        $payload=$this->stripRuntimeMaps($payload);
        $envelope=['schemaVersion'=>self::SCHEMA_VERSION,'data'=>$payload];
        $json=wp_json_encode($envelope, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(!is_string($json) || json_last_error()!==JSON_ERROR_NONE){throw new RuntimeException('Unable to encode import job payload: '.json_last_error_msg());}
        if(strlen($json)>$this->maxBytes){throw new RuntimeException(sprintf('Import job payload exceeds the allowed size (%d bytes > %d bytes).',strlen($json),$this->maxBytes));}
        return $json;
    }
    public function decode(?string $json): array {
        if($json===null || trim($json)===''){return [];}
        $decoded=json_decode($json,true);
        if(json_last_error()!==JSON_ERROR_NONE || !is_array($decoded)){return ['payloadCorrupted'=>true,'retryable'=>false,'failureCode'=>'import_payload_corrupted'];}
        if(isset($decoded['schemaVersion'],$decoded['data']) && is_array($decoded['data'])) { return $this->migrate((int)$decoded['schemaVersion'],$decoded['data']); }
        return $this->migrate(1,$decoded); // beta10-beta12 legacy payload
    }
    private function migrate(int $version,array $data): array {
        $data=$this->stripRuntimeMaps($data);
        if($version<2){$data['payloadMigratedFrom']=$version;$data['errorDetails']=is_array($data['errorDetails']??null)?$data['errorDetails']:[];$data['log']=is_array($data['log']??null)?$data['log']:[];}
        return $data;
    }
    private function stripRuntimeMaps(array $data): array { foreach(['externalMap','fingerprintMap','ambiguousExternalMap','ambiguousFingerprintMap'] as $key){unset($data[$key]);} return $data; }
}
