<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Menu;
if (!defined('ABSPATH')) { exit; }
final class AdminTitleRegistry
{
    private array $titles = [];
    public function register(mixed $hook, string $title): void
    {
        if (!is_string($hook) || $hook === '') { return; }
        $this->titles[$hook] = $title;
        add_action('load-' . $hook, [$this, 'ensure']);
    }
    public function ensure(): void
    {
        global $title, $hook_suffix;
        if (is_string($title) && $title !== '') { return; }
        $title = $this->titles[$hook_suffix] ?? __('Business Map Locator', 'business-map-locator');
    }
}
