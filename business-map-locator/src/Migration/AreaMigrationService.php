<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

final class AreaMigrationService
{
    private AreaMigrationPlanner $planner;
    private AreaMigrationStateStore $state;

    public function __construct(private readonly MigrationSnapshotStore $snapshots, ?AreaMigrationPlanner $planner = null, ?AreaMigrationStateStore $state = null)
    {
        $this->planner = $planner ?? new AreaMigrationPlanner();
        $this->state = $state ?? new AreaMigrationStateStore();
    }

    /** @return array<string, int|bool> */
    public function inspect(): array
    {
        return [
            'bml_city_exists' => taxonomy_exists('bml_city'),
            'bml_area_exists' => taxonomy_exists('bml_area'),
            'location_count' => count($this->locations(true)),
            'city_terms_count' => count($this->terms('bml_city')),
            'area_terms_count' => count($this->terms('bml_area')),
        ];
    }

    /** @return array{snapshot: array<string, mixed>, path: string} */
    public function createSnapshot(?int $createdByUserId = null): array
    {
        $simulation = $this->simulateMigration();
        $snapshot = [
            'schema_version' => 2,
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
                'bml_city' => $this->inventory('bml_city'),
                'bml_area' => $this->inventory('bml_area'),
            ],
            'locations' => $this->locations(true),
            'plan' => $simulation['plan'],
        ];

        return ['snapshot' => $snapshot, 'path' => $this->snapshots->write($snapshot)];
    }

    /** @return array<string, mixed> */
    public function simulateMigration(): array
    {
        $cities = $this->inventory('bml_city'); $areas = $this->inventory('bml_area'); $decisions = [];
        $cityCounts = array_fill_keys([AreaMigrationPlanner::CREATE, AreaMigrationPlanner::ALREADY_MAPPED, AreaMigrationPlanner::COLLISION, AreaMigrationPlanner::AMBIGUOUS, AreaMigrationPlanner::REQUIRES_DECISION], 0);
        foreach ($cities as $city) { $decision = $this->planner->decide($city, $areas); $decisions[(int) $city['id']] = $decision; $cityCounts[$decision['status']]++; }
        $locationPlan = $this->planner->planLocations($this->locations(true), $decisions);
        $requiredTaxonomies = [
            'bml_city' => taxonomy_exists('bml_city'),
            'bml_area' => taxonomy_exists('bml_area'),
        ];
        $planningBlockers = [];
        foreach ($requiredTaxonomies as $taxonomy => $available) {
            if (!$available) {
                $planningBlockers[] = ['code' => 'TAXONOMY_UNAVAILABLE', 'taxonomy' => $taxonomy];
            }
        }
        $plan = ['city_decisions' => array_values($decisions), 'location_decisions' => $locationPlan['records'], 'counts' => ['cities' => $cityCounts, 'locations' => $locationPlan['counts']], 'planning_blockers' => $planningBlockers, 'collision_list' => array_values(array_filter($decisions, static fn (array $d): bool => $d['status'] === AreaMigrationPlanner::COLLISION)), 'ambiguous_list' => array_values(array_filter($decisions, static fn (array $d): bool => $d['status'] === AreaMigrationPlanner::AMBIGUOUS)), 'decision_required_list' => array_values(array_filter($locationPlan['records'], static fn (array $r): bool => $r['status'] === AreaMigrationPlanner::REQUIRES_DECISION)), 'planned_area_creations' => array_values(array_filter($decisions, static fn (array $d): bool => $d['status'] === AreaMigrationPlanner::CREATE)), 'planned_area_relationship_additions' => array_values(array_filter($locationPlan['records'], static fn (array $r): bool => $r['status'] === 'ADD_AREA')), 'no_op_records' => array_values(array_filter($locationPlan['records'], static fn (array $r): bool => $r['status'] === 'NOOP'))];

        return [
            'locations' => count($locationPlan['records']), 'city_terms' => count($cities), 'would_create_areas' => $cityCounts[AreaMigrationPlanner::CREATE], 'would_migrate_relationships' => $locationPlan['counts']['ADD_AREA'], 'warnings' => [], 'errors' => [], 'plan' => $plan,
        ];
    }

    /** @return array<string,mixed> */
    public function startPlanningRun(?int $createdByUserId = null): array
    {
        $run = $this->startInspectionRun();
        $this->snapshotRun($run['run_id'], $createdByUserId);
        return $this->simulateRun($run['run_id']);
    }

    /** @return array<string,mixed> */
    public function startInspectionRun(): array
    {
        $run = $this->state->create();
        return $this->state->transition($run['run_id'], AreaMigrationStateStore::INSPECTED, ['inspection' => $this->inspect()]);
    }

    /** @return array<string,mixed> */
    public function snapshotRun(string $runId, ?int $createdByUserId = null): array
    {
        $run = $this->requireState($runId, AreaMigrationStateStore::INSPECTED);
        $snapshot = $this->createSnapshot($createdByUserId);
        return $this->state->transition($run['run_id'], AreaMigrationStateStore::SNAPSHOTTED, ['snapshot_path' => $snapshot['path']]);
    }

    /** @return array<string,mixed> */
    public function simulateRun(string $runId): array
    {
        $run = $this->requireState($runId, AreaMigrationStateStore::SNAPSHOTTED);
        $snapshot = $this->snapshots->read((string) ($run['snapshot_path'] ?? ''));
        if ($snapshot === null || ($snapshot['schema_version'] ?? null) !== 2) {
            throw new \LogicException('Migration snapshot is unavailable.');
        }
        $counts = (array) ($snapshot['plan']['counts'] ?? []);
        $run = $this->state->transition($runId, AreaMigrationStateStore::SIMULATED, ['plan_counts' => $counts]);
        $cities = (array) ($counts['cities'] ?? []); $locations = (array) ($counts['locations'] ?? []);
        $planningBlockers = array_values((array) ($snapshot['plan']['planning_blockers'] ?? []));
        $blocked = $planningBlockers !== [] || (int) ($cities[AreaMigrationPlanner::COLLISION] ?? 0) + (int) ($cities[AreaMigrationPlanner::AMBIGUOUS] ?? 0) + (int) ($cities[AreaMigrationPlanner::REQUIRES_DECISION] ?? 0) + (int) ($locations[AreaMigrationPlanner::REQUIRES_DECISION] ?? 0) > 0;
        return $this->state->transition($run['run_id'], $blocked ? AreaMigrationStateStore::BLOCKED : AreaMigrationStateStore::READY, ['planning_blockers' => $planningBlockers]);
    }

    /** @return array<string,mixed> */
    private function requireState(string $runId, string $expected): array
    {
        $run = $this->state->get($runId);
        if ($run === null || ($run['state'] ?? null) !== $expected) {
            throw new \LogicException('Migration action is not allowed in the current run state.');
        }
        return $run;
    }

    /** @return list<array{id: int, parent: int, slug: string, count: int, relationships_count: int}> */
    private function inventory(string $taxonomy): array
    {
        $terms = [];
        foreach ($this->terms($taxonomy) as $term) {
            $termId = (int) $term->term_id;
            $relationships = get_objects_in_term([$termId], $taxonomy);
            $terms[] = [
                'id' => $termId,
                'parent' => (int) $term->parent,
                'name' => (string) $term->name, 'slug' => (string) $term->slug,
                'count' => (int) $term->count,
                'relationships_count' => is_array($relationships) ? count($relationships) : 0,
                'area_term_id' => (int) get_term_meta($termId, '_bml_area_term_id', true),
                'migrated_from_city_term_id' => (int) get_term_meta($termId, '_bml_migrated_from_city_term_id', true),
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

        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 0, 'orderby' => 'term_id', 'order' => 'ASC']);

        return is_array($terms) ? $terms : [];
    }

    /** @return list<array<string,mixed>> */
    private function locations(bool $affectedOnly): array
    {
        $rows = [];
        foreach (get_posts(['post_type' => 'bml_location', 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => -1, 'nopaging' => true, 'suppress_filters' => true]) as $id) {
            $post = get_post((int) $id); if ($post === null) { continue; }
            $cities = wp_get_post_terms((int) $id, 'bml_city', ['fields' => 'ids']); $areas = wp_get_post_terms((int) $id, 'bml_area', ['fields' => 'ids']);
            $cityIds = is_array($cities) ? array_map('intval', $cities) : []; if ($affectedOnly && $cityIds === []) { continue; }
            $rows[] = ['location_id' => (int) $id, 'post_status' => (string) $post->post_status, 'city_ids' => $cityIds, 'area_ids' => is_array($areas) ? array_map('intval', $areas) : []];
        }
        return $rows;
    }
}
