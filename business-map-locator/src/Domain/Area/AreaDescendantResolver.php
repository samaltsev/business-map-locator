<?php
declare(strict_types=1);

namespace BusinessMapLocator\Domain\Area;

final class AreaDescendantResolver
{
    private const VERSION_OPTION = 'bml_area_descendants_version';

    /** @var array<int, list<int>> */
    private array $resolved = [];

    public function hooks(): void
    {
        add_action('created_bml_area', [$this, 'invalidate']);
        add_action('edited_bml_area', [$this, 'invalidate']);
        add_action('delete_bml_area', [$this, 'invalidate']);
    }

    /** @return list<int> */
    public function idsForSlug(string $slug): array
    {
        if ($slug === '') {
            return [];
        }

        foreach ($this->terms() as $term) {
            if ((string) $term->slug === $slug) {
                return $this->idsForTerm((int) $term->term_id);
            }
        }

        return [];
    }

    /** @return list<int> */
    public function idsForTerm(int $termId): array
    {
        if ($termId < 1) {
            return [];
        }
        if (isset($this->resolved[$termId])) {
            return $this->resolved[$termId];
        }

        $key = 'area-descendants-' . $this->version() . '-' . $termId;
        $cached = function_exists('wp_cache_get') ? wp_cache_get($key, 'business-map-locator') : false;
        if (is_array($cached)) {
            return $this->resolved[$termId] = array_map('intval', $cached);
        }

        $children = [];
        $known = false;
        foreach ($this->terms() as $term) {
            $id = (int) $term->term_id;
            $known = $known || $id === $termId;
            $children[(int) $term->parent][] = $id;
        }
        if (!$known) {
            return [];
        }

        $ids = [];
        $queue = [$termId];
        while ($queue !== []) {
            $current = array_shift($queue);
            $ids[] = $current;
            foreach ($children[$current] ?? [] as $child) {
                $queue[] = $child;
            }
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);
        if (function_exists('wp_cache_set')) {
            wp_cache_set($key, $ids, 'business-map-locator');
        }

        return $this->resolved[$termId] = $ids;
    }

    /** @return list<array{term_id:int,slug:string,name:string,parent:int,depth:int}> */
    public function publicOptions(): array
    {
        $terms = $this->terms();
        $parents = [];
        foreach ($terms as $term) {
            $parents[(int) $term->term_id] = (int) $term->parent;
        }
        $options = [];
        foreach ($terms as $term) {
            if ((string) get_term_meta((int) $term->term_id, 'bml_area_active', true) === '0') {
                continue;
            }
            $depth = 0;
            $parent = (int) $term->parent;
            $seen = [];
            while ($parent > 0 && isset($parents[$parent]) && !isset($seen[$parent])) {
                $seen[$parent] = true;
                $depth++;
                $parent = $parents[$parent];
            }
            $options[] = ['term_id' => (int) $term->term_id, 'slug' => (string) $term->slug, 'name' => (string) $term->name, 'parent' => (int) $term->parent, 'depth' => $depth];
        }
        usort($options, static fn (array $left, array $right): int => [$left['depth'], $left['name']] <=> [$right['depth'], $right['name']]);

        return $options;
    }

    public function invalidate(): void
    {
        $this->resolved = [];
        update_option(self::VERSION_OPTION, $this->version() + 1, false);
    }

    /** @return list<object> */
    private function terms(): array
    {
        $terms = get_terms(['taxonomy' => 'bml_area', 'hide_empty' => false]);

        return is_wp_error($terms) || !is_array($terms) ? [] : $terms;
    }

    private function version(): int
    {
        return max(1, (int) get_option(self::VERSION_OPTION, 1));
    }
}
