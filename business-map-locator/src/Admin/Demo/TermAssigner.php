<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Demo;
if (!defined('ABSPATH')) { exit; }
final class TermAssigner
{
    public function assign(int $post_id, string $name, string $taxonomy): void {
        $name = trim($name);
        if ($name === '' || !in_array($taxonomy, ['bml_category', 'bml_city'], true)) {
            return;
        }

        $term = term_exists($name, $taxonomy);
        if (!$term) {
            $term = wp_insert_term($name, $taxonomy, [
                'slug' => \BusinessMapLocator\Support\SlugGenerator::fromTerm($name),
            ]);
        }

        if (!is_wp_error($term)) {
            $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
            wp_set_object_terms($post_id, [$term_id], $taxonomy);
        }
    }
}
