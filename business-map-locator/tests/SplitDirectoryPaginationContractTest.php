<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SplitDirectoryPaginationContractTest extends TestCase
{
    public function testSplitUsesConfiguredPaginationWithoutDirectoryRecursion(): void
    {
        $root = dirname(__DIR__);
        $renderer = (string) file_get_contents($root . '/includes/Frontend/class-bml-locator-renderer.php');
        $controller = (string) file_get_contents($root . '/assets/js/map-controller.js');

        self::assertStringNotContainsString('load_all', $renderer);
        self::assertStringNotContainsString('loadAll', $renderer);
        self::assertStringContainsString("max(12, min(36, (int) \$attributes['per_page']))", $renderer);
        self::assertStringNotContainsString('loadAllDirectory', $controller);
        self::assertStringNotContainsString('directoryBatchSize', $controller);
        self::assertStringNotContainsString('loadRemainingDirectoryPages', $controller);
        self::assertStringContainsString('this.data.loadLocations(requestPage, this.state.user, this.state.perPage)', $controller);
        self::assertStringContainsString('return this.load(this.state.page + 1, true);', $controller);
        self::assertStringContainsString("'locations/markers?'", $controller);
        self::assertStringContainsString("'locations/' + encodeURIComponent(id)", $controller);
    }

    public function testFilteredMapIsNotFitToOnlyTheLoadedDirectoryPage(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertStringNotContainsString('focusFilteredLocations', $controller);
        self::assertStringContainsString('window.setTimeout(function () { self.loadMarkers(); }, 350);', $controller);
    }
}
