<?php

namespace BusinessMapLocator\Admin\Location\Action;

use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class DeleteLocationAction
{
    public function __construct(private \BML_Location_Index $index, private AdminActionResponder $responder, private AdminRequest $request) {}

    public function handle(): void
    {
        $id = $this->request->getInt('id');
        if ($id <= 0 || !current_user_can(Capabilities::DELETE_LOCATION, $id)) {
            $this->responder->error(__('Insufficient permissions.', 'business-map-locator'));
        }

        check_admin_referer('bml_delete_location_' . $id);

        $post = get_post($id);
        if (!$post || $post->post_type !== 'bml_location') {
            $this->responder->error(__('Location not found.', 'business-map-locator'));
        }

        $deleted = wp_delete_post($id, true);
        if (!$deleted) {
            $this->responder->error(__('Location could not be deleted.', 'business-map-locator'));
        }

        $this->index->delete($id);
        \BML_Location_Cache::invalidate();

        $this->responder->redirect('bml-locations', 'location-deleted');
    }
}
