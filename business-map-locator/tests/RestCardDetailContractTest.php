<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use BusinessMapLocator\Application\Location\SearchLocationsHandler;
use BusinessMapLocator\Infrastructure\Database\LocationRepository;
use BusinessMapLocator\Rest\LocationDetailResponseFactory;
use BusinessMapLocator\Rest\LocationResponseFactory;
use BusinessMapLocator\Rest\LocationsController;

require_once dirname(__DIR__) . '/src/Infrastructure/Database/LocationRepository.php';
require_once dirname(__DIR__) . '/src/Application/Location/SearchLocationsHandler.php';
require_once dirname(__DIR__) . '/src/Rest/LocationResponseFactory.php';
require_once dirname(__DIR__) . '/src/Rest/LocationDetailResponseFactory.php';
require_once dirname(__DIR__) . '/src/Rest/LocationsController.php';

final class RestCardDetailContractTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['bml_test_posts'] = [];
        $GLOBALS['bml_test_meta'] = [];
    }

    public function testCollectionKeepsFrontendCompatibilityAndDetailRouteIsPubliclySafe(): void
    {
        $root = dirname(__DIR__);
        $controller = (string) file_get_contents($root . '/src/Rest/LocationsController.php');
        $card = (string) file_get_contents($root . '/src/Rest/LocationResponseFactory.php');
        $detail = (string) file_get_contents($root . '/src/Rest/LocationDetailResponseFactory.php');

        self::assertStringContainsString("'/locations/(?P<id>[1-9][0-9]*)'", $controller);
        self::assertStringContainsString("post_status !== 'publish'", $controller);
        self::assertStringContainsString('hasValidCoordinates', $controller);
        foreach (['image', 'website', 'hours', 'category', 'city', 'distance'] as $key) {
            self::assertStringContainsString("'{$key}'", $card);
        }
        self::assertStringContainsString("'content' => apply_filters('the_content'", $detail);
        self::assertStringNotContainsString('get_post_meta($id)', $detail);
    }

    /** @dataProvider publicOperationalStatuses */
    public function testPublishedLocationsWithPublicOperationalStatusesRemainAccessible(string $status, string $expectedStatus): void
    {
        $result = $this->showPublishedLocation($status);

        self::assertInstanceOf(WP_REST_Response::class, $result);
        self::assertSame(200, $result->get_status());
        self::assertSame($expectedStatus, $result->get_data()['operational_status']);
    }

    /** @return array<string, array{string, string}> */
    public static function publicOperationalStatuses(): array
    {
        return [
            'active' => ['active', 'active'],
            'temporarily closed' => ['temporarily_closed', 'temporarily_closed'],
            'legacy open' => ['open', 'active'],
            'missing' => ['', 'active'],
        ];
    }

    public function testHiddenPublishedLocationUsesThePublicNotFoundContract(): void
    {
        $result = $this->showPublishedLocation('hidden');

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame('bml_location_not_found', $result->get_error_code());
        self::assertSame(404, $result->get_error_data()['status']);
    }

    public function testLegacyHiddenPublishedLocationUsesThePublicNotFoundContract(): void
    {
        $result = $this->showPublishedLocation('', 'publish', '0');

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame('bml_location_not_found', $result->get_error_code());
        self::assertSame(404, $result->get_error_data()['status']);
    }

    public function testDraftLocationUsesThePublicNotFoundContract(): void
    {
        $result = $this->showPublishedLocation('active', 'draft');

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame('bml_location_not_found', $result->get_error_code());
        self::assertSame(404, $result->get_error_data()['status']);
    }

    private function showPublishedLocation(string $operationalStatus, string $postStatus = 'publish', string $legacyVisible = ''): WP_REST_Response|WP_Error
    {
        $post = new WP_Post();
        $post->ID = 101;
        $post->post_type = 'bml_location';
        $post->post_status = $postStatus;
        $post->post_title = 'Example location';

        $GLOBALS['bml_test_posts'][101] = $post;
        $GLOBALS['bml_test_meta'][101] = [
            'bml_operational_status' => $operationalStatus,
            'bml_visible' => $legacyVisible,
            'bml_lat' => '53.9',
            'bml_lng' => '27.56',
        ];

        $controller = new LocationsController(
            new SearchLocationsHandler(new LocationRepository()),
            new LocationResponseFactory(),
            new LocationDetailResponseFactory(),
            new LocationRepository()
        );

        return $controller->show(new WP_REST_Request(['id' => 101]));
    }
}
