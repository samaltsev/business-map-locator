<?php
declare(strict_types=1);
namespace BusinessMapLocator\Export;
final class CsvFormulaEscaper {
    public function escape(mixed $value): string {
        $v=(string)$value;
        if ($v !== '' && is_numeric(trim($v))) { return $v; }
        return preg_match('/^[\x00-\x20]*[=+\-@]/u',$v)===1 ? "'".$v : $v;
    }
}
