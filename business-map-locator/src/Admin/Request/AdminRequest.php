<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Request;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminRequest
{
    public function hasPost(string $key): bool
    {
        return array_key_exists($key, $_POST) && !is_array($_POST[$key]);
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        if (is_array($value)) {
            $value = $default;
        }

        return sanitize_text_field(wp_unslash($value));
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $_GET[$key] ?? $default;
        if (is_array($value)) {
            $value = $default;
        }

        return absint(wp_unslash($value));
    }

    public function postString(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        if (is_array($value)) {
            $value = $default;
        }

        return sanitize_text_field(wp_unslash($value));
    }

    public function postTextarea(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        if (is_array($value)) {
            $value = $default;
        }

        return sanitize_textarea_field(wp_unslash($value));
    }

    public function postRawString(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        if (is_array($value)) {
            $value = $default;
        }

        return (string) wp_unslash($value);
    }

    public function postInt(string $key, int $default = 0): int
    {
        $value = $_POST[$key] ?? $default;
        if (is_array($value)) {
            $value = $default;
        }

        return absint(wp_unslash($value));
    }

    public function postBool(string $key): bool
    {
        if (!isset($_POST[$key])) {
            return false;
        }

        if (is_array($_POST[$key])) {
            return false;
        }

        $value = sanitize_text_field(wp_unslash($_POST[$key]));

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public function postArray(string $key): array
    {
        $value = $_POST[$key] ?? [];
        if (!is_array($value)) {
            return [];
        }

        return wp_unslash($value);
    }

    public function requestArray(): array
    {
        return (array) wp_unslash($_REQUEST);
    }

    public function exportFilters(): array
    {
        $fields = $this->getString('fields');

        return [
            'fields' => $fields !== '' ? $fields : [],
            'bom' => $this->getString('bom', '1'),
            'delimiter' => $this->getString('delimiter', ','),
            'status' => sanitize_key($this->getString('status')),
            's' => $this->getString('s', $this->getString('search')),
            'city' => sanitize_key($this->getString('city')),
            'category' => sanitize_key($this->getString('category')),
        ];
    }
}
