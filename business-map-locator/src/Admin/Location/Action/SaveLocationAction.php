<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Location\Action;

use BusinessMapLocator\Admin\Location\LocationWriteService;
use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) { exit; }

final class SaveLocationAction
{
    public function __construct(private LocationWriteService $writer, private AdminActionResponder $responder, private AdminRequest $request) {}

    public function handle(): void
    {
        $id = $this->request->postInt('id');
        $existing = $id > 0 ? get_post($id) : null;
        if ($id > 0 && (!$existing || $existing->post_type !== 'bml_location' || !current_user_can(Capabilities::EDIT_LOCATION, $id))) { $this->responder->error(__('You are not allowed to edit this location.', 'business-map-locator')); }
        if ($id <= 0 && !current_user_can(Capabilities::CREATE_LOCATIONS)) { $this->responder->error(__('You are not allowed to create locations.', 'business-map-locator'), 403); }
        check_admin_referer('bml_save_location_custom');

        $statusOverride = $this->request->postString('status_override');
        $status = $statusOverride === 'draft' || $this->request->postString('status') === 'draft' ? 'draft' : ($this->request->hasPost('status') || $statusOverride !== '' ? 'publish' : (string) ($existing?->post_status ?? 'publish'));
        if ($status === 'publish' && !current_user_can(Capabilities::PUBLISH_LOCATIONS)) { $status = 'draft'; }
        $input = ['title' => $this->request->postRawString('title', (string) ($existing?->post_title ?? '')), 'status' => $status];
        foreach (['content', 'excerpt', 'address', 'region', 'country', 'postcode', 'phone', 'email', 'website', 'hours', 'lat', 'lng', 'operational_status', 'category_id', 'city_id', 'area_id', 'featured_image_id', 'remove_featured_image'] as $field) {
            if ($this->request->hasPost($field)) { $input[$field] = $this->request->postRawString($field); }
        }
        foreach (['address', 'region', 'country', 'postcode', 'phone', 'email', 'website', 'hours'] as $field) {
            $legacy = 'bml_location_' . $field;
            if (!$this->request->hasPost($field) && $this->request->hasPost($legacy)) { $input[$field] = $this->request->postRawString($legacy); }
        }
        $result = $this->writer->save($id, $input);
        if (is_wp_error($result)) { $this->responder->error($result->get_error_message()); }
        $this->responder->redirect('bml-location-edit', 'location-saved', ['id' => $result]);
    }
}
