<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import;

use BusinessMapLocator\Import\Config\ImportLimits;
use BusinessMapLocator\Import\Csv\CsvReader;
use BusinessMapLocator\Import\Csv\CsvException;
use BusinessMapLocator\Import\Duplicate\DuplicateDetector;
use BusinessMapLocator\Import\Exception\ImportJobException;
use BusinessMapLocator\Import\Dto\ImportJob;
use BusinessMapLocator\Import\Job\ImportJobRepository;
use BusinessMapLocator\Import\Job\ImportJobRepositoryInterface;
use BusinessMapLocator\Import\Job\ImportJobRowRepository;
use BusinessMapLocator\Import\Job\SourceRowHasher;
use BusinessMapLocator\Import\Job\ImportJobStateMachine;
use BusinessMapLocator\Import\Job\ImportJobStatus;
use BusinessMapLocator\Import\Event\ImportJobEventRepository;
use BusinessMapLocator\Import\Logging\ImportLogger;
use BusinessMapLocator\Import\Mapping\ImportMapper;
use BusinessMapLocator\Import\Processing\LocationImporter;
use BusinessMapLocator\Import\Security\ImportDirectory;
use BusinessMapLocator\Import\Security\UploadValidator;
use RuntimeException;

final class ImportManager
{
    private CsvReader $reader;
    private ImportMapper $mapper;
    private LocationImporter $importer;
    private DuplicateDetector $duplicates;
    private ImportLogger $logger;
    private UploadValidator $uploadValidator;
    private ImportDirectory $importDirectory;
    private ImportJobRepositoryInterface $jobs;
    private ImportJobStateMachine $stateMachine;
    private ImportJobRowRepository $journal;
    private SourceRowHasher $rowHasher;

    public function __construct(
        ?CsvReader $reader = null,
        ?ImportMapper $mapper = null,
        ?LocationImporter $importer = null,
        ?DuplicateDetector $duplicates = null,
        ?ImportLogger $logger = null,
        ?UploadValidator $uploadValidator = null,
        ?ImportDirectory $importDirectory = null,
        ?ImportJobRepositoryInterface $jobs = null,
        ?ImportJobStateMachine $stateMachine = null,
        ?ImportJobRowRepository $journal = null,
        ?SourceRowHasher $rowHasher = null
    ) {
        $this->mapper = $mapper ?? new ImportMapper();
        $this->reader = $reader ?? new CsvReader(ImportLimits::maxRows(), ImportLimits::maxRecordBytes());
        $this->importer = $importer ?? new LocationImporter($this->mapper);
        $this->duplicates = $duplicates ?? new DuplicateDetector($this->mapper);
        $this->logger = $logger ?? new ImportLogger();
        $this->uploadValidator = $uploadValidator ?? new UploadValidator();
        $this->importDirectory = $importDirectory ?? new ImportDirectory();
        $this->jobs = $jobs ?? new ImportJobRepository();
        $this->stateMachine = $stateMachine ?? new ImportJobStateMachine();
        $this->journal = $journal ?? new ImportJobRowRepository();
        $this->rowHasher = $rowHasher ?? new SourceRowHasher();
    }

    public function scanDuplicates(): array
    {
        return $this->duplicates->scan();
    }

    public function deleteDuplicates(): array
    {
        return $this->duplicates->delete();
    }

    public function prepare(array $file, bool $dryRun = false): array
    {
        $this->uploadValidator->validate($file);
        $directory = $this->importDirectory->path();
        $token = wp_generate_uuid4();
        $path = $directory . '/import-' . sanitize_file_name($token) . '.csv';

        if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
            throw new RuntimeException('Unable to save uploaded CSV.');
        }

        @chmod($path, 0600);

        try {
            $inspection = $this->reader->inspect($path, $this->mapper);
            $ownerUserId = get_current_user_id();

            if ($ownerUserId <= 0) {
                throw new RuntimeException('Unable to identify the import owner.');
            }

            $job = new ImportJob([
                'token' => $token,
                'path' => $path,
                'ownerUserId' => $ownerUserId,
                'headers' => $inspection['headers'],
                'delimiter' => (string) ($inspection['delimiter'] ?? ','),
                'originalFileName' => sanitize_file_name((string) ($file['name'] ?? 'locations.csv')),
                'fileSize' => (int) ($file['size'] ?? 0),
                'encoding' => 'UTF-8',
                'columnCount' => count((array) ($inspection['headers'] ?? [])),
                'position' => $this->reader->dataStartPosition($path),
                'readPosition' => $this->reader->dataStartPosition($path),
                'committedPosition' => $this->reader->dataStartPosition($path),
                'total' => $inspection['total'],
                'processed' => 0,
                'readRows' => 0,
                'committedRows' => 0,
                'added' => 0,
                'updated' => 0,
                'skipped' => 0,
                'duplicates' => $inspection['duplicates'],
                'inspectionErrors' => (int) $inspection['errors'],
                'processingErrors' => 0,
                'inspectionErrorDetails' => (array) ($inspection['errorDetails'] ?? []),
                'errorDetails' => [],
                'errorMessages' => [],
                'log' => [],
                'duplicateExternalIds' => (array) ($inspection['duplicateExternalIds'] ?? []),
                'dryRun' => $dryRun,
                'wouldCreate' => 0,
                'wouldUpdate' => 0,
                'wouldSkip' => 0,
                'wouldFail' => 0,
                'cancelled' => false,
                'retryable' => false,
                'failureCode' => '',
                'status' => ImportJobStatus::PREPARED->value,
                'startedAt' => current_time('mysql'),
                'updatedAt' => current_time('mysql'),
                'expiresAt' => time() + ImportLimits::jobTtl(),
            ]);

            $job = $this->jobs->create($token, $job);

            return $this->summary($job) + ['token' => $token, 'dryRun' => $dryRun];
        } catch (\Throwable $exception) {
            $this->deleteFile($path);
            if ($exception instanceof CsvException) {
                throw new ImportJobException($exception->errorCode(), $exception->getMessage(), 422, $exception);
            }
            throw $exception;
        }
    }

    public function process(string $token): array
    {
        $token = sanitize_key($token);
        $leaseId = wp_generate_uuid4();
        $job = $this->jobs->acquireLease($token, $leaseId, $this->leaseTtl());
        if ($job === null) {
            throw ImportJobException::conflict();
        }

        try {
            $job = $this->assertOwnedJob($job);
            $status = $this->statusOf($job);
            if ($status === ImportJobStatus::PAUSED) {
                return $this->summary($job) + ['done' => false, 'cancelled' => false];
            }
            if (!in_array($status, [ImportJobStatus::PREPARED, ImportJobStatus::RUNNING, ImportJobStatus::RETRYING, ImportJobStatus::PROCESSING], true)) {
                throw ImportJobException::invalidTransition($status, ImportJobStatus::PROCESSING);
            }

            if ($status !== ImportJobStatus::PROCESSING) {
                $job = $this->transition($job, ImportJobStatus::PROCESSING);
                $job['updatedAt'] = current_time('mysql');
                $job = $this->saveJob($token, $job);
            }

            $handle = $this->reader->open((string) $job['path']);
            try {
                $committedPosition = (int) ($job['committedPosition'] ?? $job['position'] ?? 0);
                if (fseek($handle, $committedPosition) !== 0) {
                    throw new RuntimeException('Unable to resume the import file.');
                }

                $job = $this->transition($job, ImportJobStatus::RUNNING);
                $batchRows = 0;

                while ($batchRows < ImportLimits::batchSize()) {
                    $rowStart = (int) ftell($handle);
                    $row = $this->reader->readRow($handle);
                    if ($row === false) {
                        break;
                    }
                    $rowEnd = (int) ftell($handle);
                    $batchRows++;
                    $rowNumber = (int) ($job['committedRows'] ?? 0) + 1;
                    $job['readRows'] = $rowNumber;
                    $job['readPosition'] = $rowEnd;

                    $rowHash = $this->rowHasher->hash((array) $job['headers'], $row);
                    $journal = $this->journal->findCommitted((int) $job['id'], $rowNumber, $rowHash);
                    if ($journal !== null) {
                        $job = $this->applyCommittedJournal($job, $journal, $rowEnd);
                        $job = $this->saveJob($token, $job);
                        continue;
                    }

                    $journal = $this->journal->begin((int) $job['id'], $rowNumber, $rowHash);
                    if ((string) ($journal['status'] ?? '') === 'committed') {
                        $job = $this->applyCommittedJournal($job, $journal, $rowEnd);
                        $job = $this->saveJob($token, $job);
                        continue;
                    }

                    $recovered = $this->recoverJournalEntry((int) $job['id'], $rowHash);
                    if ($recovered !== null) {
                        $this->journal->commit((int) $journal['id'], $recovered['action'], $recovered['locationId']);
                        $journal['action'] = $recovered['action'];
                        $journal['location_id'] = $recovered['locationId'];
                        $journal['status'] = 'committed';
                        $job = $this->applyCommittedJournal($job, $journal, $rowEnd);
                        $job = $this->saveJob($token, $job);
                        continue;
                    }

                    $result = $this->importer->importRow($row, $job, $rowHash);
                    $job = $result['job'];
                    $action = (string) ($result['action'] ?? (!empty($result['error']) ? 'error' : 'skipped'));
                    $locationId = (int) ($result['locationId'] ?? 0);
                    $errorCode = (string) ($result['code'] ?? '');

                    if (!empty($result['error'])) {
                        $this->logger->error($job, [
                            'row' => $rowNumber + 1,
                            'column' => null,
                            'code' => $errorCode !== '' ? $errorCode : 'import_row_failed',
                            'message' => (string) $result['error'],
                        ]);
                    } elseif (!empty($result['message'])) {
                        $this->logger->info($job, 'Row ' . $rowNumber . ': ' . $result['message']);
                    }

                    $this->journal->commit((int) $journal['id'], $action, $locationId, $errorCode);
                    $job['committedRows'] = $rowNumber;
                    $job['processed'] = $rowNumber;
                    $job['committedPosition'] = $rowEnd;
                    $job['position'] = $rowEnd;
                    $job['updatedAt'] = current_time('mysql');
                    $job = $this->saveJob($token, $job);

                    do_action('bml_import_row_committed', (int) $job['id'], $rowNumber, $rowHash, $action, $locationId);
                }

                $done = feof($handle) || (int) ($job['committedRows'] ?? 0) >= (int) $job['total'];
            } finally {
                fclose($handle);
            }

            $latest = $this->jobs->find($token);
            if ($latest === null) {
                throw ImportJobException::expired();
            }
            $latest = $this->assertOwnedJob($latest);
            if ($this->statusOf($latest) === ImportJobStatus::CANCELLED) {
                return $this->summary($latest) + ['done' => true, 'cancelled' => true];
            }

            $paused = $this->statusOf($latest) === ImportJobStatus::PAUSED;
            $job['version'] = (int) $latest['version'];
            $target = $done ? ImportJobStatus::COMPLETE : ($paused ? ImportJobStatus::PAUSED : ImportJobStatus::RUNNING);
            if ($this->statusOf($job) !== $target) {
                $job = $this->transition($job, $target);
            }
            $job['retryable'] = false;
            $job['failureCode'] = '';
            $job = $done ? $this->finish($token, $job) : $this->saveJob($token, $job);

            return $this->summary($job) + ['done' => $done, 'cancelled' => false];
        } catch (\Throwable $exception) {
            error_log(sprintf(
                '[Business Map Locator] Import batch failure cause: %s: %s in %s:%d',
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
            $latest = $this->jobs->find($token);
            if ($latest instanceof ImportJob && $this->statusOf($latest) !== ImportJobStatus::CANCELLED) {
                $latestStatus = $this->statusOf($latest);
                if (in_array($latestStatus, [ImportJobStatus::PROCESSING, ImportJobStatus::RUNNING], true)) {
                    $latest = $this->transition($latest, ImportJobStatus::FAILED);
                    $latest['updatedAt'] = current_time('mysql');
                    $latest['retryable'] = true;
                    $latest['failureCode'] = 'import_batch_retryable';
                    $this->logger->error($latest, [
                        'row' => null,
                        'column' => null,
                        'code' => 'import_batch_retryable',
                        'message' => 'The batch stopped. Committed rows are journaled and the import may be retried safely.',
                    ]);
                    $this->jobs->updateAtomic($token, $latest, (int) ($latest['version'] ?? 0));
                }
            }
            if ($exception instanceof ImportJobException) {
                throw $exception;
            }
            throw new ImportJobException(
                'import_batch_retryable',
                'The import batch stopped. Already committed rows will not be processed again; you may retry it.',
                503,
                $exception
            );
        } finally {
            $this->jobs->releaseLease($token, $leaseId);
        }
    }

    public function pause(string $token): array
    {
        $job = $this->job($token);
        $status = $this->statusOf($job);
        if (!in_array($status, [ImportJobStatus::PROCESSING, ImportJobStatus::RUNNING], true)) {
            throw ImportJobException::invalidTransition($status, ImportJobStatus::PAUSED);
        }

        $job = $this->transition($job, ImportJobStatus::PAUSED);
        $job['updatedAt'] = current_time('mysql');
        $this->logger->info($job, 'Import paused by user.');
        $job = $this->saveJob($token, $job);

        return $this->summary($job) + ['done' => false, 'cancelled' => false];
    }

    public function cancel(string $token): array
    {
        $job = $this->job($token);
        $status = $this->statusOf($job);
        $job = $this->transition($job, ImportJobStatus::CANCELLED);
        $job['cancelled'] = true;
        $job['updatedAt'] = current_time('mysql');
        $job['completedAt'] = current_time('mysql');
        $job['expiresAt'] = time() + ImportLimits::historyTtl();
        $job['retryable'] = false;
        $this->logger->info($job, 'Import cancelled by user.');

        $this->deleteFile((string) ($job['path'] ?? ''));
        $job['path'] = '';
        $job = $this->saveJob($token, $job, false);

        return $this->summary($job) + ['done' => true, 'cancelled' => true];
    }

    public function resume(string $token): array
    {
        $job = $this->job($token);
        $status = $this->statusOf($job);

        if ($status === ImportJobStatus::PAUSED) {
            $job = $this->transition($job, ImportJobStatus::RUNNING);
            $job['updatedAt'] = current_time('mysql');
            $this->logger->info($job, 'Import resumed.');
            $this->saveJob($token, $job);
            return $this->process($token);
        }

        if ($status === ImportJobStatus::FAILED) {
            if (empty($job['retryable'])) {
                throw ImportJobException::unrecoverable();
            }

            $job = $this->transition($job, ImportJobStatus::RETRYING);
            $job['updatedAt'] = current_time('mysql');
            $job['failureCode'] = '';
            $this->logger->info($job, 'Retrying failed import batch.');
            $this->saveJob($token, $job);
            return $this->process($token);
        }

        throw ImportJobException::invalidTransition($status, ImportJobStatus::RUNNING);
    }

    public function status(string $token): array
    {
        return $this->summary($this->job($token));
    }

    public static function cleanupExpiredJobs(): void
    {
        $repository = new ImportJobRepository();
        $journal = new ImportJobRowRepository();
        $events = new ImportJobEventRepository();
        foreach ($repository->expired() as $job) {
            $path = (string) ($job['path'] ?? '');
            if ($path !== '' && is_file($path)) {
                wp_delete_file($path);
            }
            $jobId = (int) ($job['id'] ?? 0);
            $journal->deleteByJobId($jobId);
            $events->deleteByJobId($jobId);
            $repository->deleteById($jobId);
        }

        self::cleanupOrphanFiles($repository->activeFilePaths());
    }

    private static function cleanupOrphanFiles(array $activePaths = []): void
    {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return;
        }

        $directory = trailingslashit((string) $uploads['basedir']) . 'bml-imports';
        if (!is_dir($directory)) {
            return;
        }

        $activePaths = array_map('wp_normalize_path', $activePaths);
        $cutoff = time() - ImportLimits::jobTtl();
        $files = glob($directory . '/import-*.csv');
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (in_array(wp_normalize_path($file), $activePaths, true)) {
                continue;
            }
            if (is_file($file) && (int) filemtime($file) < $cutoff) {
                wp_delete_file($file);
            }
        }
    }

    private function finish(string $token, ImportJob $job): ImportJob
    {
        $this->deleteFile((string) ($job['path'] ?? ''));
        $job['path'] = '';
        $job['status'] = ImportJobStatus::COMPLETE->value;
        $job['completedAt'] = current_time('mysql');
        $job['updatedAt'] = current_time('mysql');
        $job['expiresAt'] = time() + ImportLimits::historyTtl();
        $job = $this->saveJob($token, $job, false);

        update_option('bml_last_import', [
            'date' => current_time('mysql'),
            'imported' => (int) $job['added'],
            'updated' => (int) $job['updated'],
            'duplicates' => (int) $job['duplicates'],
            'errors' => $this->totalErrors($job),
            'dry_run' => !empty($job['dryRun']) ? 1 : 0,
        ]);

        return $job;
    }

    private function job(string $token): ImportJob
    {
        $token = sanitize_key($token);
        $job = $this->jobs->find($token);

        if (!$job instanceof ImportJob) {
            throw ImportJobException::expired();
        }

        return $this->assertOwnedJob($job);
    }

    private function saveJob(string $token, ImportJob $job, bool $refreshTtl = true): ImportJob
    {
        if ($refreshTtl) {
            $job['expiresAt'] = time() + ImportLimits::jobTtl();
        }
        $expectedVersion = (int) ($job['version'] ?? 0);
        $saved = $this->jobs->updateAtomic($token, $job, $expectedVersion);
        if ($saved === null) {
            throw ImportJobException::conflict();
        }
        return $saved;
    }

    private function transition(ImportJob $job, ImportJobStatus $to): ImportJob
    {
        $from = $this->statusOf($job);
        $this->stateMachine->assertCanTransition($from, $to);
        $job['status'] = $to->value;
        return $job;
    }

    private function statusOf(ImportJob $job): ImportJobStatus
    {
        $status = ImportJobStatus::tryFrom((string) ($job['status'] ?? ''));
        if ($status === null) {
            throw new ImportJobException('import_job_invalid_status', 'The import has an invalid status.', 409);
        }
        return $status;
    }

    /** @return array{action:string,locationId:int}|null */
    private function recoverJournalEntry(int $jobId, string $rowHash): ?array
    {
        $ids = get_posts([
            'post_type' => 'bml_location',
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                ['key' => 'bml_import_job_id', 'value' => $jobId, 'compare' => '='],
                ['key' => 'bml_import_source_row_hash', 'value' => $rowHash, 'compare' => '='],
            ],
        ]);
        if (!is_array($ids) || $ids === []) {
            return null;
        }
        $locationId = (int) $ids[0];
        $action = (string) get_post_meta($locationId, 'bml_import_row_action', true);
        if (!in_array($action, ['created', 'updated'], true)) {
            $action = 'updated';
        }
        return ['action' => $action, 'locationId' => $locationId];
    }

    private function assertOwnedJob(ImportJob $job): ImportJob
    {
        $ownerUserId = (int) ($job['ownerUserId'] ?? 0);
        if ($ownerUserId <= 0 || $ownerUserId !== get_current_user_id()) {
            throw ImportJobException::forbidden();
        }
        $status = $this->statusOf($job);
        if ($status->requiresFile() && (empty($job['path']) || !is_file((string) $job['path']))) {
            throw ImportJobException::missingFile();
        }
        return $job;
    }

    private function applyCommittedJournal(ImportJob $job, array $journal, int $rowEnd): ImportJob
    {
        $action = (string) ($journal['action'] ?? 'skipped');
        if ($action === 'created') { $job['added']++; }
        elseif ($action === 'updated') { $job['updated']++; }
        elseif ($action === 'error') { $job['processingErrors']++; }
        elseif ($action === 'would_create') { $job['wouldCreate']++; }
        elseif ($action === 'would_update') { $job['wouldUpdate']++; }
        else { $job['skipped']++; }

        $rowNumber = (int) ($journal['row_number'] ?? ((int) ($job['committedRows'] ?? 0) + 1));
        $job['committedRows'] = $rowNumber;
        $job['processed'] = $rowNumber;
        $job['readRows'] = max((int) ($job['readRows'] ?? 0), $rowNumber);
        $job['committedPosition'] = $rowEnd;
        $job['readPosition'] = $rowEnd;
        $job['position'] = $rowEnd;
        $job['updatedAt'] = current_time('mysql');
        return $job;
    }

    private function leaseTtl(): int
    {
        return max(30, min(300, (int) apply_filters('bml_import_batch_lease_ttl', 90)));
    }

    private function deleteFile(string $path): void
    {
        if ($path !== '' && is_file($path)) {
            wp_delete_file($path);
        }
    }

    private function totalErrors(ImportJob $job): int
    {
        if ((int) ($job['processed'] ?? 0) === 0) {
            return (int) ($job['inspectionErrors'] ?? 0);
        }

        return (int) ($job['processingErrors'] ?? 0);
    }

    private function summary(ImportJob $job): array
    {
        $total = (int) ($job['total'] ?? 0);
        $processed = (int) ($job['processed'] ?? 0);

        return [
            'status' => (string) ($job['status'] ?? ImportJobStatus::PREPARED->value),
            'total' => $total,
            'processed' => $processed,
            'readRows' => (int) ($job['readRows'] ?? $processed),
            'committedRows' => (int) ($job['committedRows'] ?? $processed),
            'percent' => $total > 0 ? min(100, (int) round($processed / $total * 100)) : 0,
            'added' => (int) ($job['added'] ?? 0),
            'updated' => (int) ($job['updated'] ?? 0),
            'skipped' => (int) ($job['skipped'] ?? 0),
            'duplicates' => (int) ($job['duplicates'] ?? 0),
            'inspectionErrors' => (int) ($job['inspectionErrors'] ?? 0),
            'processingErrors' => (int) ($job['processingErrors'] ?? 0),
            'errors' => $this->totalErrors($job),
            'wouldCreate' => (int) ($job['wouldCreate'] ?? 0),
            'wouldUpdate' => (int) ($job['wouldUpdate'] ?? 0),
            'wouldSkip' => (int) ($job['wouldSkip'] ?? 0),
            'wouldFail' => (int) ($job['wouldFail'] ?? 0),
            'dryRun' => !empty($job['dryRun']),
            'retryable' => !empty($job['retryable']),
            'failureCode' => (string) ($job['failureCode'] ?? ''),
            'completedAt' => (string) ($job['completedAt'] ?? ''),
            'startedAt' => (string) ($job['startedAt'] ?? ''),
            'updatedAt' => (string) ($job['updatedAt'] ?? ''),
            'fileName' => (string) ($job['originalFileName'] ?? 'locations.csv'),
            'fileSize' => (int) ($job['fileSize'] ?? 0),
            'encoding' => (string) ($job['encoding'] ?? 'UTF-8'),
            'delimiter' => (string) ($job['delimiter'] ?? ','),
            'columnCount' => (int) ($job['columnCount'] ?? count((array) ($job['headers'] ?? []))),
            'currentRow' => min($total, $processed + 1),
            'errorDetails' => array_slice(array_merge((array) ($job['inspectionErrorDetails'] ?? []), (array) ($job['errorDetails'] ?? [])), -40),
            'errorMessages' => array_slice((array) ($job['errorMessages'] ?? []), -20),
            'log' => array_slice((array) ($job['log'] ?? []), -40),
        ];
    }
}
