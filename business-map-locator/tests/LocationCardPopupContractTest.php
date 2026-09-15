<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LocationCardPopupContractTest extends TestCase
{
    public function testCompactCardUsesOnlyFreeSafeFieldsAndNeverClaimsOpenNow(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertIsString($source);
        self::assertStringContainsString('bml-location-card__image', $source);
        self::assertStringContainsString('safeTelephoneUrl(location.phone)', $source);
        self::assertStringContainsString('safeWebsiteUrl(location.website)', $source);
        self::assertStringContainsString('location.address', $source);
        self::assertStringContainsString("location.operational_status === 'temporarily_closed'", $source);
        self::assertStringNotContainsString('Open now', $source);
    }

    public function testPopupRemovesEmptyAddressAndUsesSafeLinks(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/assets/js/map-controller.js');

        self::assertIsString($source);
        self::assertStringContainsString("|| !location.address", $source);
        self::assertStringContainsString("safeTelephoneUrl(location.phone)],", $source);
        self::assertStringContainsString("safeWebsiteUrl(location.website)]", $source);
    }
}
