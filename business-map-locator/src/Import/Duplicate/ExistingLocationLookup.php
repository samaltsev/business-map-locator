<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Duplicate;

final class ExistingLocationLookup
{
    /** @return array{postId:int,error?:string,message?:string} */
    public function match(string $externalId, string $fingerprint): array
    {
        $externalIds = $externalId !== '' ? $this->findByMeta('bml_external_id', $externalId) : [];
        if (count($externalIds) > 1) {
            return ['postId' => 0, 'error' => 'duplicate_external_id_in_database', 'message' => 'The external_id matches more than one existing location.'];
        }

        $fingerprintIds = $fingerprint !== '' ? $this->findByMeta('bml_import_fingerprint', $fingerprint) : [];
        if (count($fingerprintIds) > 1) {
            return ['postId' => 0, 'error' => 'ambiguous_fingerprint', 'message' => 'The row fingerprint matches more than one existing location.'];
        }

        $externalPostId = (int) ($externalIds[0] ?? 0);
        $fingerprintPostId = (int) ($fingerprintIds[0] ?? 0);
        if ($externalPostId > 0 && $fingerprintPostId > 0 && $externalPostId !== $fingerprintPostId) {
            return ['postId' => 0, 'error' => 'external_id_fingerprint_conflict', 'message' => 'The external_id and fingerprint refer to different existing locations.'];
        }

        return ['postId' => $externalPostId > 0 ? $externalPostId : $fingerprintPostId];
    }

    /** @return int[] */
    private function findByMeta(string $key, string $value): array
    {
        $ids = get_posts([
            'post_type' => 'bml_location',
            'post_status' => ['publish', 'draft', 'private'],
            'fields' => 'ids',
            'posts_per_page' => 2,
            'no_found_rows' => true,
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_query' => [[
                'key' => $key,
                'value' => $value,
                'compare' => '=',
            ]],
        ]);
        return is_array($ids) ? array_values(array_map('intval', $ids)) : [];
    }
}
