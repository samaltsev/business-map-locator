<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RestUrlCompatibilityContractTest extends TestCase
{
    public function testAffectedRuntimesUseOneUrlApiBasedBuilderForPrettyAndQueryStyleRestBases(): void
    {
        $frontend = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');
        $preview = (string) file_get_contents(dirname(__DIR__) . '/assets/js/admin/settings-ux.js');

        foreach ([$frontend, $preview] as $source) {
            self::assertStringContainsString('function buildRestUrl(restBase, endpoint, params)', $source);
            self::assertStringContainsString("new URL(String(restBase || ''), window.location.href)", $source);
            self::assertStringContainsString("url.searchParams.get('rest_route')", $source);
            self::assertStringContainsString("url.searchParams.set('rest_route', route.replace(/\\/?$/, '/') + path)", $source);
            self::assertStringContainsString("url.pathname = url.pathname.replace(/\\/?$/, '/') + path", $source);
            self::assertStringContainsString("new URLSearchParams(params || '').forEach", $source);
        }
    }

    public function testFrontendBuildsEveryAffectedEndpointThroughTheCompatibilityBuilder(): void
    {
        $frontend = (string) file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertStringContainsString("buildRestUrl(this.restUrl, 'filters', query)", $frontend);
        self::assertStringContainsString("buildRestUrl(this.restUrl, 'locations', createParams(", $frontend);
        self::assertStringContainsString("buildRestUrl(this.restUrl, 'locations/markers', params)", $frontend);
        self::assertStringContainsString("buildRestUrl(this.restUrl, 'locations/bounds', params)", $frontend);
        self::assertStringContainsString("buildRestUrl(this.restUrl, 'locations/' + encodeURIComponent(id))", $frontend);
        self::assertStringNotContainsString("this.restUrl + 'locations?", $frontend);
        self::assertStringNotContainsString("this.restUrl + 'locations/markers?", $frontend);
    }

    public function testAdminPreviewUsesTheCompatibilityBuilderForEveryRestRequestWithoutChangingApiFetchAdminPath(): void
    {
        $preview = (string) file_get_contents(dirname(__DIR__) . '/assets/js/admin/settings-ux.js');
        $admin = (string) file_get_contents(dirname(__DIR__) . '/assets/js/admin.js');

        self::assertStringContainsString("buildRestUrl(restBase, 'locations/markers', params)", $preview);
        self::assertStringContainsString("buildRestUrl(restBase, 'locations/bounds')", $preview);
        self::assertStringContainsString("buildRestUrl(restBase, 'locations', { page: 1, per_page: 6, orderby: 'title', order: 'ASC' })", $preview);
        self::assertStringContainsString("buildRestUrl(restBase, 'geocode/search', { q: query })", $preview);
        self::assertStringNotContainsString("restBase.replace(/\\/?$/, '/') + 'locations", $preview);
        self::assertStringNotContainsString("restBase.replace(/\\/?$/, '/') + 'geocode/search?", $preview);
        self::assertStringContainsString('window.wp.apiFetch', $admin);
    }
}
