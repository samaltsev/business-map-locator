<?php
declare(strict_types=1);

use BusinessMapLocator\Application\Location\SearchLocationsQuery;
use BusinessMapLocator\Application\Location\SearchLocationsHandler;
use BusinessMapLocator\Domain\Area\AreaDescendantResolver;
use BusinessMapLocator\Infrastructure\Database\LocationRepository;
use BusinessMapLocator\Rest\LocationDetailResponseFactory;
use BusinessMapLocator\Rest\LocationResponseFactory;
use BusinessMapLocator\Rest\LocationsController;
use PHPUnit\Framework\TestCase;

if (!defined('ARRAY_A')) { define('ARRAY_A', 'ARRAY_A'); }
if (!function_exists('sanitize_title')) { function sanitize_title(string $value): string { return strtolower(trim($value)); } }

final class FilterCountsTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['bml_test_options'] = [];
        $GLOBALS['bml_test_term_meta'] = [];
        $GLOBALS['bml_test_terms'] = [
            'bml_category' => [1 => $this->term(1, 'Category X', 'x'), 2 => $this->term(2, 'Category Y', 'y')],
            'bml_city' => [3 => $this->term(3, 'City X', 'city-x'), 4 => $this->term(4, 'City Y', 'city-y')],
            'bml_area' => [10 => $this->term(10, 'Country', 'country'), 11 => $this->term(11, 'Region', 'region', 10), 12 => $this->term(12, 'Leaf', 'leaf', 11), 13 => $this->term(13, 'Empty', 'empty')],
        ];
    }

    public function testGroupedRelationIndexCountsAggregateAncestorsAndKeepEmptyAreas(): void
    {
        require_once dirname(__DIR__) . '/includes/Database/class-bml-database.php';
        $wpdb = new FilterCountsWpdbFake([[1 => 2, 2 => 1], [3 => 2, 4 => 1], [12 => 3]], 2);
        $GLOBALS['wpdb'] = $wpdb;

        $counts = (new LocationRepository(new AreaDescendantResolver()))->filterCounts();

        self::assertSame(2, $this->option($counts['categories'], 1)['count']);
        self::assertSame(2, $this->option($counts['cities'], 3)['count']);
        self::assertSame(3, $this->option($counts['areas'], 12)['count']);
        self::assertSame(3, $this->option($counts['areas'], 11)['count']);
        self::assertSame(3, $this->option($counts['areas'], 10)['count']);
        self::assertSame(0, $this->option($counts['areas'], 13)['count']);
        self::assertFalse($this->option($counts['areas'], 13)['available']);
        self::assertSame(['count' => 2, 'available' => true], $counts['without_area']);
    }

    public function testCountsUseBoundedGroupedIndexQueriesAndCanonicalContext(): void
    {
        require_once dirname(__DIR__) . '/includes/Database/class-bml-database.php';
        $wpdb = new FilterCountsWpdbFake([[], [], []], 0); $GLOBALS['wpdb'] = $wpdb;
        (new LocationRepository(new AreaDescendantResolver()))->filterCounts('x', 'city-x', 'region', false, 'needle');

        self::assertCount(3, $wpdb->resultsQueries);
        self::assertStringContainsString("r.taxonomy = %s", $wpdb->resultsQueries[0]);
        self::assertStringContainsString("taxonomy = 'bml_area'", $wpdb->resultsQueries[0]);
        self::assertStringContainsString('l.category_slug = %s', $wpdb->resultsQueries[1]);
        self::assertStringContainsString('l.city_slug = %s', $wpdb->resultsQueries[0]);
        self::assertStringContainsString('l.search_text LIKE %s', $wpdb->resultsQueries[0]);
        self::assertStringContainsString("NOT EXISTS", $wpdb->varQuery);
    }

    public function testWithoutAreaUsesDirectRelationIndexForSearchMarkersAndBounds(): void
    {
        require_once dirname(__DIR__) . '/includes/Database/class-bml-database.php';
        $wpdb = new FilterCountsWpdbFake([], 0); $GLOBALS['wpdb'] = $wpdb;
        $repository = new LocationRepository(new AreaDescendantResolver());
        $repository->search(SearchLocationsQuery::fromArray(['without_area' => '1']));
        self::assertStringContainsString('NOT EXISTS', $wpdb->lastQuery());
        $repository->markers(55, 50, 30, 20, '', '', '', '', true);
        self::assertStringContainsString('NOT EXISTS', $wpdb->lastQuery());
        $repository->publicBounds('', '', '', '', true);
        self::assertStringContainsString('NOT EXISTS', $wpdb->lastQuery());
    }

    public function testWithoutAreaIsPartOfTheSharedQueryCacheKey(): void
    {
        $query = SearchLocationsQuery::fromArray(['without_area' => '1']);
        self::assertTrue($query->withoutArea);
        self::assertTrue($query->cacheKey()['without_area']);
    }

    public function testLocationsMarkersAndBoundsRejectConflictingAreaFilters(): void
    {
        $repository = new LocationRepository(new AreaDescendantResolver());
        $controller = new LocationsController(new SearchLocationsHandler($repository), new LocationResponseFactory(), new LocationDetailResponseFactory(), $repository);
        $requests = [
            fn (): WP_Error|WP_REST_Response => $controller->index(new WP_REST_Request(['area' => 'leaf', 'without_area' => '1'])),
            fn (): WP_Error|WP_REST_Response => $controller->markers(new WP_REST_Request(['north' => 55, 'south' => 50, 'east' => 30, 'west' => 20, 'area' => 'leaf', 'without_area' => '1'])),
            fn (): WP_Error|WP_REST_Response => $controller->bounds(new WP_REST_Request(['area' => 'leaf', 'without_area' => '1'])),
        ];
        foreach ($requests as $request) {
            $result = $request();
            self::assertInstanceOf(WP_Error::class, $result);
            self::assertSame('bml_conflicting_area_filters', $result->get_error_code());
            self::assertSame(400, $result->get_error_data()['status']);
        }
    }

    private function term(int $id, string $name, string $slug, int $parent = 0): object { return (object) ['term_id' => $id, 'name' => $name, 'slug' => $slug, 'parent' => $parent, 'count' => 0]; }
    private function option(array $options, int $id): array { foreach ($options as $option) if ($option['term_id'] === $id) return $option; self::fail('Missing option.'); }
}

final class FilterCountsWpdbFake
{
    public string $prefix = 'wp_';
    /** @var list<array<int,int>> */ private array $grouped;
    /** @var list<string> */ public array $resultsQueries = [];
    public string $varQuery = '';
    /** @var list<string> */ private array $queries = [];
    public function __construct(array $grouped, private int $withoutAreaCount) { $this->grouped = $grouped; }
    public function prepare(string $query, mixed ...$values): string { $this->queries[] = $query; return $query; }
    public function get_results(string $query, mixed $output = null): array { $this->resultsQueries[] = $query; $counts = array_shift($this->grouped) ?? []; $rows = []; foreach ($counts as $termId => $count) $rows[] = ['term_id' => $termId, 'count' => $count]; return $rows; }
    public function get_var(string $query): int { $this->varQuery = $query; $this->queries[] = $query; return $this->withoutAreaCount; }
    public function get_row(string $query, mixed $output = null): array { $this->queries[] = $query; return ['total' => 0]; }
    public function esc_like(string $value): string { return $value; }
    public function lastQuery(): string { return (string) end($this->queries); }
}
