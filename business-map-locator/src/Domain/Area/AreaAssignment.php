<?php
declare(strict_types=1);

namespace BusinessMapLocator\Domain\Area;

final class AreaAssignment
{
    public function byId(int $areaId, int $locationId = 0, bool $allowExistingInactive = true): int|\WP_Error
    {
        if ($areaId <= 0 || !term_exists($areaId, 'bml_area')) return new \WP_Error('location_area_invalid', __('Invalid Area.', 'business-map-locator'));
        foreach ((array) get_terms(['taxonomy' => 'bml_area', 'hide_empty' => false]) as $term) if ((int) $term->parent === $areaId) return new \WP_Error('location_area_not_leaf', __('Only leaf Areas can be assigned.', 'business-map-locator'));
        if ((string) get_term_meta($areaId, 'bml_area_active', true) === '0') {
            $existing = $locationId > 0 ? wp_get_post_terms($locationId, 'bml_area', ['fields' => 'ids']) : [];
            if (!$allowExistingInactive || is_wp_error($existing) || !in_array($areaId, array_map('intval', $existing), true)) return new \WP_Error('location_area_inactive', __('Inactive Areas cannot be newly assigned.', 'business-map-locator'));
        }
        return $areaId;
    }

    public function bySlug(string $value): int|\WP_Error
    {
        $value = trim($value); $slug = sanitize_key($value);
        if ($value === '' || $slug === '' || str_contains($value, '|') || str_contains($value, ',')) return new \WP_Error('bml_import_area_unknown', __('Area must be one existing Area slug.', 'business-map-locator'));
        foreach ((array) get_terms(['taxonomy' => 'bml_area', 'hide_empty' => false]) as $term) if ((string) $term->slug === $slug) return $this->byId((int) $term->term_id, 0, false);
        return new \WP_Error('bml_import_area_unknown', __('The Area slug does not exist.', 'business-map-locator'));
    }

}
