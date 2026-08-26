<?php
declare(strict_types=1);

namespace BusinessMapLocator\WordPress;

final class BlockRegistrar
{
    public function register(): void
    {
        $path = BML_DIR . 'includes/Blocks/business-locator';

        if (!function_exists('register_block_type') || !file_exists($path . '/block.json')) {
            return;
        }

        register_block_type($path, [
            'render_callback' => static fn (array $attributes): string =>
                \BML_Shortcode::render_locator(array_merge($attributes, [
                    'category_mode' => $attributes['categoryMode'] ?? 'visible',
                    'city_mode' => $attributes['cityMode'] ?? 'visible',
                    'per_page' => $attributes['perPage'] ?? 24,
                ])),
        ]);
    }
}
