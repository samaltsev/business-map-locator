<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Processing;

use BusinessMapLocator\Import\Config\ImportUpdatePolicy;
use BusinessMapLocator\Import\Dto\ImportJob;
use BusinessMapLocator\Import\Mapping\ImportMapper;
use BusinessMapLocator\Import\Duplicate\ExistingLocationLookup;
use BusinessMapLocator\Support\OperationalStatusResolver;
use BusinessMapLocator\Support\SlugGenerator;

final class LocationImporter
{
    public function __construct(private ImportMapper $mapper, private ?ExistingLocationLookup $lookup = null)
    {
        $this->lookup ??= new ExistingLocationLookup();
    }

    public function importRow(array $row, ImportJob $job, string $sourceRowHash = ''): array
    {
        if (count($row) !== count($job['headers'])) {
            $job['processingErrors']++;
            if (!empty($job['dryRun'])) { $job['wouldFail']++; }
            return ['job' => $job, 'error' => 'The number of columns does not match the CSV header.', 'code' => 'invalid_column_count', 'action' => 'error', 'locationId' => 0];
        }

        $data = $this->mapper->map($job['headers'], $row);
        $validation = $this->mapper->validate($data);
        if (empty($validation['valid'])) {
            $job['processingErrors']++;
            if (!empty($job['dryRun'])) { $job['wouldFail']++; }
            return ['job' => $job, 'error' => $validation['error'] ?? 'The row is invalid.', 'code' => 'invalid_location_row', 'action' => 'error', 'locationId' => 0];
        }

        $title = (string) $validation['title'];
        $lat = (float) $validation['lat'];
        $lng = (float) $validation['lng'];
        $externalId = sanitize_text_field((string) ($data['external_id'] ?? ''));
        $fingerprint = $this->mapper->fingerprint($title, (string) ($data['address'] ?? ''), (string) $lat, (string) $lng);
        $match = $this->existingPostMatch($job, $externalId, $fingerprint);
        if (!empty($match['error'])) {
            $job['processingErrors']++;
            if (!empty($job['dryRun'])) { $job['wouldFail']++; }
            return ['job' => $job, 'error' => $match['message'], 'code' => $match['error'], 'action' => 'error', 'locationId' => 0];
        }
        $postId = (int) ($match['postId'] ?? 0);
        $isUpdate = $postId > 0;
        $updatePolicy = ImportUpdatePolicy::normalize((string) ($job['updatePolicy'] ?? ''));
        $updatePolicy ??= ImportUpdatePolicy::NON_EMPTY_ONLY;

        if (($isUpdate && $updatePolicy === ImportUpdatePolicy::CREATE_ONLY) || (!$isUpdate && $updatePolicy === ImportUpdatePolicy::UPDATE_ONLY)) {
            if (!empty($job['dryRun'])) {
                $job['wouldSkip']++;
                return ['job' => $job, 'message' => 'dry run: would skip.', 'action' => 'would_skip', 'locationId' => $postId];
            }

            $job['skipped']++;
            return ['job' => $job, 'message' => 'skipped by update policy.', 'action' => 'skipped', 'locationId' => $postId];
        }

        if (!empty($job['dryRun'])) {
            $job[$isUpdate ? 'wouldUpdate' : 'wouldCreate']++;
            return ['job' => $job, 'message' => 'dry run: would ' . ($isUpdate ? 'update' : 'create') . '.', 'action' => $isUpdate ? 'would_update' : 'would_create', 'locationId' => $postId];
        }

        $result = $this->savePost($postId, $title, $data);
        if (is_wp_error($result)) {
            $job['processingErrors']++;
            return ['job' => $job, 'error' => $result->get_error_message(), 'code' => 'location_save_failed', 'action' => 'error', 'locationId' => 0];
        }

        $postId = (int) $result;
        if ($sourceRowHash !== '') {
            update_post_meta($postId, 'bml_import_source_row_hash', $sourceRowHash);
            update_post_meta($postId, 'bml_import_job_id', (int) ($job['id'] ?? 0));
            update_post_meta($postId, 'bml_import_row_action', $isUpdate ? 'updated' : 'created');
        }
        $this->saveLocation($postId, $title, $data, $externalId, $lat, $lng, $isUpdate, $updatePolicy);
        (new \BML_Location_Index())->upsert($postId);

        $job[$isUpdate ? 'updated' : 'added']++;

        return ['job' => $job, 'message' => $isUpdate ? 'updated #' . $postId : 'created #' . $postId, 'action' => $isUpdate ? 'updated' : 'created', 'locationId' => $postId];
    }

    /** @return array{postId:int,error?:string,message?:string} */
    private function existingPostMatch(ImportJob $job, string $externalId, string $fingerprint): array
    {
        if ($externalId !== '' && in_array($externalId, (array) ($job['duplicateExternalIds'] ?? []), true)) {
            return ['postId' => 0, 'error' => 'duplicate_external_id_in_file', 'message' => 'The external_id is repeated in this CSV file.'];
        }
        return $this->lookup->match($externalId, $fingerprint);
    }

    private function savePost(int $postId, string $title, array $data)
    {
        $postData = [
            'post_type' => 'bml_location',
            'post_title' => $title,
        ];
        if ($postId > 0) {
            if (($data['status'] ?? '') !== '') {
                $postData['post_status'] = $data['status'] === 'draft' ? 'draft' : 'publish';
            }
            $postData['ID'] = $postId;
            return wp_update_post($postData, true);
        }
        $postData['post_status'] = ($data['status'] ?? '') === 'draft' ? 'draft' : 'publish';
        return wp_insert_post($postData, true);
    }

    private function saveLocation(int $postId, string $title, array $data, string $externalId, float $lat, float $lng, bool $isUpdate, string $updatePolicy): void
    {
        foreach (['address','region','country','postcode','phone','hours'] as $key) {
            if ($this->shouldWrite($data, $key, $isUpdate, $updatePolicy)) {
                update_post_meta($postId, 'bml_' . $key, sanitize_text_field((string) ($data[$key] ?? '')));
            }
        }
        if ($this->shouldWrite($data, 'email', $isUpdate, $updatePolicy)) {
            update_post_meta($postId, 'bml_email', sanitize_email((string) ($data['email'] ?? '')));
        }
        if ($this->shouldWrite($data, 'website', $isUpdate, $updatePolicy)) {
            update_post_meta($postId, 'bml_website', esc_url_raw((string) ($data['website'] ?? '')));
        }
        update_post_meta($postId, 'bml_lat', $lat);
        update_post_meta($postId, 'bml_lng', $lng);
        if ($this->shouldWrite($data, 'external_id', $isUpdate, $updatePolicy)) {
            update_post_meta($postId, 'bml_external_id', $externalId);
        }
        $effectiveAddress = $this->shouldWrite($data, 'address', $isUpdate, $updatePolicy)
            ? sanitize_text_field((string) ($data['address'] ?? ''))
            : (string) get_post_meta($postId, 'bml_address', true);
        update_post_meta($postId, 'bml_import_fingerprint', $this->mapper->fingerprint($title, $effectiveAddress, (string) $lat, (string) $lng));
        $hasOperationalStatus = $this->shouldWrite($data, 'operational_status', $isUpdate, $updatePolicy) && (string) ($data['operational_status'] ?? '') !== '';
        $hasVisible = $this->shouldWrite($data, 'visible', $isUpdate, $updatePolicy) && (string) ($data['visible'] ?? '') !== '';
        if (!$isUpdate || $hasOperationalStatus || $hasVisible) {
            $operationalStatus = $this->operationalStatus($postId, $data, $isUpdate, $hasOperationalStatus, $hasVisible);
            update_post_meta($postId, 'bml_operational_status', $operationalStatus);
            update_post_meta($postId, 'bml_visible', OperationalStatusResolver::visibleValue($operationalStatus));
        }
        if ($this->shouldWrite($data, 'category', $isUpdate, $updatePolicy)) {
            $this->assignTerms($postId, (string) ($data['category'] ?? ''), 'bml_category');
        }
        if ($this->shouldWrite($data, 'city', $isUpdate, $updatePolicy)) {
            $this->assignTerms($postId, (string) ($data['city'] ?? ''), 'bml_city');
        }
    }

    private function shouldWrite(array $data, string $key, bool $isUpdate, string $updatePolicy): bool
    {
        if (!$isUpdate) {
            return true;
        }

        if (!array_key_exists($key, $data)) {
            return false;
        }

        if ($updatePolicy === ImportUpdatePolicy::OVERWRITE_MAPPED) {
            return true;
        }

        return trim((string) $data[$key]) !== '';
    }

    private function operationalStatus(int $postId, array $data, bool $isUpdate, bool $hasOperationalStatus, bool $hasVisible): string
    {
        if ($hasOperationalStatus) {
            return (string) $data['operational_status'];
        }

        if ($hasVisible && (string) $data['visible'] === '0') {
            return OperationalStatusResolver::HIDDEN;
        }

        if (!$isUpdate) {
            return OperationalStatusResolver::ACTIVE;
        }

        $existing = OperationalStatusResolver::resolve(
            get_post_meta($postId, 'bml_operational_status', true),
            get_post_meta($postId, 'bml_visible', true)
        );

        return $hasVisible && $existing === OperationalStatusResolver::HIDDEN
            ? OperationalStatusResolver::ACTIVE
            : $existing;
    }

    private function assignTerms(int $postId, string $value, string $taxonomy): void
    {
        $termIds = [];
        foreach (preg_split('/\s*\|\s*/u', $value) ?: [] as $name) {
            $name = sanitize_text_field(trim($name));
            if ($name === '') { continue; }
            $term = term_exists($name, $taxonomy);
            if (!$term) {
                $term = wp_insert_term($name, $taxonomy, ['slug' => SlugGenerator::fromTerm($name)]);
            }
            if (!is_wp_error($term)) {
                $termIds[] = is_array($term) ? (int) $term['term_id'] : (int) $term;
            }
        }
        if ($termIds !== []) { wp_set_object_terms($postId, array_values(array_unique($termIds)), $taxonomy); }
    }

}
