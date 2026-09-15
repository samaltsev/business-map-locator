<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/Database/class-bml-database.php';
require_once dirname(__DIR__) . '/includes/Database/class-bml-schema.php';
require_once dirname(__DIR__) . '/includes/Database/class-bml-location-relation-index.php';

final class RelationIndexWpdbFake
{
    public string $prefix = 'wp_';
    public bool $failTransactionStart = false;
    public ?int $failInsertAt = null;
    /** @var list<array{location_id:int,taxonomy:string,term_id:int,is_primary:int}> */
    public array $rows = [];
    /** @var list<array{location_id:int,taxonomy:string,term_id:int,is_primary:int}>|null */
    private ?array $transactionRows = null;
    private int $insertAttempts = 0;

    public function get_charset_collate(): string { return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci'; }
    public function esc_like(string $value): string { return $value; }
    public function prepare(string $query, mixed ...$values): string { return $query; }
    public function get_var(string $query): string { return $this->prefix . 'bml_location_terms'; }
    public function query(string $query): int|false
    {
        if ($query === 'START TRANSACTION') {
            if ($this->failTransactionStart) {
                return false;
            }
            $this->transactionRows = $this->rows;
            $this->insertAttempts = 0;
        } elseif ($query === 'COMMIT') {
            $this->transactionRows = null;
        } elseif ($query === 'ROLLBACK' && $this->transactionRows !== null) {
            $this->rows = $this->transactionRows;
            $this->transactionRows = null;
        }

        return 1;
    }
    public function delete(string $table, array $where, array $formats): int
    {
        $before = count($this->rows);
        $this->rows = array_values(array_filter($this->rows, static fn (array $row): bool => $row['location_id'] !== (int) $where['location_id']));

        return $before - count($this->rows);
    }
    public function insert(string $table, array $row, array $formats): int|false
    {
        $this->insertAttempts++;
        if ($this->failInsertAt === $this->insertAttempts) {
            return false;
        }
        foreach ($this->rows as $existing) {
            if ($existing['location_id'] === $row['location_id'] && $existing['taxonomy'] === $row['taxonomy'] && $existing['term_id'] === $row['term_id']) {
                return false;
            }
        }
        $this->rows[] = $row;

        return 1;
    }
}

final class LocationRelationIndexTest extends TestCase
{
    private BML_Location_Relation_Index $index;
    private RelationIndexWpdbFake $wpdb;

    protected function setUp(): void
    {
        $this->wpdb = new RelationIndexWpdbFake();
        $GLOBALS['wpdb'] = $this->wpdb;
        $GLOBALS['bml_test_post_terms'] = [];
        $this->index = new BML_Location_Relation_Index();
    }

    public function testSchemaDefinesDerivedRelationTableWithRequiredKeys(): void
    {
        $sql = BML_Schema::location_terms_sql();

        self::assertStringContainsString('CREATE TABLE wp_bml_location_terms', $sql);
        self::assertStringContainsString('location_id BIGINT UNSIGNED NOT NULL', $sql);
        self::assertStringContainsString('taxonomy VARCHAR(32) NOT NULL', $sql);
        self::assertStringContainsString('term_id BIGINT UNSIGNED NOT NULL', $sql);
        self::assertStringContainsString('is_primary TINYINT(1) NOT NULL DEFAULT 0', $sql);
        self::assertStringContainsString('UNIQUE KEY location_taxonomy_term (location_id, taxonomy, term_id)', $sql);
        self::assertStringContainsString('KEY taxonomy_term_location (taxonomy, term_id, location_id)', $sql);
        self::assertStringContainsString('KEY location_taxonomy_primary (location_id, taxonomy, is_primary)', $sql);
    }

    public function testExtractsOnlyDirectAllowlistedRelationshipsWithNoPrimaryInference(): void
    {
        $GLOBALS['bml_test_post_terms'][817] = [
            'bml_category' => [7, 2, 7],
            'bml_city' => [8],
            'bml_area' => [9],
            'post_tag' => [99],
        ];

        self::assertSame([
            ['location_id' => 817, 'taxonomy' => 'bml_category', 'term_id' => 2, 'is_primary' => 0],
            ['location_id' => 817, 'taxonomy' => 'bml_category', 'term_id' => 7, 'is_primary' => 0],
            ['location_id' => 817, 'taxonomy' => 'bml_city', 'term_id' => 8, 'is_primary' => 0],
            ['location_id' => 817, 'taxonomy' => 'bml_area', 'term_id' => 9, 'is_primary' => 0],
        ], $this->index->direct_rows(817));
    }

    public function testSyncReplacesOnlyCurrentLocationAndRemovesStaleRelationships(): void
    {
        $this->wpdb->rows = [['location_id' => 900, 'taxonomy' => 'bml_city', 'term_id' => 10, 'is_primary' => 0]];
        $GLOBALS['bml_test_post_terms'][817] = ['bml_category' => [7], 'bml_city' => [8]];

        self::assertTrue($this->index->sync(817));
        self::assertTrue($this->index->sync(817));
        $GLOBALS['bml_test_post_terms'][817] = ['bml_category' => [7], 'bml_area' => [9]];
        self::assertTrue($this->index->sync(817));

        self::assertSame([
            ['location_id' => 900, 'taxonomy' => 'bml_city', 'term_id' => 10, 'is_primary' => 0],
            ['location_id' => 817, 'taxonomy' => 'bml_category', 'term_id' => 7, 'is_primary' => 0],
            ['location_id' => 817, 'taxonomy' => 'bml_area', 'term_id' => 9, 'is_primary' => 0],
        ], $this->wpdb->rows);
    }

    public function testAreaLessLocationSynchronizesWithoutARequiredAreaRow(): void
    {
        $this->wpdb->rows = [['location_id' => 900, 'taxonomy' => 'bml_city', 'term_id' => 10, 'is_primary' => 0]];
        $GLOBALS['bml_test_post_terms'][817] = [];

        self::assertTrue($this->index->sync(817));
        self::assertSame([['location_id' => 900, 'taxonomy' => 'bml_city', 'term_id' => 10, 'is_primary' => 0]], $this->wpdb->rows);
    }

    public function testSourceReadOrTransactionFailurePreservesCurrentRows(): void
    {
        $current = [['location_id' => 817, 'taxonomy' => 'bml_city', 'term_id' => 8, 'is_primary' => 0]];
        $this->wpdb->rows = $current;
        $GLOBALS['bml_test_post_terms'][817] = ['bml_city' => new WP_Error('term_read_failed', 'Term read failed.')];
        self::assertFalse($this->index->sync(817));
        self::assertSame($current, $this->wpdb->rows);

        $GLOBALS['bml_test_post_terms'][817] = ['bml_city' => [8]];
        $this->wpdb->failTransactionStart = true;
        self::assertFalse($this->index->sync(817));
        self::assertSame($current, $this->wpdb->rows);
    }

    public function testInsertFailureRollsBackReplacementRows(): void
    {
        $current = [['location_id' => 817, 'taxonomy' => 'bml_city', 'term_id' => 8, 'is_primary' => 0]];
        $this->wpdb->rows = $current;
        $this->wpdb->failInsertAt = 2;
        $GLOBALS['bml_test_post_terms'][817] = ['bml_category' => [7], 'bml_city' => [8]];

        self::assertFalse($this->index->sync(817));
        self::assertSame($current, $this->wpdb->rows);
    }

    public function testRelationRowsUseStableTermIdsAndExistingRebuildCallsUpsert(): void
    {
        $GLOBALS['bml_test_post_terms'][817] = ['bml_city' => [8]];
        self::assertSame([['location_id' => 817, 'taxonomy' => 'bml_city', 'term_id' => 8, 'is_primary' => 0]], $this->index->direct_rows(817));

        $locationIndex = (string) file_get_contents(dirname(__DIR__) . '/includes/Database/class-bml-location-index.php');
        self::assertStringContainsString('return $this->relation_index->sync($post_id);', $locationIndex);
        self::assertStringContainsString('if ($this->upsert((int) $post_id))', $locationIndex);
    }
}
