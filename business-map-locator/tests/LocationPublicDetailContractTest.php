<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LocationPublicDetailContractTest extends TestCase
{
    public function testInlineDetailRendersEveryRequiredContactField(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertIsString($source);
        self::assertStringContainsString('bml-inline-detail__email', $source);
        self::assertStringContainsString('detail.email', $source);
        self::assertStringContainsString('bml-inline-detail__hours', $source);
    }

    public function testMultilineHoursAndDetailLabelsHaveDedicatedPresentationContracts(): void
    {
        $css = file_get_contents(dirname(__DIR__) . '/assets/css/frontend.css');
        $frontend = file_get_contents(dirname(__DIR__) . '/includes/Frontend/class-bml-frontend.php');

        self::assertIsString($css);
        self::assertIsString($frontend);
        self::assertStringContainsString('.bml-inline-detail__hours', $css);
        self::assertStringContainsString('white-space: pre-line;', $css);
        self::assertStringContainsString("'active' => __('Active'", $frontend);
        self::assertStringContainsString("'hours' => __('Hours'", $frontend);
    }
}
