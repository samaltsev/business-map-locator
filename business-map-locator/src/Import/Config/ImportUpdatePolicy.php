<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Config;

final class ImportUpdatePolicy
{
    public const NON_EMPTY_ONLY = 'non_empty_only';
    public const OVERWRITE_MAPPED = 'overwrite_mapped';
    public const CREATE_ONLY = 'create_only';
    public const UPDATE_ONLY = 'update_only';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::NON_EMPTY_ONLY, self::OVERWRITE_MAPPED, self::CREATE_ONLY, self::UPDATE_ONLY];
    }

    public static function normalize(string $policy): ?string
    {
        $policy = sanitize_key($policy);

        if ($policy === '') {
            return self::NON_EMPTY_ONLY;
        }

        return in_array($policy, self::all(), true) ? $policy : null;
    }
}
