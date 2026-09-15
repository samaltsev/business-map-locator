<?php
declare(strict_types=1);

namespace BusinessMapLocator\Support;

final class AreaCompatibilityResolver
{
    private const LEGACY_CITY_AREA_META_KEY = '_bml_area_term_id';

    /** @return array{area:string,city:string} */
    public static function normalize(mixed $area, mixed $city): array
    {
        $areaSlug = self::slug((string) $area);
        $citySlug = self::slug((string) $city);

        if ($areaSlug !== '') {
            return ['area' => $areaSlug, 'city' => ''];
        }

        if ($citySlug === '') {
            return ['area' => '', 'city' => ''];
        }

        if (function_exists('get_term_by')) {
            $publicArea = get_term_by('slug', $citySlug, 'bml_area');
            if (is_object($publicArea) && isset($publicArea->slug)) {
                return ['area' => self::slug((string) $publicArea->slug), 'city' => ''];
            }
        }

        $mappedArea = self::mappedAreaSlug($citySlug);

        return $mappedArea !== ''
            ? ['area' => $mappedArea, 'city' => '']
            : ['area' => '', 'city' => $citySlug];
    }

    private static function mappedAreaSlug(string $citySlug): string
    {
        if (!function_exists('get_term_by') || !function_exists('get_term_meta') || !function_exists('get_term')) {
            return '';
        }

        $city = get_term_by('slug', $citySlug, 'bml_city');
        if (!is_object($city) || !isset($city->term_id)) {
            return '';
        }

        $areaId = (int) get_term_meta((int) $city->term_id, self::LEGACY_CITY_AREA_META_KEY, true);
        if ($areaId <= 0) {
            return '';
        }

        $area = get_term($areaId, 'bml_area');
        if (!is_object($area) || !isset($area->slug)) {
            return '';
        }

        return self::slug((string) $area->slug);
    }

    private static function slug(string $value): string
    {
        if (function_exists('sanitize_title')) {
            return sanitize_title($value);
        }

        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }

        return strtolower(trim($value));
    }
}
