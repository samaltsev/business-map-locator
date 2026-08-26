<?php

namespace BusinessMapLocator\Admin\Location\Action;

use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class BulkLocationsAction
{
    public function __construct(private \BML_Location_Index $index, private AdminActionResponder $responder, private AdminRequest $request) {}

    public function handle(): void
    {
        if (!current_user_can(Capabilities::EDIT_LOCATIONS)) {
            $this->responder->error(__('Insufficient permissions.', 'business-map-locator'));
        }

        check_admin_referer('bml_bulk_locations');
        $action = sanitize_key($this->request->postString('bulk_action'));
        $ids = array_map('absint', $this->request->postArray('ids'));

        if (!in_array($action, ['delete', 'publish', 'draft'], true)) {
            $this->responder->error(__('Invalid bulk action.', 'business-map-locator'));
        }

        $changed = false;
        
        foreach ($ids as $id) {
            $post = get_post($id);
            if (!$post || $post->post_type !== 'bml_location') {
                continue;
            }

            if ($action === 'delete' && current_user_can(Capabilities::DELETE_LOCATION, $id)) {
                $deleted = wp_delete_post($id, true);
                if ($deleted) {
                    $this->index->delete($id);
                    $changed = true;
                }
            } elseif (in_array($action, ['publish', 'draft'], true) && current_user_can(Capabilities::EDIT_LOCATION, $id)) {
                $target = ($action === 'publish' && !current_user_can(Capabilities::PUBLISH_LOCATIONS)) ? 'draft' : $action;
                $result = wp_update_post(['ID' => $id, 'post_status' => $target], true);
                if (!is_wp_error($result) && $result) {
                    $this->index->upsert($id);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            \BML_Location_Cache::invalidate();
        }

        $this->responder->redirect('bml-locations', 'bulk-complete');
    }
}
