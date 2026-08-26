<?php
declare(strict_types=1);

namespace BusinessMapLocator\Domain\Location;

final class LocationServiceCatalog
{
    public function all(): array
    {
        return (array) apply_filters(
            'bml_location_services',
            [
                'parking' => __('Parking', 'business-map-locator'),
                'wifi' => __('Wi-Fi', 'business-map-locator'),
                'pickup' => __('Pickup', 'business-map-locator'),
                'delivery' => __('Delivery', 'business-map-locator'),
                'wheelchair' => __('Wheelchair access', 'business-map-locator'),
                'atm' => __('ATM', 'business-map-locator'),
                'charging' => __('Charging station', 'business-map-locator'),
            ]
        );
    }
}
