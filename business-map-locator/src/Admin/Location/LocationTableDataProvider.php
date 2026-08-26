<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Location;
if (!defined('ABSPATH')) { exit; }
final class LocationTableDataProvider
{
    public function rows(array $posts): array
    {
        $rows = [];
        foreach ($posts as $post) {
            $cats = wp_get_post_terms($post->ID, 'bml_category');
            $cities = wp_get_post_terms($post->ID, 'bml_city');
            $lat = get_post_meta($post->ID, 'bml_lat', true);
            $lng = get_post_meta($post->ID, 'bml_lng', true);
            $address = (string) get_post_meta($post->ID, 'bml_address', true);
            $phone = (string) get_post_meta($post->ID, 'bml_phone', true);
            $category = $cats && !is_wp_error($cats) ? $cats[0] : null;
            $city = $cities && !is_wp_error($cities) ? $cities[0] : null;
            $hasCoordinates = $lat !== '' && $lng !== '';
            $hasCategory = $category !== null;
            $hasCity = $city !== null;
            $hasAddress = trim($address) !== '';
            $qualityChecks = [$hasAddress, $hasCoordinates, $hasCategory, $hasCity];
            $qualityCompleted = count(array_filter($qualityChecks));
            $qualityPercent = (int) round(($qualityCompleted / count($qualityChecks)) * 100);
            $categoryIcon = '';
            if ($category !== null) {
                $iconId = (int) get_term_meta((int) $category->term_id, 'bml_icon_id', true);
                if ($iconId > 0) {
                    $categoryIcon = (string) wp_get_attachment_image($iconId, [52, 52], false, ['class' => 'bml-location-category-icon']);
                }
            }
            $rows[] = [
                'id' => (int) $post->ID,
                'title' => (string) $post->post_title,
                'address' => $address,
                'category' => $category !== null ? (string) $category->name : '—',
                'category_slug' => $category !== null ? (string) $category->slug : '',
                'city' => $city !== null ? (string) $city->name : '—',
                'city_slug' => $city !== null ? (string) $city->slug : '',
                'coordinates' => $hasCoordinates ? number_format((float) $lat, 5) . ', ' . number_format((float) $lng, 5) : '—',
                'lat' => $hasCoordinates ? (float) $lat : null,
                'lng' => $hasCoordinates ? (float) $lng : null,
                'phone' => $phone,
                'status' => (string) $post->post_status,
                'thumbnail' => has_post_thumbnail($post->ID) ? get_the_post_thumbnail($post->ID, [52, 52]) : $categoryIcon,
                'quality_percent' => $qualityPercent,
                'quality_state' => $qualityPercent === 100 ? 'complete' : ($qualityPercent >= 75 ? 'review' : 'incomplete'),
                'quality_label' => $qualityPercent === 100 ? __('Complete', 'business-map-locator') : __('Needs review', 'business-map-locator'),
                'missing' => [
                    'address' => !$hasAddress,
                    'coordinates' => !$hasCoordinates,
                    'category' => !$hasCategory,
                    'city' => !$hasCity,
                    'phone' => trim($phone) === '',
                ],
                'modified_human' => human_time_diff((int) get_post_modified_time('U', true, $post), current_time('timestamp', true)) . ' ' . __('ago', 'business-map-locator'),
                'modified_iso' => get_post_modified_time(DATE_W3C, true, $post),
            ];
        }
        return $rows;
    }
}
