<?php
declare(strict_types=1);

use BusinessMapLocator\Domain\Location\LocationFieldSanitizer;
use PHPUnit\Framework\TestCase;

final class LocationFieldSanitizerTest extends TestCase
{
    public function testHoursRemainMultilineForEveryWritePath(): void
    {
        self::assertSame(
            "Mon–Fri 09:00–18:00\nSat 10:00–14:00",
            LocationFieldSanitizer::sanitize('hours', "Mon–Fri 09:00–18:00\nSat 10:00–14:00")
        );
    }

    public function testContactFieldsUseTheirFieldSpecificSanitizers(): void
    {
        self::assertSame('info@example.test', LocationFieldSanitizer::sanitize('email', ' info@example.test '));
        self::assertSame('https://example.test/location', LocationFieldSanitizer::sanitize('website', ' https://example.test/location '));
        self::assertSame('Main Street 1', LocationFieldSanitizer::sanitize('address', ' Main Street 1 '));
    }
}
