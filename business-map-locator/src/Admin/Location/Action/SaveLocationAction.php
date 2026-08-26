<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Location\Action;

use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\Domain\Location\LocationServiceCatalog;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class SaveLocationAction
{
    public function __construct(
        private LocationServiceCatalog $services,
        private \BML_Location_Index $index,
        private AdminActionResponder $responder,
        private AdminRequest $request
    ) {
    }

    public function handle(): void
    {
        $id = $this->request->postInt('id');
        $existing = null;

        if ($id > 0) {
            $existing = get_post($id);
            if (!$existing || $existing->post_type !== 'bml_location' || !current_user_can(Capabilities::EDIT_LOCATION, $id)) {
                $this->responder->error(__('You are not allowed to edit this location.', 'business-map-locator'));
            }
        } elseif (!current_user_can(Capabilities::CREATE_LOCATIONS)) {
            $this->responder->error(__('You are not allowed to create locations.', 'business-map-locator'), 403);
        }

        check_admin_referer('bml_save_location_custom');

        $title = $this->request->hasPost('title') ? $this->request->postString('title') : (string) ($existing?->post_title ?? '');
        if ($title === '') {
            $this->responder->error(__('Location title is required.', 'business-map-locator'));
        }

        $statusOverride = $this->request->postString('status_override');
        $status = $statusOverride === 'draft' || $this->request->postString('status') === 'draft'
            ? 'draft'
            : ($this->request->hasPost('status') || $statusOverride !== '' ? 'publish' : (string) ($existing?->post_status ?? 'publish'));

        $hasLat = $this->request->hasPost('lat');
        $hasLng = $this->request->hasPost('lng');
        $latRaw = $hasLat ? trim($this->request->postRawString('lat')) : (string) get_post_meta($id, 'bml_lat', true);
        $lngRaw = $hasLng ? trim($this->request->postRawString('lng')) : (string) get_post_meta($id, 'bml_lng', true);
        $lat = $latRaw === '' ? null : filter_var($latRaw, FILTER_VALIDATE_FLOAT);
        $lng = $lngRaw === '' ? null : filter_var($lngRaw, FILTER_VALIDATE_FLOAT);

        if ($lat !== null && ($lat === false || $lat < -90 || $lat > 90)) {
            $this->responder->error(__('Invalid latitude.', 'business-map-locator'));
        }
        if ($lng !== null && ($lng === false || $lng < -180 || $lng > 180)) {
            $this->responder->error(__('Invalid longitude.', 'business-map-locator'));
        }
        if ($status === 'publish' && ($lat === null || $lng === null || $lat === false || $lng === false)) {
            $this->responder->error(__('A map position is required before publishing.', 'business-map-locator'));
        }

        if ($status === 'publish' && !current_user_can(Capabilities::PUBLISH_LOCATIONS)) {
            $status = 'draft';
        }

        $postData = [
            'post_type' => 'bml_location',
            'post_title' => $title,
            'post_status' => $status,
        ];
        if ($this->request->hasPost('content')) {
            $postData['post_content'] = wp_kses_post($this->request->postRawString('content'));
        }
        if ($this->request->hasPost('excerpt')) {
            $postData['post_excerpt'] = $this->request->postTextarea('excerpt');
        }

        $result = 0;
        if ($id > 0) {
            $postData['ID'] = $id;
            $result = wp_update_post($postData, true);
        } else {
            $result = wp_insert_post($postData, true);
        }

        if (is_wp_error($result)) {
            $this->responder->error($result->get_error_message());
        }

        $id = (int) $result;
        if ($id <= 0) {
            $this->responder->error(__('Location could not be saved.', 'business-map-locator'));
        }
        foreach (['address','region','country','postcode','phone','email','website','hours'] as $key) {
            if ($this->hasLocationField($key)) {
                update_post_meta($id, 'bml_' . $key, $this->locationField($key));
            }
        }
        if (($hasLat || $hasLng) && ($lat === null || $lng === null)) {
            delete_post_meta($id, 'bml_lat');
            delete_post_meta($id, 'bml_lng');
        } elseif ($hasLat || $hasLng) {
            update_post_meta($id, 'bml_lat', (float) $lat);
            update_post_meta($id, 'bml_lng', (float) $lng);
        }

        if ($this->request->hasPost('operational_status')) {
            $operational = sanitize_key($this->request->postString('operational_status'));
            $operational = $operational === 'open' ? 'active' : $operational;
            if (!in_array($operational, ['active', 'temporarily_closed', 'hidden'], true)) {
                $operational = 'active';
            }
            update_post_meta($id, 'bml_operational_status', $operational);
        }

        foreach (['bml_category' => 'category_id', 'bml_city' => 'city_id'] as $taxonomy => $field) {
            if (!$this->request->hasPost($field)) {
                continue;
            }
            $termId = $this->request->postInt($field);
            if ($termId > 0 && (!term_exists($termId, $taxonomy))) {
                $this->responder->error(__('Invalid location taxonomy term.', 'business-map-locator'));
            }
            $termResult = wp_set_object_terms($id, $termId ? [$termId] : [], $taxonomy);
            if (is_wp_error($termResult)) {
                $this->responder->error($termResult->get_error_message());
            }
        }
        if ($this->request->postBool('remove_featured_image')) {
            delete_post_thumbnail($id);
        } elseif ($this->request->hasPost('featured_image_id')) {
            $imageId = $this->request->postInt('featured_image_id');
            if ($imageId > 0 && get_post_type($imageId) === 'attachment') {
                set_post_thumbnail($id, $imageId);
            }
        }

        $this->index->upsert($id);
        \BML_Location_Cache::invalidate();

        $this->responder->redirect('bml-location-edit', 'location-saved', ['id' => $id]);
    }

    private function locationField(string $key): string
    {
        $value = $this->request->hasPost($key) ? $this->request->postRawString($key) : $this->request->postRawString('bml_location_' . $key);
        return match ($key) {
            'email' => sanitize_email($value),
            'website' => esc_url_raw($value),
            'hours' => sanitize_textarea_field($value),
            default => sanitize_text_field($value),
        };
    }

    private function hasLocationField(string $key): bool
    {
        return $this->request->hasPost($key) || $this->request->hasPost('bml_location_' . $key);
    }
}
