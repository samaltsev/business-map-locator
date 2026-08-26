<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

final class AreaMigrationService
{
    public function __construct(private readonly MigrationSnapshotStore $snapshots)
    {
    }

    /** @return array<string, int|bool> */
    public function inspect(): array
    {
        return [
            'bml_city_exists' => taxonomy_exists('bml_city'),
            'bml_area_exists' => taxonomy_exists('bml_area'),
            'location_count' => count(get_posts([
                'post_type' => 'bml_location',
                'post_status' => 'any',
                'fields' => 'ids',
                'numberposts' => -1,
                'nopaging' => true,
                'suppress_filters' => true,
            ])),
            'city_terms_count' => count($this->terms('bml_city')),
            'area_terms_count' => count($this->terms('bml_area')),
        ];
    }

    /** @return array{snapshot: array<string, mixed>, path: string} */
    public function createSnapshot(?int $createdByUserId = null): array
    {
        $snapshot = [
            'schema_version' => 1,
            'migration' => 'bml_city_to_area_v1',
            'created_at' => gmdate('c'),
            'created_by_user_id' => $createdByUserId ?? get_current_user_id(),
            'ownership' => [
                'site_url' => site_url('/'),
                'plugin_version' => defined('BML_VERSION') ? BML_VERSION : '',
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'created_at' => gmdate('c'),
                'created_by_user_id' => $createdByUserId ?? get_current_user_id(),
            ],
            'taxonomies' => ['bml_city', 'bml_area'],
            'terms' => [
                'bml_city' => $this->snapshotTerms('bml_city'),
                'bml_area' => $this->snapshotTerms('bml_area'),
            ],
        ];

        return ['snapshot' => $snapshot, 'path' => $this->snapshots->write($snapshot)];
    }

    /** @return array{locations: int, city_terms: int, would_create_areas: int, would_migrate_relationships: int, warnings: list<string>, errors: list<string>} */
    public function simulateMigration(): array
    {
        $state = $this->inspect();
        $warnings = [];
        $errors = [];

        if (!$state['bml_city_exists']) {
            $errors[] = 'Legacy bml_city taxonomy is not registered.';
        }
        if (!$state['bml_area_exists']) {
            $errors[] = 'Canonical bml_area taxonomy is not registered.';
        }

        $cityTerms = $this->snapshotTerms('bml_city');
        $relationships = array_sum(array_map(static fn (array $term): int => (int) $term['relationships_count'], $cityTerms));
        if ($state['city_terms_count'] === 0) {
            $warnings[] = 'No legacy City terms exist.';
        }

        return [
            'locations' => (int) $state['location_count'],
            'city_terms' => (int) $state['city_terms_count'],
            'would_create_areas' => (int) $state['city_terms_count'],
            'would_migrate_relationships' => $relationships,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /** @return list<array{id: int, parent: int, slug: string, count: int, relationships_count: int}> */
    private function snapshotTerms(string $taxonomy): array
    {
        $terms = [];
        foreach ($this->terms($taxonomy) as $term) {
            $termId = (int) $term->term_id;
            $relationships = get_objects_in_term([$termId], $taxonomy);
            $terms[] = [
                'id' => $termId,
                'parent' => (int) $term->parent,
                'slug' => (string) $term->slug,
                'count' => (int) $term->count,
                'relationships_count' => is_array($relationships) ? count($relationships) : 0,
            ];
        }

        return $terms;
    }

    /** @return list<object> */
    private function terms(string $taxonomy): array
    {
        if (!taxonomy_exists($taxonomy)) {
            return [];
        }

        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

        return is_array($terms) ? $terms : [];
    }
}
