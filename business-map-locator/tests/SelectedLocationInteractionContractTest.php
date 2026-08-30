<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SelectedLocationInteractionContractTest extends TestCase
{
    public function testMarkerAndDirectorySelectionShareOneOnDemandDetailState(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertStringContainsString('selectedLocationId: null', $controller);
        self::assertStringContainsString('selectedLocationDetail: null', $controller);
        self::assertStringContainsString('LocatorController.prototype.selectLocation', $controller);
        self::assertStringContainsString("this.selectLocation(location.id, 'marker')", $controller);
        self::assertStringContainsString("self.selectLocation(card.dataset.id, 'directory', card);", $controller);
        self::assertStringContainsString("this.data.loadDetail(id)", $controller);
        self::assertStringContainsString('this.detailCache[id]', $controller);
        self::assertStringContainsString('if (sequence !== self.state.detailSequence', $controller);
        self::assertStringContainsString('LocatorController.prototype.openSelectedMarkerPopup', $controller);
        self::assertStringContainsString('this.popup.renderDetail(detail)', $controller);
    }

    public function testSelectedDetailUsesInlineSidebarAndCurrentMarkerGeneration(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');
        $osm = (string) file_get_contents(dirname(__DIR__) . '/assets/js/providers/openstreetmap-provider.js');
        $google = (string) file_get_contents(dirname(__DIR__) . '/assets/js/providers/google-maps-provider.js');

        self::assertStringContainsString("return this.root.classList.contains('bml-layout-split');", $controller);
        self::assertStringContainsString("'bml-selected-location bml-location-card is-selected is-expanded'", $controller);
        self::assertStringContainsString('LocatorController.prototype.reconcileSelectionWithMarkers', $controller);
        self::assertStringContainsString('self.reconcileSelectionWithMarkers();', $controller);
        self::assertStringContainsString('this.clearSelection(false);', $controller);
        self::assertStringContainsString('OpenStreetMapProvider.prototype.openMarkerPopup', $osm);
        self::assertStringContainsString('var marker = this.markersById[id];', $osm);
        self::assertStringContainsString('marker.bindPopup(content);', $osm);
        self::assertStringContainsString('GoogleMapsProvider.prototype.openMarkerPopup', $google);
        self::assertStringNotContainsString('zoomToShowLayer', $osm);
    }

    public function testExpandedSplitCardKeepsInlineDetailInDocumentFlow(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__) . '/assets/css/frontend.css');

        self::assertStringContainsString('.bml-location-card.is-expanded {', $css);
        self::assertStringContainsString('height: auto;', $css);
        self::assertStringContainsString('max-height: none;', $css);
        self::assertStringContainsString('overflow: visible;', $css);
        self::assertStringContainsString('.bml-location-card.is-expanded .bml-location-card__expanded {', $css);
        self::assertStringContainsString('position: static;', $css);
        self::assertStringNotContainsString('.bml-location-card.is-expanded .bml-location-card__expanded { position: absolute;', $css);
    }

    public function testMarkerSelectionScrollsOnlyTheDirectoryResultsContainer(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertStringContainsString('LocatorController.prototype.scrollSelectedCardIntoDirectoryView', $controller);
        self::assertStringContainsString("this.root.querySelector('.bml-results')", $controller);
        self::assertStringContainsString('container.contains(card)', $controller);
        self::assertStringContainsString('container.scrollTop + (cardRect.top - containerRect.top)', $controller);
        self::assertStringContainsString("container.scrollTo({ top: Math.max(0, top), behavior: 'smooth' })", $controller);
        self::assertStringNotContainsString("selectedCard.scrollIntoView({ block: 'nearest', behavior: 'smooth' })", $controller);
    }
}
