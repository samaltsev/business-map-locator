<?php
declare(strict_types=1);

namespace BusinessMapLocator\WordPress;

final class MetaRegistrar
{
    public function register(): void
    {
        $fields = [
            'bml_address' => 'string',
            'bml_region' => 'string',
            'bml_country' => 'string',
            'bml_postcode' => 'string',
            'bml_lat' => 'number',
            'bml_lng' => 'number',
            'bml_phone' => 'string',
            'bml_email' => 'string',
            'bml_website' => 'string',
            'bml_whatsapp' => 'string',
            'bml_telegram' => 'string',
            'bml_viber' => 'string',
            'bml_facebook' => 'string',
            'bml_instagram' => 'string',
            'bml_linkedin' => 'string',
            'bml_tiktok' => 'string',
            'bml_hours' => 'string',
            'bml_services' => 'array',
            'bml_operational_status' => 'string',
        ];

        foreach ($fields as $key => $type) {
            register_post_meta('bml_location', $key, [
                'type' => $type,
                'single' => true,
                'show_in_rest' => $type === 'array'
                    ? ['schema' => ['type' => 'array', 'items' => ['type' => 'string']]]
                    : true,
                'auth_callback' => static fn (bool $allowed, string $metaKey, int $postId): bool => current_user_can(Capabilities::EDIT_LOCATION, $postId),
            ]);
        }
    }
}
