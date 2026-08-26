<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Location\View;

if (!defined('ABSPATH')) {
    exit;
}

final class ContactIcon
{
    public static function render(string $name): string
    {
        $icons = [
            'whatsapp' => '<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M8.2 7.7c.3-.6.7-.6 1-.6h.4c.2 0 .4.1.5.4l.9 2c.1.3.1.5-.1.7l-.7.9c-.2.2-.1.4 0 .6.7 1.3 1.8 2.3 3.1 3 .3.2.5.2.7 0l.9-1.1c.2-.2.4-.3.7-.2l2 .9c.3.1.4.3.4.5 0 .5-.2 1.6-1 2.2-.7.6-1.7.9-2.8.6-1.5-.4-3.5-1.2-5.2-3-1.4-1.5-2.3-3.3-2.5-4.7-.2-1 .1-1.7.7-2.2Z" fill="#fff"/><path d="m7.3 17.2-.6 2.1 2.2-.6" fill="none" stroke="#fff" stroke-width="1.4" stroke-linecap="round"/></svg>',
            'telegram' => '<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="m6.8 11.7 9.8-3.8c.5-.2.9.1.7.8l-1.7 7.8c-.1.6-.5.7-1 .4l-2.6-1.9-1.2 1.2c-.2.2-.3.3-.6.3l.2-2.7 5-4.5c.2-.2-.1-.3-.4-.1l-6.2 3.9-2.6-.8c-.6-.2-.6-.5.1-.8Z" fill="#fff"/></svg>',
            'viber' => '<svg viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="18" height="18" rx="6" fill="currentColor"/><path d="M8 7.4c.5-.5 1.2-.6 1.8-.2 1.4.8 3.3 2.7 4.1 4.1.4.7.3 1.4-.2 1.8l-.8.7c-.2.2-.2.4-.1.6.4.7 1 1.3 1.7 1.7.2.1.4.1.6-.1l.7-.8c.5-.5 1.2-.6 1.8-.2l.7.5c.5.4.6 1.1.2 1.6-.7.9-1.7 1.3-2.9 1.1-2.4-.5-5.6-3.6-6.1-6.1-.2-1.2.2-2.2 1.1-2.9Z" fill="#fff" transform="translate(-2 -1) scale(.95)"/><path d="M14 7.4c1.7.2 3 1.5 3.2 3.2M14.2 5.5c2.7.2 4.8 2.4 5 5" fill="none" stroke="#fff" stroke-width="1.2" stroke-linecap="round"/></svg>',
            'facebook' => '<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M13.4 18v-5h1.8l.3-2h-2.1V9.7c0-.6.2-1 1-1h1.2V6.9c-.2 0-.9-.1-1.7-.1-1.7 0-2.8 1-2.8 2.9V11H9.2v2H11v5h2.4Z" fill="#fff"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="4" width="16" height="16" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3.5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.3" cy="6.8" r="1" fill="currentColor"/></svg>',
            'linkedin' => '<svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="4" width="16" height="16" rx="2" fill="currentColor"/><circle cx="8.2" cy="9" r="1.2" fill="#fff"/><path d="M7.2 11h2v6h-2zm3.6 0h1.9v.8c.5-.7 1.2-1 2.1-1 2 0 2.4 1.4 2.4 3.2v3h-2v-2.7c0-.7 0-1.7-1.1-1.7s-1.3.8-1.3 1.7V17h-2v-6Z" fill="#fff"/></svg>',
            'tiktok' => '<svg viewBox="0 0 24 24" focusable="false"><path d="M14.2 5.2c.4 1.9 1.5 3 3.3 3.3v2.2c-1.2 0-2.3-.4-3.3-1.1v4.3c0 2.7-1.8 4.6-4.4 4.6-2.5 0-4.3-1.8-4.3-4.2 0-2.7 2.2-4.7 5-4.2v2.3c-1.4-.4-2.7.4-2.7 1.8 0 1.1.8 1.9 1.9 1.9 1.3 0 2.1-.8 2.1-2.3V5.2h2.4Z" fill="currentColor"/></svg>',
            'website' => '<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.8 12h16.4M12 3.5c2.1 2.3 3.2 5.1 3.2 8.5S14.1 18.2 12 20.5M12 3.5C9.9 5.8 8.8 8.6 8.8 12s1.1 6.2 3.2 8.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
            'navigation' => '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 3.3 20.7 12 12 20.7 3.3 12 12 3.3Z" fill="currentColor"/><path d="M9 14.8v-2.2c0-.9.7-1.6 1.6-1.6h3.2" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/><path d="m12.7 8.8 2.3 2.2-2.3 2.2" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'directions' => '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 3.3 20.7 12 12 20.7 3.3 12 12 3.3Z" fill="currentColor"/><path d="M9 14.8v-2.2c0-.9.7-1.6 1.6-1.6h3.2" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/><path d="m12.7 8.8 2.3 2.2-2.3 2.2" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];

        return $icons[$name] ?? '';
    }
}
