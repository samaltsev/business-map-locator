<?php
declare(strict_types=1);
namespace BusinessMapLocator\Infrastructure\Database\Migration;
final class SchemaMigrator {
    public const CURRENT_VERSION=4;
    public const OPTION='bml_schema_version';
    public function migrate(): bool {
        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $current=(int)get_option(self::OPTION,0);
        $steps=[1=>static fn()=>dbDelta(\BML_Schema::locations_index_sql()),2=>static fn()=>dbDelta(\BML_Schema::import_jobs_sql()),3=>static fn()=>dbDelta(\BML_Schema::import_job_rows_sql()),4=>static fn()=>dbDelta(\BML_Schema::import_job_events_sql())];
        foreach($steps as $version=>$step){if($current<$version){$step();update_option(self::OPTION,$version,false);$current=$version;}}
        return $current===self::CURRENT_VERSION;
    }
}
