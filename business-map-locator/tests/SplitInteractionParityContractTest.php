<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SplitInteractionParityContractTest extends TestCase
{
    public function testCitySelectionUsesAggregateBoundsThenOnlyRefreshesViewportResults(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertStringContainsString("'locations/bounds?'", $controller);
        self::assertStringContainsString("params.set('city', city);", $controller);
        self::assertStringContainsString('LocatorController.prototype.centerOnSelectedCity', $controller);
        self::assertStringContainsString('LocatorController.prototype.fitAggregateBounds', $controller);
        self::assertStringContainsString('provider.fitCoordinates([{ lat: south, lng: west }, { lat: north, lng: east }])', $controller);
        self::assertStringContainsString('provider.focusCoordinates(centerLat, centerLng, 14)', $controller);
        self::assertStringContainsString('self.refreshViewport();', $controller);
        self::assertStringNotContainsString('fitBounds(this.state.items', $controller);
        self::assertStringNotContainsString('focusFilteredLocations', $controller);
    }

    public function testFreshMarkersRetainTheDetailSelectionCallback(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');
        $provider = (string) file_get_contents(dirname(__DIR__) . '/assets/js/providers/openstreetmap-provider.js');
        $rest = (string) file_get_contents(dirname(__DIR__) . '/src/Rest/LocationsController.php');

        self::assertStringContainsString('self.handleMarkerSelection(location);', $controller);
        self::assertStringContainsString('LocatorController.prototype.handleMarkerSelection', $controller);
        self::assertStringContainsString("this.selectLocation(location.id, 'marker')", $controller);
        self::assertStringContainsString("marker.on('click', function ()", $provider);
        self::assertStringContainsString('onSelect(location);', $provider);
        self::assertStringContainsString('self.createMarker(', $provider);
        self::assertStringContainsString('onSelect,', $provider);
        self::assertStringContainsString("post_status !== 'publish'", $rest);
        self::assertStringContainsString('hasHiddenOperationalStatus', $rest);
        self::assertStringNotContainsString('zoomToShowLayer', $provider);
        self::assertStringContainsString('maxZoom: 19', $provider);
        self::assertStringContainsString('maxNativeZoom: 19', $provider);
    }
}
