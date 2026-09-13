<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class TaxonomyFixtureSemanticsTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['bml_test_post_terms'] = [];
    }

    public function testAppendPreservesOtherTaxonomiesIsReadableAndPreventsDuplicates(): void
    {
        $GLOBALS['bml_test_post_terms'][7] = [
            'bml_city' => [1],
            'bml_area' => [8],
            'bml_category' => [3],
        ];

        wp_set_object_terms(7, [9], 'bml_area', true);

        $this->assertSame([8, 9], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids']));
        $this->assertSame([1], wp_get_post_terms(7, 'bml_city', ['fields' => 'ids']));
        $this->assertSame([3], wp_get_post_terms(7, 'bml_category', ['fields' => 'ids']));

        wp_set_object_terms(7, [9], 'bml_area', true);

        $this->assertSame([8, 9], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids']));
    }

    public function testReplacementIsTaxonomySpecific(): void
    {
        $GLOBALS['bml_test_post_terms'][7] = ['bml_city' => [1], 'bml_area' => [8, 9]];

        wp_set_object_terms(7, [10], 'bml_area');

        $this->assertSame([10], wp_get_post_terms(7, 'bml_area', ['fields' => 'ids']));
        $this->assertSame([1], wp_get_post_terms(7, 'bml_city', ['fields' => 'ids']));
    }
}
