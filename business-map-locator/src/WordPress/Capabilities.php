<?php
declare(strict_types=1);

namespace BusinessMapLocator\WordPress;

final class Capabilities
{
    public const EDIT_LOCATIONS = 'edit_bml_locations';
    public const EDIT_LOCATION = 'edit_bml_location';
    public const EDIT_OTHERS_LOCATIONS = 'edit_others_bml_locations';
    public const EDIT_PRIVATE_LOCATIONS = 'edit_private_bml_locations';
    public const EDIT_PUBLISHED_LOCATIONS = 'edit_published_bml_locations';
    public const PUBLISH_LOCATIONS = 'publish_bml_locations';
    public const READ_LOCATION = 'read_bml_location';
    public const READ_PRIVATE_LOCATIONS = 'read_private_bml_locations';
    public const CREATE_LOCATIONS = 'create_bml_locations';
    public const DELETE_LOCATIONS = 'delete_bml_locations';
    public const DELETE_LOCATION = 'delete_bml_location';
    public const DELETE_OTHERS_LOCATIONS = 'delete_others_bml_locations';
    public const DELETE_PRIVATE_LOCATIONS = 'delete_private_bml_locations';
    public const DELETE_PUBLISHED_LOCATIONS = 'delete_published_bml_locations';

    public const MANAGE_TERMS = 'manage_bml_terms';
    public const MANAGE_AREAS = 'manage_bml_areas';
    public const EDIT_AREAS = 'edit_bml_areas';
    public const DELETE_AREAS = 'delete_bml_areas';
    public const ASSIGN_AREAS = 'assign_bml_areas';
    public const MANAGE_IMPORTS = 'manage_bml_imports';
    public const EXPORT_LOCATIONS = 'export_bml_locations';
    public const MANAGE_SETTINGS = 'manage_bml_settings';
    public const VIEW_DIAGNOSTICS = 'view_bml_diagnostics';

    /** @return list<string> */
    public static function administratorCaps(): array
    {
        return [
            self::EDIT_LOCATIONS,
            self::EDIT_LOCATION,
            self::EDIT_OTHERS_LOCATIONS,
            self::EDIT_PRIVATE_LOCATIONS,
            self::EDIT_PUBLISHED_LOCATIONS,
            self::PUBLISH_LOCATIONS,
            self::READ_LOCATION,
            self::READ_PRIVATE_LOCATIONS,
            self::CREATE_LOCATIONS,
            self::DELETE_LOCATIONS,
            self::DELETE_LOCATION,
            self::DELETE_OTHERS_LOCATIONS,
            self::DELETE_PRIVATE_LOCATIONS,
            self::DELETE_PUBLISHED_LOCATIONS,
            self::MANAGE_TERMS,
            self::MANAGE_AREAS,
            self::EDIT_AREAS,
            self::DELETE_AREAS,
            self::ASSIGN_AREAS,
            self::MANAGE_IMPORTS,
            self::EXPORT_LOCATIONS,
            self::MANAGE_SETTINGS,
            self::VIEW_DIAGNOSTICS,
        ];
    }
}
