<?php
declare(strict_types=1);

use BusinessMapLocator\Admin\Location\LocationWriteService;
use PHPUnit\Framework\TestCase;

final class LocationWriteServiceTest extends TestCase
{
    private LocationWriteService $writer;

    protected function setUp(): void
    {
        $post = new WP_Post();
        $post->ID = 42; $post->post_type = 'bml_location'; $post->post_status = 'publish';
        $post->post_title = 'Test Location'; $post->post_content = 'Existing full description'; $post->post_excerpt = 'Existing excerpt';
        $GLOBALS['bml_test_posts'] = [42 => $post];
        $GLOBALS['bml_test_meta'] = [42 => ['bml_lat' => 53.9, 'bml_lng' => 27.56, 'bml_email' => 'info@example.com', 'bml_website' => 'https://example.com', 'bml_hours' => 'Mon-Fri', 'bml_operational_status' => 'active']];
        $GLOBALS['bml_test_terms'] = ['bml_category' => [7 => true], 'bml_city' => [8 => true]];
        $GLOBALS['bml_test_object_terms'] = [42 => ['bml_category' => [7], 'bml_city' => [8]]];
        $GLOBALS['bml_test_indexed'] = []; $GLOBALS['bml_test_cache_invalidations'] = 0;
        $this->writer = new LocationWriteService(new BML_Location_Index());
    }

    public function testPartialAutosavePreservesUnsubmittedFieldsAndTerms(): void
    {
        self::assertSame(42, $this->writer->save(42, ['title' => 'Renamed', 'status' => 'draft', 'phone' => '+123456']));
        $post = $GLOBALS['bml_test_posts'][42];
        self::assertSame('Existing full description', $post->post_content);
        self::assertSame('Existing excerpt', $post->post_excerpt);
        self::assertSame('info@example.com', $GLOBALS['bml_test_meta'][42]['bml_email']);
        self::assertSame('https://example.com', $GLOBALS['bml_test_meta'][42]['bml_website']);
        self::assertSame('Mon-Fri', $GLOBALS['bml_test_meta'][42]['bml_hours']);
        self::assertSame([7], $GLOBALS['bml_test_object_terms'][42]['bml_category']);
        self::assertSame([8], $GLOBALS['bml_test_object_terms'][42]['bml_city']);
    }

    public function testExplicitUpdatesAndEmptyValueAreApplied(): void
    {
        self::assertSame(42, $this->writer->save(42, ['title' => 'Test Location', 'status' => 'draft', 'email' => '', 'website' => 'https://new.example.com', 'hours' => 'Sat 10:00-14:00', 'content' => 'Updated']));
        self::assertSame('', $GLOBALS['bml_test_meta'][42]['bml_email']);
        self::assertSame('https://new.example.com', $GLOBALS['bml_test_meta'][42]['bml_website']);
        self::assertSame('Sat 10:00-14:00', $GLOBALS['bml_test_meta'][42]['bml_hours']);
        self::assertSame('Updated', $GLOBALS['bml_test_posts'][42]->post_content);
    }

    /** @dataProvider statuses */
    public function testNormalizesOperationalStatus(string $input, string $expected): void
    {
        self::assertSame(42, $this->writer->save(42, ['title' => 'Test Location', 'status' => 'draft', 'operational_status' => $input]));
        self::assertSame($expected, $GLOBALS['bml_test_meta'][42]['bml_operational_status']);
    }

    public static function statuses(): array { return [['active', 'active'], ['temporarily_closed', 'temporarily_closed'], ['hidden', 'hidden'], ['open', 'active']]; }

    public function testPublishCoordinateValidationDoesNotWriteOrIndexInvalidData(): void
    {
        $result = $this->writer->save(42, ['title' => 'Corrupt me', 'status' => 'publish', 'lat' => '91', 'lng' => '27.56']);
        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame('Test Location', $GLOBALS['bml_test_posts'][42]->post_title);
        self::assertSame([], $GLOBALS['bml_test_indexed']);
        self::assertSame(0, $GLOBALS['bml_test_cache_invalidations']);
    }

    public function testPublishRequiresCoordinatesWhileDraftMayOmitThem(): void
    {
        self::assertInstanceOf(WP_Error::class, $this->writer->save(0, ['title' => 'No map', 'status' => 'publish']));
        self::assertIsInt($this->writer->save(0, ['title' => 'Draft without map', 'status' => 'draft']));
    }

    public function testSuccessfulWriteSynchronizesIndexAndInvalidatesCache(): void
    {
        self::assertSame(42, $this->writer->save(42, ['title' => 'Indexed', 'status' => 'publish', 'lat' => '54', 'lng' => '28']));
        self::assertSame([42], $GLOBALS['bml_test_indexed']);
        self::assertSame(1, $GLOBALS['bml_test_cache_invalidations']);
    }
}
