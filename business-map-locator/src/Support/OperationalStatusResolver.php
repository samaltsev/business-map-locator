<?php
declare(strict_types=1);

namespace BusinessMapLocator\Support;

final class OperationalStatusResolver
{
    public const ACTIVE = 'active';
    public const TEMPORARILY_CLOSED = 'temporarily_closed';
    public const HIDDEN = 'hidden';

    public static function resolve(mixed $canonicalStatus, mixed $legacyVisible): string
    {
        $canonicalStatus = sanitize_key((string) $canonicalStatus);
        $canonicalStatus = $canonicalStatus === 'open' ? self::ACTIVE : $canonicalStatus;

        if (in_array($canonicalStatus, [self::ACTIVE, self::TEMPORARILY_CLOSED, self::HIDDEN], true)) {
            return $canonicalStatus;
        }

        return trim((string) $legacyVisible) === '0' ? self::HIDDEN : self::ACTIVE;
    }

    public static function visibleValue(string $status): string
    {
        return $status === self::HIDDEN ? '0' : '1';
    }
}
