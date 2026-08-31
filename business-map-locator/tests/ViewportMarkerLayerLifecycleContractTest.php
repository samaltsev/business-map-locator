<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ViewportMarkerLayerLifecycleContractTest extends TestCase
{
    public function testViewportReplacementPublishesOnlyTheDedicatedMarkerLayerAfterItIsAttached(): void
    {
        $provider = (string) file_get_contents(dirname(__DIR__) . '/assets/js/providers/openstreetmap-provider.js');

        self::assertStringContainsString('this.markerLayer = null;', $provider);
        self::assertStringContainsString('var oldLayer = this.markerLayer;', $provider);
        self::assertStringContainsString('newLayer = this.createMarkerLayer();', $provider);
        self::assertStringContainsString('newLayer.addTo(this.map);', $provider);
        self::assertStringContainsString('this.markerLayer = newLayer;', $provider);
        self::assertStringContainsString('this.markersById = newRegistry;', $provider);
        self::assertStringContainsString('this.map.removeLayer(oldLayer);', $provider);
        self::assertGreaterThan(
            strpos($provider, 'this.markerLayer = newLayer;'),
            strpos($provider, 'this.map.removeLayer(oldLayer);')
        );
    }

    public function testPopupAndSelectionRemainRegistryOnlyAndDoNotClearViewportMarkers(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');
        $provider = (string) file_get_contents(dirname(__DIR__) . '/assets/js/providers/openstreetmap-provider.js');

        self::assertStringContainsString('var marker = this.markersById[id];', $provider);
        self::assertStringContainsString('marker.bindPopup(content);', $provider);
        self::assertStringNotContainsString('clearMarkers();', substr($controller, strpos($controller, 'LocatorController.prototype.clearSelection'), 500));
        self::assertStringNotContainsString('removeLayer(', substr($controller, strpos($controller, 'LocatorController.prototype.clearSelection'), 500));
        self::assertStringContainsString('this.clearSelection(false);', $controller);
        self::assertStringContainsString('this.map.provider.closePopup()', $controller);
    }

    public function testFreshViewportGenerationKeepsMarkerCallbacksAndFiniteCoordinates(): void
    {
        $provider = (string) file_get_contents(dirname(__DIR__) . '/assets/js/providers/openstreetmap-provider.js');

        self::assertStringContainsString('if (!Number.isFinite(lat) || !Number.isFinite(lng))', $provider);
        self::assertStringContainsString("marker.on('click', function ()", $provider);
        self::assertStringContainsString('onSelect(location);', $provider);
        self::assertStringContainsString('registry[location.id] = marker;', $provider);
        self::assertStringContainsString('layer.addLayer(marker);', $provider);
    }

    public function testSplitLeafletPaneOrderingIsProtectedFromLocatorCss(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__) . '/assets/css/frontend.css');

        self::assertStringNotContainsString('.bml-locator.bml-layout-split .leaflet-pane,', $css);
        self::assertStringNotContainsString('z-index: auto;', $css);
        self::assertStringContainsString('.leaflet-tile-pane { z-index: 200; }', $css);
        self::assertStringContainsString('.leaflet-marker-pane { z-index: 600; }', $css);
        self::assertStringContainsString('.leaflet-popup-pane { z-index: 700; }', $css);
        self::assertStringContainsString('.leaflet-marker-icon,', $css);
        self::assertStringContainsString('max-width: none !important;', $css);
    }
}
