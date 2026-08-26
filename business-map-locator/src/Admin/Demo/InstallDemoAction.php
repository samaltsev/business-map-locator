<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Demo;

use BusinessMapLocator\Admin\Shared\AdminActionResponder;
use BusinessMapLocator\Support\SlugGenerator;
use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class InstallDemoAction
{
    public function __construct(private TermAssigner $terms, private AdminActionResponder $responder)
    {
    }

    public function handle(): void
    {
        if (
            !current_user_can(Capabilities::MANAGE_SETTINGS)
            || !current_user_can(Capabilities::MANAGE_TERMS)
            || !current_user_can(Capabilities::PUBLISH_LOCATIONS)
        ) {
            $this->responder->error(__('Insufficient permissions.', 'business-map-locator'), 403);
        }

        check_admin_referer('bml_handle');

        foreach (['Office', 'Store', 'Service Center', 'Pickup Point'] as $category) {
            if (!term_exists($category, 'bml_category')) {
                $result = wp_insert_term($category, 'bml_category', ['slug' => SlugGenerator::fromTerm($category)]);
                if (is_wp_error($result)) {
                    $this->responder->error($result->get_error_message());
                }
            }
        }

        foreach (['Minsk', 'Warsaw', 'Berlin', 'Kyiv'] as $city) {
            if (!term_exists($city, 'bml_city')) {
                $result = wp_insert_term($city, 'bml_city', ['slug' => SlugGenerator::fromTerm($city)]);
                if (is_wp_error($result)) {
                    $this->responder->error($result->get_error_message());
                }
            }
        }

        $samples = [
            ['Main Office', 'Minsk', 'Office', 53.9006, 27.5590, 'Central Street 12'],
            ['Warsaw Store', 'Warsaw', 'Store', 52.2297, 21.0122, 'Marszalkowska 10'],
            ['Berlin Service Center', 'Berlin', 'Service Center', 52.5200, 13.4050, 'Alexanderplatz 4'],
            ['Kyiv Pickup Point', 'Kyiv', 'Pickup Point', 50.4501, 30.5234, 'Khreshchatyk 22'],
        ];

        foreach ($samples as $sample) {
            $id = wp_insert_post([
                'post_type' => 'bml_location',
                'post_title' => $sample[0],
                'post_status' => 'publish',
            ], true);

            if (is_wp_error($id)) {
                $this->responder->error($id->get_error_message());
            }

            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            $this->terms->assign($id, $sample[1], 'bml_city');
            $this->terms->assign($id, $sample[2], 'bml_category');
            update_post_meta($id, 'bml_lat', $sample[3]);
            update_post_meta($id, 'bml_lng', $sample[4]);
            update_post_meta($id, 'bml_address', $sample[5]);
            update_post_meta($id, 'bml_hours', 'Mon-Fri 09:00-18:00');
        }

        \BML_Location_Cache::invalidate();

        $this->responder->redirect('business-map-locator', 'demo-installed');
    }
}
