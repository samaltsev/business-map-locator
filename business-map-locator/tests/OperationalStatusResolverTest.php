<?php
declare(strict_types=1);

use BusinessMapLocator\Support\OperationalStatusResolver;
use PHPUnit\Framework\TestCase;

final class OperationalStatusResolverTest extends TestCase
{
    /** @dataProvider resolvedStatuses */
    public function testResolvesStoredCanonicalAndLegacyValues(string $canonical, string $visible, string $expected): void
    {
        self::assertSame($expected, OperationalStatusResolver::resolve($canonical, $visible));
    }

    /** @return array<string, array{string, string, string}> */
    public static function resolvedStatuses(): array
    {
        return [
            'active canonical wins stale legacy hidden' => ['active', '0', 'active'],
            'temporarily closed canonical wins stale legacy hidden' => ['temporarily_closed', '0', 'temporarily_closed'],
            'hidden canonical' => ['hidden', '1', 'hidden'],
            'open alias' => ['open', '0', 'active'],
            'legacy hidden' => ['', '0', 'hidden'],
            'missing status remains active' => ['', '1', 'active'],
            'invalid canonical uses legacy fallback' => ['banana', '0', 'hidden'],
        ];
    }

    /** @dataProvider visibleValues */
    public function testDerivesLegacyVisibleValue(string $status, string $expected): void
    {
        self::assertSame($expected, OperationalStatusResolver::visibleValue($status));
    }

    /** @return array<string, array{string, string}> */
    public static function visibleValues(): array
    {
        return [
            'active' => ['active', '1'],
            'temporarily closed' => ['temporarily_closed', '1'],
            'hidden' => ['hidden', '0'],
        ];
    }
}
