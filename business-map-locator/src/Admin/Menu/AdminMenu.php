<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Menu;
use BusinessMapLocator\Admin\Dashboard\DashboardPage;
use BusinessMapLocator\Admin\Location\LocationsPage;
use BusinessMapLocator\Admin\Location\LocationEditorPage;
use BusinessMapLocator\Admin\Taxonomy\TaxonomyPage;
use BusinessMapLocator\Admin\Import\ImportPage;
use BusinessMapLocator\Admin\Settings\SettingsPage;
use BusinessMapLocator\WordPress\Capabilities;
if (!defined('ABSPATH')) { exit; }
final class AdminMenu
{
    public function __construct(
        private AdminTitleRegistry $titles,
        private DashboardPage $dashboard,
        private LocationsPage $locations,
        private LocationEditorPage $locationEditor,
        private TaxonomyPage $taxonomies,
        private ImportPage $importPage,
        private SettingsPage $settings
    ) {}
    public function register(): void
    {
        $main = add_menu_page(__('Business Map Locator','business-map-locator'), __('Business Map','business-map-locator'), Capabilities::EDIT_LOCATIONS, 'business-map-locator', [$this->dashboard,'render'], 'dashicons-location-alt', 26);
        $this->titles->register($main, __('Business Map Locator','business-map-locator'));
        $items = [
            ['business-map-locator', __('Overview','business-map-locator'), Capabilities::EDIT_LOCATIONS, [$this->dashboard,'render']],
            ['bml-locations', __('Locations','business-map-locator'), Capabilities::EDIT_LOCATIONS, [$this->locations,'render']],
            ['bml-categories', __('Categories','business-map-locator'), Capabilities::MANAGE_TERMS, [$this->taxonomies,'categories']],
            ['bml-cities', __('Cities','business-map-locator'), Capabilities::MANAGE_TERMS, [$this->taxonomies,'cities']],
            ['bml-import', __('Import / Export','business-map-locator'), Capabilities::MANAGE_IMPORTS, [$this->importPage,'render']],
            ['bml-settings', __('Settings','business-map-locator'), Capabilities::MANAGE_SETTINGS, [$this->settings,'render']],
        ];
        foreach ($items as [$slug,$label,$cap,$callback]) {
            $hook=add_submenu_page('business-map-locator',$label,$label,$cap,$slug,$callback);
            $this->titles->register($hook,$label);
        }
        foreach ([
            ['bml-location-edit',__('Add Location','business-map-locator'),Capabilities::EDIT_LOCATIONS,[$this->locationEditor,'render']],
            ['bml-providers',__('Map Providers','business-map-locator'),Capabilities::MANAGE_SETTINGS,[$this->settings,'render']],
            ['bml-display',__('Display','business-map-locator'),Capabilities::MANAGE_SETTINGS,[$this->settings,'render']],
            ['bml-embed',__('Gutenberg Block','business-map-locator'),Capabilities::EDIT_LOCATIONS,[$this->settings,'render']],
        ] as [$slug,$label,$cap,$callback]) {
            $hook=add_submenu_page(null,$label,$label,$cap,$slug,$callback);
            $this->titles->register($hook,$label);
        }
    }
}
