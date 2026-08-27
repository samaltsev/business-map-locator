<?php
declare(strict_types=1);

namespace BusinessMapLocator\Rest;

use BusinessMapLocator\Application\Location\SearchLocationsQuery;
use BusinessMapLocator\Support\OperationalStatusResolver;
use WP_REST_Response;

final readonly class LocationResponseFactory
{
    /**
     * @param array{items: list<array<string, mixed>>, total: int} $result
     */
    public function create(array $result, SearchLocationsQuery $query): WP_REST_Response
    {
        $items = $this->items($result['items']);
        $payload = [
            'items' => $items,
            'pagination' => [
                'page' => $query->page,
                'perPage' => $query->perPage,
                'total' => $result['total'],
                'totalPages' => (int) ceil($result['total'] / $query->perPage),
            ],
        ];

        $response = rest_ensure_response($payload);
        $response->header('X-WP-Total', (string) $payload['pagination']['total']);
        $response->header('X-WP-TotalPages', (string) $payload['pagination']['totalPages']);

        return $response;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function items(array $items): array
    {
        return array_map(fn (array $item): array => $this->item($item), $items);
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function item(array $item): array
    {
        $postId = (int) ($item['id'] ?? 0);
        $phone = $this->value($item, 'phone', $postId, 'bml_phone');
        $operationalStatus = OperationalStatusResolver::resolve(
            $this->value($item, 'operational_status', $postId, 'bml_operational_status'),
            $this->meta($postId, 'bml_visible')
        );
        return [
            'id' => $postId,
            'title' => $this->string($item['title'] ?? ''),
            'excerpt' => $this->string($item['excerpt'] ?? ''),
            'address' => $this->string($item['address'] ?? ''),
            'region' => $this->string($item['region'] ?? ''),
            'country' => $this->string($item['country'] ?? ''),
            'postcode' => $this->string($item['postcode'] ?? ''),
            'lat' => isset($item['lat']) ? (float) $item['lat'] : null,
            'lng' => isset($item['lng']) ? (float) $item['lng'] : null,
            'phone' => $phone,
            'email' => $this->value($item, 'email', $postId, 'bml_email'),
            'website' => $this->value($item, 'website', $postId, 'bml_website'),
            'image' => $this->string($item['image'] ?? '') ?: $this->image($postId),
            'hours' => $this->value($item, 'hours', $postId, 'bml_hours'),
            'operational_status' => $operationalStatus,
            'whatsapp' => '',
            'telegram' => '',
            'viber' => '',
            'facebook' => '',
            'instagram' => '',
            'linkedin' => '',
            'tiktok' => '',
            'category' => $this->term($item['category'] ?? null, $postId, 'bml_category'),
            'city' => $this->term($item['city'] ?? null, $postId, 'bml_city'),
            'distance' => array_key_exists('distance', $item) && $item['distance'] !== null ? round((float) $item['distance'], 3) : null,
        ];
    }

    private function meta(int $postId, string $key): string
    {
        if ($postId <= 0) {
            return '';
        }

        return $this->string(get_post_meta($postId, $key, true));
    }

    /** @param array<string, mixed> $item */
    private function value(array $item, string $field, int $postId, string $metaKey): string
    {
        return $this->string($item[$field] ?? '') ?: $this->meta($postId, $metaKey);
    }

    private function image(int $postId): string
    {
        if ($postId <= 0) {
            return '';
        }

        return (string) (get_the_post_thumbnail_url($postId, 'medium') ?: '');
    }

    /**
     * @return array<string, string|int>|null
     */
    private function term(mixed $value, int $postId, string $taxonomy): ?array
    {
        if (is_array($value) && !empty($value['name'])) {
            $term = [
                'name' => $this->string($value['name']),
                'slug' => $this->string($value['slug'] ?? ''),
            ];
            return $this->withCategoryIcon($term, $taxonomy);
        }

        if ($postId <= 0) {
            return null;
        }

        $terms = wp_get_post_terms($postId, $taxonomy);
        if (is_wp_error($terms) || empty($terms)) {
            return null;
        }

        $term = $terms[0];
        return $this->withCategoryIcon([
            'name' => $term->name,
            'slug' => $term->slug,
        ], $taxonomy, (int) $term->term_id);
    }

    /**
     * @param array<string, string|int> $term
     * @return array<string, string|int>
     */
    private function withCategoryIcon(array $term, string $taxonomy, int $termId = 0): array
    {
        if ($taxonomy !== 'bml_category') {
            return $term;
        }

        $icon = $this->categoryIcon($termId, (string) ($term['slug'] ?? ''));
        if ($icon !== '') {
            $term['icon'] = $icon;
        }

        return $term;
    }

    private function categoryIcon(int $termId, string $slug): string
    {
        static $cache = [];

        $key = $termId > 0 ? 'id:' . $termId : 'slug:' . $slug;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        if ($termId <= 0 && $slug !== '') {
            $term = get_term_by('slug', $slug, 'bml_category');
            if ($term && !is_wp_error($term)) {
                $termId = (int) $term->term_id;
            }
        }

        $iconId = $termId > 0 ? (int) get_term_meta($termId, 'bml_icon_id', true) : 0;
        if ($iconId <= 0) {
            return $cache[$key] = '';
        }

        return $cache[$key] = (string) (
            wp_get_attachment_image_url($iconId, 'bml_category_icon')
            ?: wp_get_attachment_image_url($iconId, 'thumbnail')
            ?: wp_get_attachment_image_url($iconId, 'full')
            ?: ''
        );
    }

    private function string(mixed $value): string
    {
        return trim((string) $value);
    }
}
