<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Import;

use BusinessMapLocator\Admin\Shared\AdminShell;
use BusinessMapLocator\Import\Dto\ImportJob;
use BusinessMapLocator\Import\Job\ImportJobRepositoryInterface;

if (!defined('ABSPATH')) { exit; }

final class ImportPage
{
    public function __construct(private AdminShell $shell, private ImportJobRepositoryInterface $jobs) {}

    public function render(): void
    {
        $history = $this->jobs->listByOwner(get_current_user_id(), 8);
        $summary = $this->historySummary($history);

        $this->shell->start(
            __('Import / Export', 'business-map-locator'),
            __('Move location data safely with validation, resumable jobs and controlled exports.', 'business-map-locator')
        );
        ?>
        <div class="bml-transfer-page" data-bml-transfer-page>
            <nav class="bml-transfer-tabs" aria-label="<?php esc_attr_e('Import and export sections', 'business-map-locator'); ?>">
                <button type="button" class="is-active" data-transfer-tab="import"><span class="dashicons dashicons-upload"></span><?php esc_html_e('Import', 'business-map-locator'); ?></button>
                <button type="button" data-transfer-tab="history"><span class="dashicons dashicons-backup"></span><?php esc_html_e('History', 'business-map-locator'); ?></button>
                <button type="button" data-transfer-tab="export"><span class="dashicons dashicons-download"></span><?php esc_html_e('Export', 'business-map-locator'); ?></button>
                <button type="button" data-transfer-tab="tools"><span class="dashicons dashicons-admin-tools"></span><?php esc_html_e('Data tools', 'business-map-locator'); ?></button>
            </nav>

            <section class="bml-transfer-overview" aria-label="<?php esc_attr_e('Transfer overview', 'business-map-locator'); ?>">
                <article><span class="bml-transfer-overview__icon bml-transfer-overview__icon--blue dashicons dashicons-media-spreadsheet"></span><div><small><?php esc_html_e('Recent jobs', 'business-map-locator'); ?></small><strong><?php echo esc_html((string) count($history)); ?></strong><em><?php esc_html_e('Latest account activity', 'business-map-locator'); ?></em></div></article>
                <article><span class="bml-transfer-overview__icon bml-transfer-overview__icon--green dashicons dashicons-yes-alt"></span><div><small><?php esc_html_e('Completed', 'business-map-locator'); ?></small><strong><?php echo esc_html((string) $summary['complete']); ?></strong><em><?php esc_html_e('Successful recent jobs', 'business-map-locator'); ?></em></div></article>
                <article><span class="bml-transfer-overview__icon bml-transfer-overview__icon--violet dashicons dashicons-plus-alt2"></span><div><small><?php esc_html_e('Rows added', 'business-map-locator'); ?></small><strong><?php echo esc_html((string) $summary['added']); ?></strong><em><?php esc_html_e('Across recent imports', 'business-map-locator'); ?></em></div></article>
                <article><span class="bml-transfer-overview__icon bml-transfer-overview__icon--amber dashicons dashicons-warning"></span><div><small><?php esc_html_e('Errors', 'business-map-locator'); ?></small><strong><?php echo esc_html((string) $summary['errors']); ?></strong><em><?php esc_html_e('Rows needing attention', 'business-map-locator'); ?></em></div></article>
            </section>

            <section class="bml-transfer-section is-active" data-transfer-panel="import">
                <div class="bml-transfer-layout">
                    <main class="bml-transfer-main">
                        <article class="bml-panel bml-import-workspace">
                            <div class="bml-panel__head bml-transfer-panel-head">
                                <div>
                                    <span class="bml-eyebrow"><?php esc_html_e('CSV import', 'business-map-locator'); ?></span>
                                    <h2><?php esc_html_e('Import locations', 'business-map-locator'); ?></h2>
                                    <p><?php esc_html_e('Upload a UTF-8 CSV file. Every file is inspected before any location is written.', 'business-map-locator'); ?></p>
                                </div>
                                <span id="bml-import-state-badge" class="bml-job-badge bml-job-badge--idle"><?php esc_html_e('Ready', 'business-map-locator'); ?></span>
                            </div>

                            <form id="bml-import-form" class="bml-import-form" enctype="multipart/form-data">
                                <div id="bml-upload-zone" class="bml-upload bml-upload--empty" tabindex="0" role="button" aria-controls="bml-csv-file">
                                    <span class="bml-upload__icon dashicons dashicons-cloud-upload"></span>
                                    <div class="bml-upload__empty">
                                        <h3><?php esc_html_e('Drop your CSV file here', 'business-map-locator'); ?></h3>
                                        <p><?php esc_html_e('or choose a file from your computer', 'business-map-locator'); ?></p>
                                        <label class="bml-btn bml-btn--primary" for="bml-csv-file"><span class="dashicons dashicons-open-folder"></span><?php esc_html_e('Choose CSV file', 'business-map-locator'); ?></label>
                                        <small><?php esc_html_e('CSV only · UTF-8 recommended · comma, semicolon or tab delimiter', 'business-map-locator'); ?></small>
                                    </div>
                                    <input id="bml-csv-file" type="file" name="csv" accept=".csv,text/csv,text/plain" required>
                                </div>

                                <section id="bml-selected-file" class="bml-selected-file" hidden aria-live="polite">
                                    <div class="bml-selected-file__main"><span class="bml-selected-file__icon dashicons dashicons-media-spreadsheet"></span><div><strong data-file="name"><?php esc_html_e('CSV file', 'business-map-locator'); ?></strong><span data-file="meta"></span></div></div>
                                    <div class="bml-file-facts">
                                        <div><small><?php esc_html_e('Size', 'business-map-locator'); ?></small><strong data-file="size">—</strong></div>
                                        <div><small><?php esc_html_e('Encoding', 'business-map-locator'); ?></small><strong data-file="encoding"><?php esc_html_e('Checking…', 'business-map-locator'); ?></strong></div>
                                        <div><small><?php esc_html_e('Delimiter', 'business-map-locator'); ?></small><strong data-file="delimiter"><?php esc_html_e('Checking…', 'business-map-locator'); ?></strong></div>
                                        <div><small><?php esc_html_e('Rows', 'business-map-locator'); ?></small><strong data-file="rows">—</strong></div>
                                        <div><small><?php esc_html_e('Columns', 'business-map-locator'); ?></small><strong data-file="columns">—</strong></div>
                                    </div>
                                    <button id="bml-replace-file" class="bml-btn bml-btn--secondary bml-btn--small" type="button"><?php esc_html_e('Replace', 'business-map-locator'); ?></button>
                                </section>

                                <div class="bml-import-options">
                                    <div><span class="bml-import-options__label"><?php esc_html_e('Import mode', 'business-map-locator'); ?></span><p><?php esc_html_e('Run a safe simulation first or write changes immediately.', 'business-map-locator'); ?></p></div>
                                    <div class="bml-mode-choice" role="radiogroup" aria-label="<?php esc_attr_e('Import mode', 'business-map-locator'); ?>">
                                        <label class="bml-mode-card is-active"><input type="radio" name="import_mode" value="real" checked><span class="dashicons dashicons-database-import"></span><span><strong><?php esc_html_e('Real import', 'business-map-locator'); ?></strong><small><?php esc_html_e('Create and update locations', 'business-map-locator'); ?></small></span></label>
                                        <label class="bml-mode-card"><input type="radio" name="import_mode" value="dry"><span class="dashicons dashicons-search"></span><span><strong><?php esc_html_e('Dry run', 'business-map-locator'); ?></strong><small><?php esc_html_e('Validate without writing data', 'business-map-locator'); ?></small></span></label>
                                    </div>
                                </div>

                                <div class="bml-import-submit-row"><p id="bml-import-help-text"><?php esc_html_e('Select a file to enable validation and import.', 'business-map-locator'); ?></p><button id="bml-import-submit" class="bml-btn bml-btn--primary" type="submit" disabled><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Validate and import', 'business-map-locator'); ?></button></div>
                            </form>

                            <?php $this->renderProgress(); ?>
                        </article>
                    </main>

                    <aside class="bml-transfer-side">
                        <article class="bml-panel bml-transfer-guide">
                            <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Before you import', 'business-map-locator'); ?></span><h3><?php esc_html_e('CSV checklist', 'business-map-locator'); ?></h3></div></div>
                            <ol>
                                <li><span>1</span><div><strong><?php esc_html_e('Use the correct columns', 'business-map-locator'); ?></strong><small><?php esc_html_e('Start from one of the templates below.', 'business-map-locator'); ?></small></div></li>
                                <li><span>2</span><div><strong><?php esc_html_e('Keep external IDs stable', 'business-map-locator'); ?></strong><small><?php esc_html_e('This allows safe updates without duplicates.', 'business-map-locator'); ?></small></div></li>
                                <li><span>3</span><div><strong><?php esc_html_e('Run a dry test', 'business-map-locator'); ?></strong><small><?php esc_html_e('Review the forecast before writing data.', 'business-map-locator'); ?></small></div></li>
                            </ol>
                        </article>

                        <article class="bml-panel bml-template-panel">
                            <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Resources', 'business-map-locator'); ?></span><h3><?php esc_html_e('CSV templates', 'business-map-locator'); ?></h3></div></div>
                            <a href="<?php echo esc_url(BML_URL . 'assets/samples/locations-basic.csv'); ?>" download><span class="dashicons dashicons-media-spreadsheet"></span><span><strong><?php esc_html_e('Basic template', 'business-map-locator'); ?></strong><small><?php esc_html_e('Essential location fields', 'business-map-locator'); ?></small></span><span class="dashicons dashicons-download"></span></a>
                            <a href="<?php echo esc_url(BML_URL . 'assets/samples/locations-full.csv'); ?>" download><span class="dashicons dashicons-store"></span><span><strong><?php esc_html_e('Full template', 'business-map-locator'); ?></strong><small><?php esc_html_e('All supported columns', 'business-map-locator'); ?></small></span><span class="dashicons dashicons-download"></span></a>
                            <a href="<?php echo esc_url(BML_URL . 'docs/csv-import-reference.html'); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-book-alt"></span><span><strong><?php esc_html_e('Field reference', 'business-map-locator'); ?></strong><small><?php esc_html_e('Accepted values and rules', 'business-map-locator'); ?></small></span><span class="dashicons dashicons-external"></span></a>
                        </article>
                    </aside>
                </div>
            </section>

            <section class="bml-transfer-section" data-transfer-panel="history" hidden>
                <article class="bml-panel bml-transfer-history-panel">
                    <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Activity', 'business-map-locator'); ?></span><h2><?php esc_html_e('Import history', 'business-map-locator'); ?></h2><p><?php esc_html_e('Recent jobs created by your account.', 'business-map-locator'); ?></p></div></div>
                    <?php if ($history !== []): ?><div class="bml-import-history"><?php foreach ($history as $job): $this->historyRow($job); endforeach; ?></div><?php else: ?><div class="bml-transfer-empty"><span class="dashicons dashicons-backup"></span><h3><?php esc_html_e('No imports yet', 'business-map-locator'); ?></h3><p><?php esc_html_e('Completed and interrupted jobs will appear here.', 'business-map-locator'); ?></p></div><?php endif; ?>
                </article>
            </section>

            <section class="bml-transfer-section" data-transfer-panel="export" hidden>
                <div class="bml-transfer-layout bml-transfer-layout--export">
                    <main class="bml-transfer-main">
                        <article class="bml-panel bml-export-workspace">
                            <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('CSV export', 'business-map-locator'); ?></span><h2><?php esc_html_e('Download locations', 'business-map-locator'); ?></h2><p><?php esc_html_e('Filter the dataset and choose which fields to include.', 'business-map-locator'); ?></p></div><span class="bml-job-badge bml-job-badge--idle"><?php esc_html_e('CSV', 'business-map-locator'); ?></span></div>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bml-export-form" data-bml-export-form>
                                <input type="hidden" name="action" value="bml_export_csv"><?php wp_nonce_field('bml_export_csv'); ?>
                                <div class="bml-export-grid">
                                    <label><span><?php esc_html_e('Search locations', 'business-map-locator'); ?></span><input type="search" name="s" placeholder="<?php esc_attr_e('Name or address', 'business-map-locator'); ?>"></label>
                                    <label><span><?php esc_html_e('Status', 'business-map-locator'); ?></span><select name="status"><option value=""><?php esc_html_e('All statuses', 'business-map-locator'); ?></option><option value="publish"><?php esc_html_e('Published', 'business-map-locator'); ?></option><option value="draft"><?php esc_html_e('Draft', 'business-map-locator'); ?></option><option value="private"><?php esc_html_e('Private', 'business-map-locator'); ?></option></select></label>
                                    <label><span><?php esc_html_e('City slug', 'business-map-locator'); ?></span><input type="text" name="city" placeholder="minsk"></label>
                                    <label><span><?php esc_html_e('Category slug', 'business-map-locator'); ?></span><input type="text" name="category" placeholder="pharmacy"></label>
                                </div>
                                <fieldset class="bml-export-fields"><legend><?php esc_html_e('Fields', 'business-map-locator'); ?></legend><div class="bml-export-presets"><button type="button" class="is-active" data-export-preset="full"><?php esc_html_e('Full', 'business-map-locator'); ?></button><button type="button" data-export-preset="basic"><?php esc_html_e('Basic', 'business-map-locator'); ?></button><button type="button" data-export-preset="contact"><?php esc_html_e('Contact', 'business-map-locator'); ?></button></div><input type="text" name="fields" value="external_id,title,address,city,category,region,country,postcode,lat,lng,phone,email,website,hours,status,operational_status,visible" aria-label="<?php esc_attr_e('Export fields', 'business-map-locator'); ?>"></fieldset>
                                <label class="bml-export-check"><input type="checkbox" name="bom" value="1" checked><span><strong><?php esc_html_e('Excel compatibility', 'business-map-locator'); ?></strong><small><?php esc_html_e('Add UTF-8 BOM to preserve non-Latin characters.', 'business-map-locator'); ?></small></span></label>
                                <div class="bml-export-submit"><p><?php esc_html_e('The export is generated as a streamed CSV download.', 'business-map-locator'); ?></p><button class="bml-btn bml-btn--primary" type="submit"><span class="dashicons dashicons-download"></span><?php esc_html_e('Export CSV', 'business-map-locator'); ?></button></div>
                            </form>
                        </article>
                    </main>
                    <aside class="bml-transfer-side"><article class="bml-panel bml-transfer-guide"><div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Export tips', 'business-map-locator'); ?></span><h3><?php esc_html_e('Clean, portable data', 'business-map-locator'); ?></h3></div></div><ul class="bml-transfer-tip-list"><li><span class="dashicons dashicons-shield-alt"></span><?php esc_html_e('Spreadsheet formulas are escaped automatically.', 'business-map-locator'); ?></li><li><span class="dashicons dashicons-filter"></span><?php esc_html_e('Filters apply before the file is streamed.', 'business-map-locator'); ?></li><li><span class="dashicons dashicons-database-export"></span><?php esc_html_e('Large exports are paginated to reduce memory use.', 'business-map-locator'); ?></li></ul></article></aside>
                </div>
            </section>

            <section class="bml-transfer-section" data-transfer-panel="tools" hidden>
                <article class="bml-panel bml-data-tool-card">
                    <div class="bml-data-tool-card__icon"><span class="dashicons dashicons-admin-page"></span></div>
                    <div class="bml-data-tool-card__content"><span class="bml-eyebrow"><?php esc_html_e('Database maintenance', 'business-map-locator'); ?></span><h2><?php esc_html_e('Find duplicate locations', 'business-map-locator'); ?></h2><p><?php esc_html_e('Compare titles, addresses and coordinates. Nothing is deleted until you explicitly confirm the cleanup.', 'business-map-locator'); ?></p><div class="bml-actions"><button id="bml-check-duplicates" class="bml-btn bml-btn--secondary" type="button"><span class="dashicons dashicons-search"></span><?php esc_html_e('Scan for duplicates', 'business-map-locator'); ?></button><button id="bml-delete-duplicates" class="bml-btn bml-btn--danger" type="button" hidden><?php esc_html_e('Delete extra duplicates', 'business-map-locator'); ?></button></div><div id="bml-duplicate-results"></div></div>
                </article>
            </section>
        </div>
        <?php
        $this->shell->end();
    }

    private function renderProgress(): void
    {
        ?>
        <div id="bml-import-progress" class="bml-import-progress bml-job-card" hidden>
            <div class="bml-job-card__head"><div><span class="bml-eyebrow"><?php esc_html_e('Current job', 'business-map-locator'); ?></span><h3 data-job="title"><?php esc_html_e('Preparing import', 'business-map-locator'); ?></h3><p class="bml-import-progress__status"></p></div><strong class="bml-job-percent" data-job="percent">0%</strong></div>
            <div class="bml-import-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span style="width:0%"></span></div>
            <div class="bml-job-metrics"><div><small><?php esc_html_e('Processed', 'business-map-locator'); ?></small><strong><span data-job="processed">0</span> / <span data-job="total">0</span></strong></div><div><small><?php esc_html_e('Remaining', 'business-map-locator'); ?></small><strong data-job="remaining">0</strong></div><div><small><?php esc_html_e('Current row', 'business-map-locator'); ?></small><strong data-job="current-row">—</strong></div><div><small><?php esc_html_e('Elapsed', 'business-map-locator'); ?></small><strong data-job="elapsed">00:00</strong></div><div><small><?php esc_html_e('Estimated time', 'business-map-locator'); ?></small><strong data-job="eta">—</strong></div></div>
            <div class="bml-actions bml-import-actions"><button id="bml-import-pause" class="bml-btn bml-btn--secondary bml-btn--small" type="button" hidden><?php esc_html_e('Pause', 'business-map-locator'); ?></button><button id="bml-import-cancel" class="bml-btn bml-btn--danger bml-btn--small" type="button" hidden><?php esc_html_e('Cancel', 'business-map-locator'); ?></button><button id="bml-import-retry" class="bml-btn bml-btn--secondary bml-btn--small" type="button" hidden><?php esc_html_e('Retry', 'business-map-locator'); ?></button><button id="bml-import-resume" class="bml-btn bml-btn--primary bml-btn--small" type="button" hidden><?php esc_html_e('Resume', 'business-map-locator'); ?></button><button id="bml-import-new" class="bml-btn bml-btn--primary bml-btn--small" type="button" hidden><?php esc_html_e('Import another CSV', 'business-map-locator'); ?></button></div>
            <div class="bml-import-stats bml-import-stats--visual"><?php $this->stat('processed', 'dashicons-media-spreadsheet', __('Processed', 'business-map-locator')); ?><?php $this->stat('added', 'dashicons-plus-alt2', __('Added', 'business-map-locator')); ?><?php $this->stat('updated', 'dashicons-update', __('Updated', 'business-map-locator')); ?><?php $this->stat('skipped', 'dashicons-controls-skipforward', __('Skipped', 'business-map-locator')); ?><?php $this->stat('duplicates', 'dashicons-warning', __('Duplicates', 'business-map-locator')); ?><?php $this->stat('errors', 'dashicons-dismiss', __('Errors', 'business-map-locator')); ?></div>
            <div class="bml-dry-run-stats" hidden><h4><?php esc_html_e('Dry-run forecast', 'business-map-locator'); ?></h4><div class="bml-import-stats"><?php foreach (['wouldCreate' => 'Would create', 'wouldUpdate' => 'Would update', 'wouldSkip' => 'Would skip', 'wouldFail' => 'Would fail'] as $key => $label): ?><div><small><?php echo esc_html__($label, 'business-map-locator'); ?></small><strong data-stat="<?php echo esc_attr($key); ?>">0</strong></div><?php endforeach; ?></div></div>
            <div class="bml-import-error-summary" hidden><strong data-error="count">0</strong> <?php esc_html_e('rows failed.', 'business-map-locator'); ?> <button class="bml-link-button" type="button" data-toggle-log><?php esc_html_e('View details', 'business-map-locator'); ?></button></div><pre class="bml-import-log" hidden></pre>
        </div>
        <?php
    }

    private function stat(string $key, string $icon, string $label): void
    {
        echo '<div class="bml-stat bml-stat--' . esc_attr($key) . '"><span class="dashicons ' . esc_attr($icon) . '"></span><div><small>' . esc_html($label) . '</small><strong data-stat="' . esc_attr($key) . '">0</strong></div></div>';
    }

    /** @param ImportJob[] $history */
    private function historySummary(array $history): array
    {
        $summary = ['complete' => 0, 'added' => 0, 'errors' => 0];
        foreach ($history as $job) {
            $data = $job->toArray();
            if (($data['status'] ?? '') === 'complete') { $summary['complete']++; }
            $summary['added'] += (int) ($data['added'] ?? 0);
            $summary['errors'] += (int) ($data['processingErrors'] ?? 0) + (int) ($data['inspectionErrors'] ?? 0);
        }
        return $summary;
    }

    private function historyRow(ImportJob $job): void
    {
        $data = $job->toArray();
        $status = sanitize_key((string) ($data['status'] ?? 'unknown'));
        $file = (string) ($data['originalFileName'] ?? __('CSV import', 'business-map-locator'));
        $date = (string) ($data['completedAt'] ?? $data['updatedAt'] ?? $data['startedAt'] ?? '');
        $summary = sprintf(__('%1$d added, %2$d updated, %3$d errors', 'business-map-locator'), (int) ($data['added'] ?? 0), (int) ($data['updated'] ?? 0), (int) ($data['processingErrors'] ?? 0) + (int) ($data['inspectionErrors'] ?? 0));
        ?>
        <div class="bml-history-row"><span class="bml-history-row__status bml-history-row__status--<?php echo esc_attr($status); ?>"><span class="dashicons <?php echo $status === 'complete' ? 'dashicons-yes-alt' : ($status === 'failed' ? 'dashicons-warning' : 'dashicons-clock'); ?>"></span></span><div><strong><?php echo esc_html($file); ?></strong><small><?php echo esc_html($summary); ?><?php echo !empty($data['dryRun']) ? ' · ' . esc_html__('Dry run', 'business-map-locator') : ''; ?></small></div><span class="bml-job-badge bml-job-badge--<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span><time><?php echo esc_html($date !== '' ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $date) : '—'); ?></time></div>
        <?php
    }
}
