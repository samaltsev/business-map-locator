<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FrontendMarkerViewportContractTest extends TestCase
{
    public function testFrontendMarkersRemainViewportDrivenAndIndependentFromDirectoryPagination(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertStringContainsString('LocatorController.prototype.currentMapBounds', $controller);
        self::assertStringContainsString('this.data.loadMarkers(bounds, this.state.user)', $controller);
        self::assertStringContainsString('self.state.markers = data.items || [];', $controller);
        self::assertStringContainsString('if (sequence !== self.state.markerSequence) { return; }', $controller);
        self::assertStringContainsString('provider.replaceMarkers(this.locations', $controller);
        self::assertStringNotContainsString('markerLocationsMatch', $controller);
        self::assertStringNotContainsString('BML marker debug', $controller);
        self::assertStringNotContainsString('BML MARKER DEBUG', $controller);
        self::assertStringContainsString('appendNearParams(params, this.settings, origin);', $controller);
        self::assertStringContainsString('self.map.provider.onBoundsChanged(debounce(function () { self.refreshViewport(); }, 300))', $controller);
        self::assertStringContainsString('return this.load(this.state.page + 1, true);', $controller);
        self::assertStringNotContainsString('loadAllDirectory', $controller);
        self::assertStringNotContainsString('directoryBatchSize', $controller);
        self::assertStringNotContainsString('loadRemainingDirectoryPages', $controller);
    }

    public function testOsmPreviewAndFrontendUseTheSameBoundedMarkerLimitAndTileCeiling(): void
    {
        $provider = (string) file_get_contents(dirname(__DIR__) . '/assets/js/providers/openstreetmap-provider.js');
        $preview = (string) file_get_contents(dirname(__DIR__) . '/assets/js/admin/settings-ux.js');
        $admin = (string) file_get_contents(dirname(__DIR__) . '/assets/js/admin.js');

        self::assertStringContainsString('maxZoom: 19', $provider);
        self::assertStringContainsString('maxNativeZoom: 19', $provider);
        self::assertStringNotContainsString('zoomToShowLayer', $provider);
        self::assertStringContainsString('OpenStreetMapProvider.prototype.createMarkerLayer', $provider);
        self::assertStringContainsString('OpenStreetMapProvider.prototype.replaceMarkers', $provider);
        self::assertStringContainsString('newLayer.addTo(this.map)', $provider);
        self::assertStringContainsString('this.markerLayer = newLayer', $provider);
        self::assertStringContainsString('this.map.removeLayer(oldLayer)', $provider);
        self::assertStringNotContainsString('BML marker add', $provider);
        self::assertStringNotContainsString('BML marker clear', $provider);
        self::assertStringContainsString('var PREVIEW_MARKER_LIMIT = 1000;', $preview);
        self::assertStringContainsString('maxZoom: 19', $preview);
        self::assertStringContainsString('maxNativeZoom: 19', $preview);
        self::assertStringContainsString("var settingsMap = document.getElementById('bml-settings-map');", $admin);
        self::assertMatchesRegularExpression('/var settingsMap[\\s\\S]*?L\\.tileLayer\\(BMLAdmin\\.settings\\.tile_url, \\{[\\s\\S]*?maxZoom: 19,[\\s\\S]*?maxNativeZoom: 19/', $admin);
    }
}
