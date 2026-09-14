<?php
declare(strict_types=1);

namespace BusinessMapLocator\Domain\Location;

/**
 * Sanitizes Location scalar fields consistently for manual editing and CSV import.
 */
final class LocationFieldSanitizer
{
    public static function sanitize(string $field, string $value): string
    {
        return match ($field) {
            'email' => sanitize_email($value),
            'website' => esc_url_raw($value),
            'hours' => sanitize_textarea_field($value),
            default => sanitize_text_field($value),
        };
    }
}
