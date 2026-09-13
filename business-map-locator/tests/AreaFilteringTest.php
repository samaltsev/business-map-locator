<?php
declare(strict_types=1);

use BusinessMapLocator\Application\Location\SearchLocationsQuery;
use BusinessMapLocator\Domain\Area\AreaDescendantResolver;
use BusinessMapLocator\Infrastructure\Database\LocationRepository;
use PHPUnit\Framework\TestCase;

if (!defined('ARRAY_A')) { define('ARRAY_A', 'ARRAY_A'); }
if (!function_exists('add_action')) { function add_action(string $hook, callable $callback): void { $GLOBALS['bml_test_actions'][$hook][] = $callback; } }
function area_filtering_do_action(string $hook): void { foreach ($GLOBALS['bml_test_actions'][$hook] ?? [] as $callback) { $callback(); } }

final class AreaFilteringTest extends TestCase
{
    private AreaDescendantResolver $areas;

    protected function setUp(): void
    {
        $this->areas = new AreaDescendantResolver();
        $GLOBALS['bml_test_terms'] = ['bml_area' => [
            1 => $this->term(1, 'Country', 'country'), 2 => $this->term(2, 'Region A', 'region-a', 1),
            3 => $this->term(3, 'City A', 'city-a', 2), 4 => $this->term(4, 'District A1', 'district-a1', 3),
            5 => $this->term(5, 'District A2', 'district-a2', 3), 6 => $this->term(6, 'City B', 'city-b', 2),
            7 => $this->term(7, 'Region B', 'region-b', 1), 8 => $this->term(8, 'City C', 'city-c', 7),
            9 => $this->term(9, 'Empty', 'empty'), 10 => $this->term(10, 'Inactive', 'inactive'),
        ]];
        $GLOBALS['bml_test_term_meta'] = [10 => ['bml_area_active' => '0']];
        $GLOBALS['bml_test_options'] = [];
        $GLOBALS['bml_test_actions'] = [];
    }

    public function testLeafAndEveryAncestorDepthResolveOnlyTheirOwnSubtree(): void
    {
        self::assertSame([4], $this->areas->idsForSlug('district-a1'));
        self::assertSame([2, 3, 4, 5, 6], $this->areas->idsForSlug('region-a'));
        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8], $this->areas->idsForSlug('country'));
        self::assertSame([], $this->areas->idsForSlug('unknown'));
        self::assertSame([9], $this->areas->idsForSlug('empty'));
    }

    public function testInvalidationRefreshesCreateReparentAndDelete(): void
    {
        $this->areas->hooks();
        self::assertSame([2, 3, 4, 5, 6], $this->areas->idsForTerm(2));
        $GLOBALS['bml_test_terms']['bml_area'][11] = $this->term(11, 'New', 'new', 6); area_filtering_do_action('created_bml_area');
        self::assertContains(11, $this->areas->idsForTerm(2));
        $GLOBALS['bml_test_terms']['bml_area'][6]->parent = 7; area_filtering_do_action('edited_bml_area');
        self::assertNotContains(6, $this->areas->idsForTerm(2)); self::assertContains(6, $this->areas->idsForTerm(7));
        unset($GLOBALS['bml_test_terms']['bml_area'][11]); area_filtering_do_action('delete_bml_area');
        self::assertNotContains(11, $this->areas->idsForTerm(7));
    }

    public function testPublicOptionsExposeHierarchyWithoutInactiveTermsOrCounts(): void
    {
        $options = $this->areas->publicOptions();
        self::assertSame(['term_id' => 4, 'slug' => 'district-a1', 'name' => 'District A1', 'parent' => 3, 'depth' => 3], $this->option($options, 4));
        self::assertNull($this->option($options, 10)); self::assertArrayNotHasKey('count', $this->option($options, 4));
    }

    public function testAreaIsSlugBasedAndIncludedInTheSharedQueryCacheKey(): void
    {
        $query = SearchLocationsQuery::fromArray(['area' => 'region-a', 'category' => 'x', 'city' => 'y']);
        self::assertSame('region-a', $query->area); self::assertSame('region-a', $query->cacheKey()['area']);
    }

    public function testRepositoryAndRestEndpointsUseOneAreaFilterPath(): void
    {
        $repository = (string) file_get_contents(dirname(__DIR__) . '/src/Infrastructure/Database/LocationRepository.php');
        $controller = (string) file_get_contents(dirname(__DIR__) . '/src/Rest/LocationsController.php');
        $filters = (string) file_get_contents(dirname(__DIR__) . '/includes/REST/class-bml-rest.php');
        self::assertStringContainsString('idsForSlug($area)', $repository);
        self::assertStringContainsString("taxonomy = 'bml_area'", $repository);
        self::assertStringContainsString("'area' =>", $controller);
        self::assertStringContainsString('$this->areas->publicOptions()', $filters);
        self::assertStringNotContainsString('wp_set_object_terms', $repository);
        self::assertStringNotContainsString('rebuild(', $repository);
    }

    public function testRepositoryAppliesAreaSubtreeToLocationsMarkersAndBounds(): void
    {
        require_once dirname(__DIR__) . '/includes/Database/class-bml-database.php';
        $wpdb = new AreaFilteringWpdbFake(); $GLOBALS['wpdb'] = $wpdb;
        $repository = new LocationRepository($this->areas);

        $repository->search(SearchLocationsQuery::fromArray(['area' => 'region-a', 'category' => 'x', 'city' => 'y', 'search' => 'needle']));
        self::assertStringContainsString("taxonomy = 'bml_area'", $wpdb->lastQuery());
        self::assertStringContainsString('term_id IN (%d, %d, %d, %d, %d)', $wpdb->lastQuery());
        self::assertStringContainsString('category_slug = %s', $wpdb->lastQuery()); self::assertStringContainsString('city_slug = %s', $wpdb->lastQuery());
        self::assertStringContainsString("visibility = 'public'", $wpdb->lastQuery()); self::assertStringContainsString("operational_status <> 'hidden'", $wpdb->lastQuery());
        self::assertStringContainsString('SELECT COUNT(1) FROM', implode("\n", $wpdb->queries));

        $repository->markers(55, 50, 30, 20, '', '', 'region-a');
        self::assertStringContainsString("taxonomy = 'bml_area'", $wpdb->lastQuery());
        $repository->publicBounds('', '', 'region-a');
        self::assertStringContainsString("taxonomy = 'bml_area'", $wpdb->lastQuery());
    }

    public function testUnknownAreaCompilesTheExistingEmptyResultPolicy(): void
    {
        require_once dirname(__DIR__) . '/includes/Database/class-bml-database.php';
        $wpdb = new AreaFilteringWpdbFake(); $GLOBALS['wpdb'] = $wpdb;
        (new LocationRepository($this->areas))->search(SearchLocationsQuery::fromArray(['area' => 'unknown']));
        self::assertStringContainsString('1 = 0', $wpdb->lastQuery());
    }

    private function term(int $id, string $name, string $slug, int $parent = 0): object { return (object) ['term_id' => $id, 'name' => $name, 'slug' => $slug, 'parent' => $parent, 'count' => 0]; }
    /** @param list<array{term_id:int,slug:string,name:string,parent:int,depth:int}> $options */
    private function option(array $options, int $id): ?array { foreach ($options as $option) if ($option['term_id'] === $id) return $option; return null; }
}

final class AreaFilteringWpdbFake
{
    public string $prefix = 'wp_';
    /** @var list<string> */ public array $queries = [];
    public function prepare(string $query, mixed ...$values): string { $this->queries[] = $query; return $query; }
    public function get_var(string $query): int { $this->queries[] = $query; return 0; }
    public function get_results(string $query, mixed $output = null): array { $this->queries[] = $query; return []; }
    public function get_row(string $query, mixed $output = null): array { $this->queries[] = $query; return ['total' => 0]; }
    public function esc_like(string $value): string { return $value; }
    public function lastQuery(): string { return (string) end($this->queries); }
}
