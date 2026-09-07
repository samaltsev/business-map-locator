<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Location;

if (!defined('ABSPATH')) {
    exit;
}

/** Canonical write contract shared by the form and editor AJAX entry points. */
final class LocationWriteService
{
    public function __construct(private \BML_Location_Index $index)
    {
    }

    /** @param array<string, mixed> $input */
    public function save(int $id, array $input): int|\WP_Error
    {
        $existing = $id > 0 ? get_post($id) : null;
        $title = $this->string($input, 'title', (string) ($existing?->post_title ?? ''));
        if ($title === '') {
            return new \WP_Error('location_title_required', __('Location title is required.', 'business-map-locator'));
        }
        $status = $this->string($input, 'status', (string) ($existing?->post_status ?? 'draft')) === 'publish' ? 'publish' : 'draft';
        $coordinates = $this->coordinates($id, $input);
        if (is_wp_error($coordinates)) {
            return $coordinates;
        }
        if ($status === 'publish' && ($coordinates['lat'] === null || $coordinates['lng'] === null)) {
            return new \WP_Error('location_coordinates_required', __('A map position is required before publishing.', 'business-map-locator'));
        }
        foreach (['bml_category' => 'category_id', 'bml_city' => 'city_id'] as $taxonomy => $field) {
            if ($this->has($input, $field) && $this->integer($input[$field]) > 0 && !term_exists($this->integer($input[$field]), $taxonomy)) {
                return new \WP_Error('location_taxonomy_invalid', __('Invalid location taxonomy term.', 'business-map-locator'));
            }
        }

        $postData = ['post_type' => 'bml_location', 'post_title' => $title, 'post_status' => $status];
        if ($this->has($input, 'content')) {
            $postData['post_content'] = wp_kses_post($this->string($input, 'content'));
        }
        if ($this->has($input, 'excerpt')) {
            $postData['post_excerpt'] = sanitize_textarea_field($this->string($input, 'excerpt'));
        }
        $result = $id > 0 ? wp_update_post(['ID' => $id] + $postData, true) : wp_insert_post($postData, true);
        if (is_wp_error($result) || (int) $result <= 0) {
            return is_wp_error($result) ? $result : new \WP_Error('location_save_failed', __('Location could not be saved.', 'business-map-locator'));
        }
        $id = (int) $result;

        foreach (['address', 'region', 'country', 'postcode', 'phone', 'email', 'website', 'hours'] as $field) {
            if ($this->has($input, $field)) {
                update_post_meta($id, 'bml_' . $field, $this->locationField($field, $this->string($input, $field)));
            }
        }
        if ($this->has($input, 'lat') || $this->has($input, 'lng')) {
            if ($coordinates['lat'] === null || $coordinates['lng'] === null) {
                delete_post_meta($id, 'bml_lat');
                delete_post_meta($id, 'bml_lng');
            } else {
                update_post_meta($id, 'bml_lat', $coordinates['lat']);
                update_post_meta($id, 'bml_lng', $coordinates['lng']);
            }
        }
        if ($this->has($input, 'operational_status')) {
            $operational = sanitize_key($this->string($input, 'operational_status'));
            $operational = $operational === 'open' ? 'active' : $operational;
            update_post_meta($id, 'bml_operational_status', in_array($operational, ['active', 'temporarily_closed', 'hidden'], true) ? $operational : 'active');
        }
        foreach (['bml_category' => 'category_id', 'bml_city' => 'city_id'] as $taxonomy => $field) {
            if ($this->has($input, $field)) {
                $terms = wp_set_object_terms($id, ($termId = $this->integer($input[$field])) > 0 ? [$termId] : [], $taxonomy);
                if (is_wp_error($terms)) {
                    return $terms;
                }
            }
        }
        if ($this->has($input, 'remove_featured_image') && $this->boolean($input['remove_featured_image'])) {
            delete_post_thumbnail($id);
        } elseif ($this->has($input, 'featured_image_id')) {
            $imageId = $this->integer($input['featured_image_id']);
            if ($imageId > 0 && get_post_type($imageId) === 'attachment') {
                set_post_thumbnail($id, $imageId);
            }
        }

        $this->index->upsert($id);
        \BML_Location_Cache::invalidate();
        return $id;
    }

    /** @param array<string, mixed> $input @return array{lat: float|null, lng: float|null}|\WP_Error */
    private function coordinates(int $id, array $input): array|\WP_Error
    {
        $latRaw = $this->has($input, 'lat') ? trim($this->string($input, 'lat')) : (string) get_post_meta($id, 'bml_lat', true);
        $lngRaw = $this->has($input, 'lng') ? trim($this->string($input, 'lng')) : (string) get_post_meta($id, 'bml_lng', true);
        $lat = $latRaw === '' ? null : filter_var($latRaw, FILTER_VALIDATE_FLOAT);
        $lng = $lngRaw === '' ? null : filter_var($lngRaw, FILTER_VALIDATE_FLOAT);
        if ($lat !== null && ($lat === false || $lat < -90 || $lat > 90)) {
            return new \WP_Error('location_latitude_invalid', __('Invalid latitude.', 'business-map-locator'));
        }
        if ($lng !== null && ($lng === false || $lng < -180 || $lng > 180)) {
            return new \WP_Error('location_longitude_invalid', __('Invalid longitude.', 'business-map-locator'));
        }
        return ['lat' => $lat === null ? null : (float) $lat, 'lng' => $lng === null ? null : (float) $lng];
    }

    /** @param array<string, mixed> $input */
    private function has(array $input, string $key): bool
    {
        return array_key_exists($key, $input) && !is_array($input[$key]);
    }

    /** @param array<string, mixed> $input */
    private function string(array $input, string $key, string $default = ''): string
    {
        return $this->has($input, $key) ? (string) $input[$key] : $default;
    }

    private function integer(mixed $value): int
    {
        return absint($value);
    }

    private function boolean(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function locationField(string $key, string $value): string
    {
        return match ($key) {
            'email' => sanitize_email($value),
            'website' => esc_url_raw($value),
            'hours' => sanitize_textarea_field($value),
            default => sanitize_text_field($value),
        };
    }
}
