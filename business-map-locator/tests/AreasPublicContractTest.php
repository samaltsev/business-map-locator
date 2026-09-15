<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AreasPublicContractTest extends TestCase
{
    public function testAreaOwnsPublicLocatorContractWhileCityRemainsCompatibilityAlias(): void
    {
        $root = dirname(__DIR__);
        $query = (string) file_get_contents($root . '/src/Application/Location/SearchLocationsQuery.php');
        $controller = (string) file_get_contents($root . '/src/Rest/LocationsController.php');
        $filters = (string) file_get_contents($root . '/includes/REST/class-bml-rest.php');
        $card = (string) file_get_contents($root . '/src/Rest/LocationResponseFactory.php');
        $detail = (string) file_get_contents($root . '/src/Rest/LocationDetailResponseFactory.php');
        $shortcode = (string) file_get_contents($root . '/includes/Shortcodes/class-bml-shortcode.php');
        $renderer = (string) file_get_contents($root . '/includes/Frontend/class-bml-locator-renderer.php');
        $locator = (string) file_get_contents($root . '/templates/frontend/locator.php');
        $toolbar = (string) file_get_contents($root . '/templates/frontend/toolbar.php');
        $block = (string) file_get_contents($root . '/includes/Blocks/business-locator/block.json');
        $editor = (string) file_get_contents($root . '/includes/Blocks/business-locator/index.js');

        self::assertStringContainsString('AreaCompatibilityResolver::normalize', $query);
        self::assertStringContainsString('AreaCompatibilityResolver::normalize', $controller);
        self::assertStringContainsString("\$payload['cities'] = \$payload['areas']", $filters);
        self::assertStringContainsString("'area' =>", $card);
        self::assertStringContainsString("'area' =>", $detail);
        self::assertStringContainsString("'area' => ''", $shortcode);
        self::assertStringContainsString("'area' => ''", $renderer);
        self::assertStringContainsString('data-area=', $locator);
        self::assertStringContainsString('bml-area-filter', $toolbar);
        self::assertStringContainsString('bml-city-filter', $toolbar);
        self::assertStringContainsString('"area"', $block);
        self::assertStringContainsString('Area slug', $editor);
        self::assertStringContainsString('"city"', $block);
    }

    public function testGeocodingCityRemainsAddressContextAndIsNotTerritoryRenamed(): void
    {
        $rest = (string) file_get_contents(dirname(__DIR__) . '/includes/REST/class-bml-rest.php');

        self::assertStringContainsString("'city' => ['sanitize_callback' => 'sanitize_text_field']", $rest);
        self::assertStringContainsString("'city' => \$a['city']", $rest);
    }
}
