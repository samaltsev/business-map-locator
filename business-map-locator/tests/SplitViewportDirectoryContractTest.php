<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SplitViewportDirectoryContractTest extends TestCase
{
    public function testSplitDirectoryRequestsUseTheCapturedViewportAndPreserveItForLoadMore(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertStringContainsString('function appendBoundsParams(params, bounds)', $controller);
        self::assertStringContainsString("params.set('north', String(bounds.north));", $controller);
        self::assertStringContainsString('appendNearParams(params, settings, origin);', $controller);
        self::assertStringContainsString('appendBoundsParams(params, bounds);', $controller);
        self::assertStringContainsString('createParams(this.root, this.settings, page || 1, origin, perPage, bounds)', $controller);
        self::assertStringContainsString('directoryBounds: null', $controller);
        self::assertStringContainsString('bounds = this.state.directoryBounds;', $controller);
        self::assertStringContainsString('this.data.loadLocations(requestPage, this.state.user, this.state.perPage, bounds)', $controller);
        self::assertStringContainsString('this.state.items = [];', $controller);
        self::assertStringContainsString('return this.load(this.state.page + 1, true);', $controller);
        self::assertStringNotContainsString('loadAllDirectory', $controller);
        self::assertStringNotContainsString('loadRemainingDirectoryPages', $controller);
    }

    public function testSplitViewportRefreshUsesOneBoundsSnapshotForCardsAndMarkers(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertStringContainsString('LocatorController.prototype.refreshResults', $controller);
        self::assertStringContainsString('var bounds = this.currentMapBounds();', $controller);
        self::assertStringContainsString('this.state.page = 1;', $controller);
        self::assertStringContainsString('this.state.directoryBounds = bounds;', $controller);
        self::assertStringContainsString('this.load(1, false, this.isViewportBoundDirectory() ? bounds : null)', $controller);
        self::assertStringContainsString('this.loadMarkers(bounds)', $controller);
        self::assertStringContainsString('LocatorController.prototype.refreshViewport', $controller);
        self::assertStringContainsString('if (!this.isViewportBoundDirectory()) {', $controller);
        self::assertStringContainsString('return this.loadMarkers();', $controller);
        self::assertStringContainsString('return this.refreshResults();', $controller);
        self::assertStringContainsString('if (sequence !== self.state.cardSequence) { return; }', $controller);
        self::assertStringNotContainsString('focusFilteredLocations', $controller);
    }
}
