<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Taxonomy\Action;

use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class DuplicateTermAction
{
    public function __construct(private AdminActionResponder $responder, private AdminRequest $request)
    {
    }

    public function handle(): void
    {
        if (!current_user_can(Capabilities::MANAGE_TERMS)) {
            $this->responder->error(__('Insufficient permissions.', 'business-map-locator'));
        }

        $termId = $this->request->getInt('term_id');
        check_admin_referer('bml_duplicate_term_' . $termId);

        $term = $termId > 0 ? get_term($termId, 'bml_category') : null;
        if (!$term || is_wp_error($term)) {
            $this->responder->error(__('Category not found.', 'business-map-locator'));
        }

        $baseName = sprintf(__('%s copy', 'business-map-locator'), $term->name);
        $name = $baseName;
        $suffix = 2;
        while (term_exists($name, 'bml_category')) {
            $name = $baseName . ' ' . $suffix;
            $suffix++;
        }

        $result = wp_insert_term($name, 'bml_category', [
            'description' => $term->description,
            'slug' => sanitize_title($name),
        ]);

        if (is_wp_error($result)) {
            $this->responder->error($result->get_error_message());
        }

        $newId = (int) ($result['term_id'] ?? 0);
        foreach (['bml_sort_order', 'bml_icon_id'] as $metaKey) {
            $value = get_term_meta($termId, $metaKey, true);
            if ($value !== '') {
                update_term_meta($newId, $metaKey, $value);
            }
        }

        \BML_Location_Cache::invalidate();
        $this->responder->redirect('bml-categories', 'term-duplicated');
    }
}
