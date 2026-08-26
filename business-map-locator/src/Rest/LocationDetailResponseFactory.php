<?php
declare(strict_types=1);

namespace BusinessMapLocator\Rest;

use WP_Post;

final readonly class LocationDetailResponseFactory
{
    /** @return array<string, mixed> */
    public function create(WP_Post $post): array
    {
        $id = (int) $post->ID;
        return [
            'id' => $id,
            'title' => (string) $post->post_title,
            'permalink' => (string) get_permalink($id),
            'excerpt' => (string) $post->post_excerpt,
            'content' => apply_filters('the_content', $post->post_content),
            'address' => $this->meta($id, 'bml_address'),
            'region' => $this->meta($id, 'bml_region'),
            'country' => $this->meta($id, 'bml_country'),
            'postcode' => $this->meta($id, 'bml_postcode'),
            'lat' => (float) get_post_meta($id, 'bml_lat', true),
            'lng' => (float) get_post_meta($id, 'bml_lng', true),
            'phone' => $this->meta($id, 'bml_phone'),
            'email' => $this->meta($id, 'bml_email'),
            'website' => $this->meta($id, 'bml_website'),
            'hours' => $this->meta($id, 'bml_hours'),
            'image' => (string) (get_the_post_thumbnail_url($id, 'medium') ?: ''),
            'operational_status' => $this->status($this->meta($id, 'bml_operational_status')),
            'category' => $this->term($id, 'bml_category'),
            'city' => $this->term($id, 'bml_city'),
        ];
    }

    private function meta(int $id, string $key): string { return trim((string) get_post_meta($id, $key, true)); }
    private function status(string $status): string { $status = $status === 'open' ? 'active' : $status; return in_array($status, ['active', 'temporarily_closed', 'hidden'], true) ? $status : 'active'; }
    /** @return array{name:string,slug:string}|null */
    private function term(int $id, string $taxonomy): ?array { $terms = wp_get_post_terms($id, $taxonomy); if (is_wp_error($terms) || empty($terms)) { return null; } return ['name' => (string) $terms[0]->name, 'slug' => (string) $terms[0]->slug]; }
}
