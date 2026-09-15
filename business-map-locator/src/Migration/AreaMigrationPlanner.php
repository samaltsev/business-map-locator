<?php
declare(strict_types=1);

namespace BusinessMapLocator\Migration;

/** Pure decision and plan builder. It deliberately has no WordPress write dependency. */
final class AreaMigrationPlanner
{
    public const CREATE = 'CREATE';
    public const ALREADY_MAPPED = 'ALREADY_MAPPED';
    public const COLLISION = 'COLLISION';
    public const AMBIGUOUS = 'AMBIGUOUS';
    public const REQUIRES_DECISION = 'REQUIRES_DECISION';

    /** @param array<string,mixed> $city @param list<array<string,mixed>> $areas @return array<string,mixed> */
    public function decide(array $city, array $areas): array
    {
        $cityId = (int) $city['id'];
        $pointed = (int) ($city['area_term_id'] ?? 0);
        $reciprocal = [];
        foreach ($areas as $area) {
            if ((int) ($area['migrated_from_city_term_id'] ?? 0) === $cityId) {
                $reciprocal[] = $area;
            }
        }
        foreach ($areas as $area) {
            if ($pointed > 0 && (int) $area['id'] === $pointed && (int) ($area['migrated_from_city_term_id'] ?? 0) === $cityId) {
                return ['status' => self::ALREADY_MAPPED, 'city_id' => $cityId, 'target_area_id' => $pointed];
            }
        }
        if ($pointed > 0 || $reciprocal !== []) {
            return ['status' => self::REQUIRES_DECISION, 'city_id' => $cityId, 'reason' => 'provenance_is_not_reciprocal'];
        }
        $candidates = array_values(array_filter($areas, static fn (array $area): bool =>
            (string) $area['slug'] === (string) $city['slug'] || (string) $area['name'] === (string) $city['name']));
        if (count($candidates) > 1) {
            return ['status' => self::AMBIGUOUS, 'city_id' => $cityId, 'candidate_area_ids' => array_map(static fn (array $area): int => (int) $area['id'], $candidates)];
        }
        if ($candidates !== []) {
            return ['status' => self::COLLISION, 'city_id' => $cityId, 'candidate_area_ids' => [(int) $candidates[0]['id']]];
        }
        return ['status' => self::CREATE, 'city_id' => $cityId, 'planned_area' => ['name' => (string) $city['name'], 'slug' => (string) $city['slug'], 'parent' => 0]];
    }

    /** @param list<array<string,mixed>> $locations @param array<int,array<string,mixed>> $decisions @return array<string,mixed> */
    public function planLocations(array $locations, array $decisions): array
    {
        $records = []; $counts = ['UNCHANGED' => 0, 'ADD_AREA' => 0, 'NOOP' => 0, self::REQUIRES_DECISION => 0];
        foreach ($locations as $location) {
            $cities = array_values(array_map('intval', $location['city_ids'])); $areas = array_values(array_map('intval', $location['area_ids']));
            $record = ['location_id' => (int) $location['location_id'], 'post_status' => (string) $location['post_status'], 'city_ids' => $cities, 'area_ids' => $areas];
            if ($cities === []) { $record['status'] = 'UNCHANGED'; }
            elseif (count($cities) !== 1) { $record['status'] = self::REQUIRES_DECISION; $record['reason'] = 'multiple_city_terms'; }
            else {
                $decision = $decisions[$cities[0]] ?? ['status' => self::REQUIRES_DECISION, 'reason' => 'missing_city_decision'];
                if (!in_array($decision['status'], [self::CREATE, self::ALREADY_MAPPED], true)) { $record['status'] = self::REQUIRES_DECISION; $record['reason'] = 'city_' . strtolower((string) $decision['status']); }
                elseif ($areas !== [] && ($decision['status'] !== self::ALREADY_MAPPED || !in_array((int) $decision['target_area_id'], $areas, true))) { $record['status'] = self::REQUIRES_DECISION; $record['reason'] = 'unrelated_existing_area'; }
                elseif ($decision['status'] === self::ALREADY_MAPPED && in_array((int) $decision['target_area_id'], $areas, true)) { $record['status'] = 'NOOP'; $record['target_area_id'] = (int) $decision['target_area_id']; }
                else { $record['status'] = 'ADD_AREA'; if ($decision['status'] === self::CREATE) { $record['planned_area'] = $decision['planned_area']; } else { $record['target_area_id'] = (int) $decision['target_area_id']; } }
            }
            $counts[$record['status']]++; $records[] = $record;
        }
        return ['records' => $records, 'counts' => $counts];
    }
}
