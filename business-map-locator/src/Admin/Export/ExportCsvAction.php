<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Export;

use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\Export\LocationCsvExporter;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) { exit; }
final class ExportCsvAction
{
    public function __construct(private LocationCsvExporter $exporter, private AdminRequest $request) {}

    public function handle(): void
    {
        if (!current_user_can(Capabilities::EXPORT_LOCATIONS)) {
            wp_die(esc_html__('Insufficient permissions.', 'business-map-locator'));
        }
        check_admin_referer('bml_export_csv');
        $this->exporter->stream($this->request->exportFilters());
    }
}
