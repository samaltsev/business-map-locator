<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Taxonomy\Action;

use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\Support\SlugGenerator;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class SaveTermAction
{
    public function __construct(private AdminActionResponder $responder, private AdminRequest $request)
    {
    }

    public function handle(): void
    {
        if (!current_user_can(Capabilities::MANAGE_TERMS)) {
            $this->responder->error(__('Insufficient permissions.', 'business-map-locator'));
        }

        $taxonomy = sanitize_key($this->request->postString('taxonomy'));
        $termId = $this->request->postInt('term_id');

        check_admin_referer('bml_handle');

        if (!in_array($taxonomy, ['bml_category', 'bml_city'], true)) {
            $this->responder->error(__('Invalid taxonomy.', 'business-map-locator'));
        }

        if ($termId > 0 && !term_exists($termId, $taxonomy)) {
            $this->responder->error(__('Term not found.', 'business-map-locator'));
        }

        $name = $this->request->postString('name');
        if ($name === '') {
            $this->responder->error(__('Name is required.', 'business-map-locator'));
        }

        $slug = SlugGenerator::fromTerm($name, $this->request->postString('slug'));
        $args = [
            'slug' => $slug,
            'description' => sanitize_textarea_field($this->request->postString('description')),
        ];

        $result = $termId > 0
            ? wp_update_term($termId, $taxonomy, array_merge($args, ['name' => $name]))
            : wp_insert_term($name, $taxonomy, $args);

        if (is_wp_error($result)) {
            $this->responder->error($result->get_error_message());
        }

        $newId = (int) ($result['term_id'] ?? 0);
        if ($newId <= 0) {
            $this->responder->error(__('Term could not be saved.', 'business-map-locator'));
        }

        update_term_meta($newId, 'bml_sort_order', $this->request->postInt('sort_order'));

        if ($taxonomy === 'bml_category') {
            $iconId = $this->request->postInt('icon_id');
            if ($iconId > 0) {
                update_term_meta($newId, 'bml_icon_id', $iconId);
            } else {
                delete_term_meta($newId, 'bml_icon_id');
            }
        }

        \BML_Location_Cache::invalidate();

        $this->responder->redirect(
            $taxonomy === 'bml_category' ? 'bml-categories' : 'bml-cities',
            'term-saved'
        );
    }
}
