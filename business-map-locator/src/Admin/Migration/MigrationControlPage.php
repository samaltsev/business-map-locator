<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Migration;
use BusinessMapLocator\Admin\Shared\AdminShell;
use BusinessMapLocator\Migration\{AreaMigrationExecutor,AreaMigrationService,AreaMigrationStateStore,AreaRollbackService};
if (!defined('ABSPATH')) { exit; }
final class MigrationControlPage
{
    public function __construct(private AdminShell $shell, private AreaMigrationService $migration, private AreaMigrationStateStore $state, private AreaMigrationExecutor $executor, private AreaRollbackService $rollback) {}
    public function render(): void
    {
        $runId = isset($_GET['run_id']) && !is_array($_GET['run_id']) ? sanitize_text_field(wp_unslash($_GET['run_id'])) : '';
        $run = $runId === '' ? null : $this->state->get($runId); $inspection = $run['inspection'] ?? $this->migration->inspect();
        $execution = $run === null ? null : $this->executor->inspectEligibility($runId); $rollback = $run === null ? null : $this->rollback->inspectEligibility($runId);
        $this->shell->start(__('Migration control', 'business-map-locator'), __('Plan and review the City to Area migration. No migration or rollback runs automatically.', 'business-map-locator'));
        if (isset($_GET['bml_notice'])) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sanitize_text_field(wp_unslash($_GET['bml_notice']))) . '</p></div>';
        ?>
        <section class="bml-panel"><div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Read-only diagnostics', 'business-map-locator'); ?></span><h2><?php esc_html_e('Current migration context', 'business-map-locator'); ?></h2></div></div>
            <p><?php echo esc_html(sprintf(__('Locations: %d · Cities: %d · Areas: %d', 'business-map-locator'), (int) ($inspection['location_count'] ?? 0), (int) ($inspection['city_terms_count'] ?? 0), (int) ($inspection['area_terms_count'] ?? 0))); ?></p>
            <p><?php echo !empty($inspection['bml_city_exists']) && !empty($inspection['bml_area_exists']) ? esc_html__('Required taxonomies are available.', 'business-map-locator') : esc_html__('Required taxonomies are unavailable.', 'business-map-locator'); ?></p>
        </section>
        <section class="bml-panel"><div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Planning run', 'business-map-locator'); ?></span><h2><?php echo esc_html($run === null ? __('No run selected', 'business-map-locator') : sprintf(__('Run %s', 'business-map-locator'), $runId)); ?></h2></div></div>
            <?php if ($run !== null) : ?><p><?php echo esc_html(sprintf(__('State: %s', 'business-map-locator'), (string) ($run['state'] ?? 'UNKNOWN'))); ?></p><p><?php echo esc_html(sprintf(__('Snapshot: %s', 'business-map-locator'), !empty($run['snapshot_path']) ? __('available', 'business-map-locator') : __('not created', 'business-map-locator'))); ?></p><?php if (!empty($run['planning_blockers'])) : ?><p><?php echo esc_html(sprintf(__('Planning blockers: %s', 'business-map-locator'), implode(', ', array_map(static fn (array $blocker): string => (string) ($blocker['code'] ?? 'UNKNOWN'), (array) $run['planning_blockers'])))); ?></p><?php endif; ?><?php endif; ?>
            <div class="bml-topbar-actions">
                <?php $this->form('inspect', __('Inspect', 'business-map-locator'), $runId, $run === null); ?>
                <?php $this->form('snapshot', __('Create snapshot', 'business-map-locator'), $runId, $run !== null && ($run['state'] ?? '') === AreaMigrationStateStore::INSPECTED); ?>
                <?php $this->form('simulate', __('Simulate', 'business-map-locator'), $runId, $run !== null && ($run['state'] ?? '') === AreaMigrationStateStore::SNAPSHOTTED); ?>
            </div>
        </section>
        <?php if ($run !== null) : ?><section class="bml-panel"><div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Protected actions', 'business-map-locator'); ?></span><h2><?php esc_html_e('Execution and rollback eligibility', 'business-map-locator'); ?></h2></div></div><p><?php echo esc_html(sprintf(__('Execute: %s (%s)', 'business-map-locator'), !empty($execution['eligible']) ? __('eligible', 'business-map-locator') : __('blocked', 'business-map-locator'), (string) ($execution['code'] ?? '—'))); ?></p><p><?php echo esc_html(sprintf(__('Rollback: %s (%s)', 'business-map-locator'), !empty($rollback['eligible']) ? __('eligible', 'business-map-locator') : __('blocked', 'business-map-locator'), (string) ($rollback['code'] ?? '—'))); ?></p><p><?php esc_html_e('Execute, resume and rollback are intentionally not available in this planning slice.', 'business-map-locator'); ?></p></section><?php endif; ?>
        <?php $this->shell->end();
    }
    private function form(string $operation, string $label, string $runId, bool $enabled): void { ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bml_migration_planning"><input type="hidden" name="operation" value="<?php echo esc_attr($operation); ?>"><input type="hidden" name="run_id" value="<?php echo esc_attr($runId); ?>"><?php wp_nonce_field('bml_migration_planning_' . $operation); ?><button class="bml-btn bml-btn--secondary" type="submit" <?php disabled(!$enabled); ?>><?php echo esc_html($label); ?></button></form>
    <?php }
}
