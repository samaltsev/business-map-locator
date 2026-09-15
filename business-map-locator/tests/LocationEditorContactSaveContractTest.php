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
        self::assertStringContainsString('if (field(name)) { data.set(name, value(name)); }', $source);
    }

    public function testStructuredHoursEditorPreservesTheCanonicalHoursField(): void
    {
        $view = file_get_contents(dirname(__DIR__) . '/src/Admin/Location/View/location-editor.php');
        $script = file_get_contents(dirname(__DIR__) . '/assets/js/admin/location-editor.js');

        self::assertIsString($view);
        self::assertIsString($script);
        self::assertStringContainsString('data-bml-hours-editor', $view);
        self::assertStringContainsString('data-hours-output', $view);
        self::assertStringContainsString('data-hours-copy-source', $view);
        self::assertStringContainsString('data-hours-copy-target', $view);
        self::assertStringContainsString('data-hours-copy-apply', $view);
        self::assertStringContainsString('function initHoursEditor()', $script);
        self::assertStringContainsString("output.value = lines.join('\\n');", $script);
        self::assertStringContainsString('function beginCopyMode(row)', $script);
        self::assertStringContainsString('function applyCopy()', $script);
        self::assertStringContainsString('markDirty(copySource);', $script);
    }

    public function testAjaxSaveAcceptsLegacyContactFieldNames(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/Admin/Ajax/LocationEditorAjaxController.php');

        self::assertIsString($source);
        self::assertStringContainsString("foreach (['address', 'region', 'country', 'postcode', 'phone', 'email', 'website', 'hours'] as \$field)", $source);
        self::assertStringContainsString("\$legacy = 'bml_location_' . \$field;", $source);
        self::assertStringContainsString('!isset($input[$field]) && isset($_POST[$legacy])', $source);
    }

    public function testFreeLocationEditorFocusesOnCoreFieldsAndExplainsSiteTimezone(): void
    {
        $view = file_get_contents(dirname(__DIR__) . '/src/Admin/Location/View/location-editor.php');

        self::assertIsString($view);
        self::assertStringContainsString("Hours and description", $view);
        self::assertStringContainsString("WordPress site timezone", $view);
        self::assertStringNotContainsString('name="area_id"', $view);
        self::assertStringNotContainsString('name="bml_location_phone"', $view);
        self::assertStringNotContainsString('name="bml_location_email"', $view);
        self::assertStringNotContainsString('name="bml_location_website"', $view);
    }
}
