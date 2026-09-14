<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LocationEditorContactSaveContractTest extends TestCase
{
    public function testEditorSubmitsCanonicalContactFieldsForAjaxSave(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/assets/js/admin/location-editor.js');

        self::assertIsString($source);
        self::assertStringContainsString("['address', 'region', 'country', 'postcode', 'phone', 'email', 'website', 'hours'].forEach", $source);
        self::assertStringContainsString("form.elements['bml_location_' + name]", $source);
        self::assertStringContainsString('data.set(name, value(name));', $source);
    }

    public function testAjaxSaveAcceptsLegacyContactFieldNames(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/Admin/Ajax/LocationEditorAjaxController.php');

        self::assertIsString($source);
        self::assertStringContainsString("foreach (['address', 'region', 'country', 'postcode', 'phone', 'email', 'website', 'hours'] as \$field)", $source);
        self::assertStringContainsString("\$legacy = 'bml_location_' . \$field;", $source);
        self::assertStringContainsString('!isset($input[$field]) && isset($_POST[$legacy])', $source);
    }
}
