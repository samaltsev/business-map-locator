<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Taxonomy;
if (!defined('ABSPATH')) { exit; }
final class TaxonomyOptionsProvider
{
    public function options(string $taxonomy, string $valueField = 'slug'): array
    {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        if (is_wp_error($terms)) { return []; }
        return array_map(static fn ($term): array => [
            'value' => $valueField === 'id' ? (string) $term->term_id : (string) $term->slug,
            'label' => (string) $term->name,
        ], $terms);
    }
}
