<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Taxonomy\Action;

use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class DeleteTermAction
{
    public function __construct(private AdminActionResponder $responder, private AdminRequest $request)
    {
    }

    public function handle(): void
    {
        if (!current_user_can(Capabilities::MANAGE_TERMS)) {
            $this->responder->error(__('Insufficient permissions.', 'business-map-locator'));
        }

        $taxonomy = sanitize_key($this->request->getString('taxonomy'));
        $termId = $this->request->getInt('term_id');

        check_admin_referer('bml_handle_' . $termId);

        if (!in_array($taxonomy, ['bml_category', 'bml_city'], true)) {
            $this->responder->error(__('Invalid taxonomy.', 'business-map-locator'));
        }

        if ($termId <= 0 || !term_exists($termId, $taxonomy)) {
            $this->responder->error(__('Term not found.', 'business-map-locator'));
        }

        $term = get_term($termId, $taxonomy);
        if ($term && !is_wp_error($term) && (int) $term->count > 0) {
            $this->responder->error(__('This item is still assigned to locations. Reassign those locations before deleting it.', 'business-map-locator'));
        }

        $result = wp_delete_term($termId, $taxonomy);

        if (is_wp_error($result)) {
            $this->responder->error($result->get_error_message());
        }

        if ($result === false || $result === 0) {
            $this->responder->error(__('Term could not be deleted.', 'business-map-locator'));
        }

        \BML_Location_Cache::invalidate();

        $this->responder->redirect(
            $taxonomy === 'bml_category' ? 'bml-categories' : 'bml-cities',
            'term-deleted'
        );
    }
}
