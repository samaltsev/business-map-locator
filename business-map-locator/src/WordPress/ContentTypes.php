<?php
declare(strict_types=1);

namespace BusinessMapLocator\WordPress;

final class ContentTypes
{
    public function register(): void
    {
        register_post_type('bml_location', [
            'labels' => [
                'name' => __('Locations', 'business-map-locator'),
                'singular_name' => __('Location', 'business-map-locator'),
                'add_new_item' => __('Add location', 'business-map-locator'),
                'edit_item' => __('Edit location', 'business-map-locator'),
                'menu_name' => __('Locations', 'business-map-locator'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon' => 'dashicons-location-alt',
            'capability_type' => ['bml_location', 'bml_locations'],
            'map_meta_cap' => true,
        ]);

        register_taxonomy('bml_category', ['bml_location'], [
            'labels' => [
                'name' => __('Categories', 'business-map-locator'),
                'singular_name' => __('Category', 'business-map-locator'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
            'show_admin_column' => true,
            'capabilities' => [
                'manage_terms' => Capabilities::MANAGE_TERMS,
                'edit_terms' => Capabilities::MANAGE_TERMS,
                'delete_terms' => Capabilities::MANAGE_TERMS,
                'assign_terms' => Capabilities::EDIT_LOCATIONS,
            ],
        ]);

        register_taxonomy('bml_city', ['bml_location'], [
            'labels' => [
                'name' => __('Cities', 'business-map-locator'),
                'singular_name' => __('City', 'business-map-locator'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'hierarchical' => false,
            'show_admin_column' => true,
            'capabilities' => [
                'manage_terms' => Capabilities::MANAGE_TERMS,
                'edit_terms' => Capabilities::MANAGE_TERMS,
                'delete_terms' => Capabilities::MANAGE_TERMS,
                'assign_terms' => Capabilities::EDIT_LOCATIONS,
            ],
        ]);

        register_taxonomy('bml_area', ['bml_location'], [
            'labels' => [
                'name' => __('Areas', 'business-map-locator'),
                'singular_name' => __('Area', 'business-map-locator'),
            ],
            'public' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'hierarchical' => true,
            'show_admin_column' => false,
            'rewrite' => false,
            'query_var' => false,
            'capabilities' => [
                'manage_terms' => Capabilities::MANAGE_AREAS,
                'edit_terms' => Capabilities::EDIT_AREAS,
                'delete_terms' => Capabilities::DELETE_AREAS,
                'assign_terms' => Capabilities::ASSIGN_AREAS,
            ],
        ]);
    }
}
