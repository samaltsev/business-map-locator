<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin;
use BusinessMapLocator\Admin\Menu\AdminMenu;
use BusinessMapLocator\Admin\Assets\AdminAssets;
use BusinessMapLocator\Admin\Ajax\ImportAjaxController;
use BusinessMapLocator\Admin\Ajax\LocationEditorAjaxController;
use BusinessMapLocator\Admin\Location\Action\SaveLocationAction;
use BusinessMapLocator\Admin\Location\Action\DeleteLocationAction;
use BusinessMapLocator\Admin\Location\Action\DuplicateLocationAction;
use BusinessMapLocator\Admin\Location\Action\BulkLocationsAction;
use BusinessMapLocator\Admin\Taxonomy\Action\SaveTermAction;
use BusinessMapLocator\Admin\Taxonomy\Action\DeleteTermAction;
use BusinessMapLocator\Admin\Taxonomy\Action\DuplicateTermAction;
use BusinessMapLocator\Admin\Settings\Action\SaveSettingsAction;
use BusinessMapLocator\Admin\Demo\InstallDemoAction;
use BusinessMapLocator\Admin\Export\ExportCsvAction;
use BusinessMapLocator\Admin\Notice\AdminNotices;
if (!defined('ABSPATH')) { exit; }
final class AdminModule
{
    public function __construct(
        private AdminMenu $menu,
        private AdminAssets $assets,
        private SaveLocationAction $saveLocation,
        private DeleteLocationAction $deleteLocation,
        private DuplicateLocationAction $duplicateLocation,
        private BulkLocationsAction $bulkLocations,
        private SaveTermAction $saveTerm,
        private DeleteTermAction $deleteTerm,
        private DuplicateTermAction $duplicateTerm,
        private SaveSettingsAction $saveSettings,
        private InstallDemoAction $installDemo,
        private ExportCsvAction $export,
        private ImportAjaxController $importAjax,
        private LocationEditorAjaxController $locationEditorAjax,
        private AdminNotices $notices
    ) {}
    public function hooks(): void
    {
        add_action('admin_menu',[$this->menu,'register']);
        add_action('admin_enqueue_scripts',[$this->assets,'enqueue']);
        add_action('admin_post_bml_save_location_custom',[$this->saveLocation,'handle']);
        add_action('admin_post_bml_delete_location',[$this->deleteLocation,'handle']);
        add_action('admin_post_bml_duplicate_location',[$this->duplicateLocation,'handle']);
        add_action('admin_post_bml_bulk_locations',[$this->bulkLocations,'handle']);
        add_action('admin_post_bml_save_term',[$this->saveTerm,'handle']);
        add_action('admin_post_bml_delete_term',[$this->deleteTerm,'handle']);
        add_action('admin_post_bml_duplicate_term',[$this->duplicateTerm,'handle']);
        add_action('admin_post_bml_save_settings',[$this->saveSettings,'handle']);
        add_action('admin_post_bml_install_demo',[$this->installDemo,'handle']);
        add_action('admin_post_bml_export_csv',[$this->export,'handle']);
        add_action('wp_ajax_bml_prepare_import',[$this->importAjax,'prepareImport']);
        add_action('wp_ajax_bml_process_import',[$this->importAjax,'processImport']);
        add_action('wp_ajax_bml_pause_import',[$this->importAjax,'pauseImport']);
        add_action('wp_ajax_bml_cancel_import',[$this->importAjax,'cancelImport']);
        add_action('wp_ajax_bml_resume_import',[$this->importAjax,'resumeImport']);
        add_action('wp_ajax_bml_scan_duplicates',[$this->importAjax,'scanDuplicates']);
        add_action('wp_ajax_bml_delete_duplicates',[$this->importAjax,'deleteDuplicates']);
        add_action('wp_ajax_bml_create_inline_term',[$this->locationEditorAjax,'createTerm']);
        add_action('wp_ajax_bml_autosave_location',[$this->locationEditorAjax,'autoSave']);
        add_action('admin_notices',[$this->notices,'general']);
        add_action('admin_notices',[$this->notices,'diagnostics']);
    }
}
