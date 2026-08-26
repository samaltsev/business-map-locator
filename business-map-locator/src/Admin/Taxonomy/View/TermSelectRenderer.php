<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Taxonomy\View;
if (!defined('ABSPATH')) { exit; }
final class TermSelectRenderer
{
    public function render(array $options, string $name, string $selected, string $placeholder): void
    {
        echo '<select name="' . esc_attr($name) . '"><option value="">' . esc_html($placeholder) . '</option>';
        foreach ($options as $option) {
            echo '<option value="' . esc_attr($option['value']) . '" ' . selected($selected, $option['value'], false) . '>' . esc_html($option['label']) . '</option>';
        }
        echo '</select>';
    }
}
