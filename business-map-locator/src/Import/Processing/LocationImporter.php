<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Processing;

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
        $this->saveLocation($postId, $data, $externalId, $fingerprint, $lat, $lng, $isUpdate);
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

    private function saveLocation(int $postId, array $data, string $externalId, string $fingerprint, float $lat, float $lng, bool $isUpdate): void
    {
        foreach (['address','region','country','postcode','phone','hours'] as $key) {
            update_post_meta($postId, 'bml_' . $key, sanitize_text_field((string) ($data[$key] ?? '')));
        }
        update_post_meta($postId, 'bml_email', sanitize_email((string) ($data['email'] ?? '')));
        update_post_meta($postId, 'bml_website', esc_url_raw((string) ($data['website'] ?? '')));
        update_post_meta($postId, 'bml_lat', $lat);
        update_post_meta($postId, 'bml_lng', $lng);
        update_post_meta($postId, 'bml_external_id', $externalId);
        update_post_meta($postId, 'bml_import_fingerprint', $fingerprint);
        $hasOperationalStatus = array_key_exists('operational_status', $data) && $data['operational_status'] !== '';
        $hasVisible = array_key_exists('visible', $data);
        if (!$isUpdate || $hasOperationalStatus || $hasVisible) {
            $operationalStatus = $this->operationalStatus($postId, $data, $isUpdate, $hasOperationalStatus, $hasVisible);
            update_post_meta($postId, 'bml_operational_status', $operationalStatus);
            update_post_meta($postId, 'bml_visible', OperationalStatusResolver::visibleValue($operationalStatus));
        }
        if (!empty($data['category'])) {
            $this->assignTerms($postId, (string) $data['category'], 'bml_category');
        }
        if (!empty($data['city'])) {
            $this->assignTerms($postId, (string) $data['city'], 'bml_city');
        }
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
