<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Migration\Action;
use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\Migration\AreaMigrationService;
use BusinessMapLocator\WordPress\Capabilities;
if (!defined('ABSPATH')) { exit; }
final class MigrationPlanningAction
{
    public function __construct(private AreaMigrationService $migration, private AdminActionResponder $response, private AdminRequest $request) {}
    public function handle(): void
    {
        if (!current_user_can(Capabilities::MANAGE_SETTINGS)) $this->response->error(__('You are not allowed to manage migrations.', 'business-map-locator'), 403);
        $operation = $this->request->postString('operation'); if (!in_array($operation, ['inspect', 'snapshot', 'simulate'], true)) $this->response->error(__('Invalid migration action.', 'business-map-locator'));
        check_admin_referer('bml_migration_planning_' . $operation);
        try { $run = match ($operation) {'inspect' => $this->migration->startInspectionRun(), 'snapshot' => $this->migration->snapshotRun($this->request->postString('run_id')), 'simulate' => $this->migration->simulateRun($this->request->postString('run_id'))}; }
        catch (\LogicException $error) { $this->response->error($error->getMessage()); }
        $this->response->redirect('bml-migration-control', __('Migration planning action completed.', 'business-map-locator'), ['run_id' => $run['run_id']]);
    }
}
