<?php
declare(strict_types=1);

use BusinessMapLocator\Admin\Taxonomy\AreaTermMeta;
use PHPUnit\Framework\TestCase;

if (!function_exists('wp_unslash')) { function wp_unslash(mixed $value): mixed { return $value; } }
if (!function_exists('current_user_can')) { function current_user_can(string $capability): bool { return $GLOBALS['bml_test_capabilities'][$capability] ?? true; } }

final class AreaAdminTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['bml_test_terms']=['bml_city'=>[9=>(object)['term_id'=>9,'name'=>'Legacy city','slug'=>'legacy-city','parent'=>0,'count'=>0]],'bml_area'=>[1=>(object)['term_id'=>1,'name'=>'Belarus','slug'=>'belarus','parent'=>0,'count'=>0],2=>(object)['term_id'=>2,'name'=>'Minsk Region','slug'=>'minsk-region','parent'=>1,'count'=>0],3=>(object)['term_id'=>3,'name'=>'Minsk','slug'=>'minsk','parent'=>2,'count'=>0]]];
        $GLOBALS['bml_test_term_meta']=[3=>['_bml_migrated_from_city_term_id'=>9]]; $GLOBALS['bml_test_capabilities']=[]; $_POST=[];
    }
    public function testAreaTaxonomyAndMenuUseCanonicalNativeSurface(): void
    {
        $types=file_get_contents(dirname(__DIR__).'/src/WordPress/ContentTypes.php'); $menu=file_get_contents(dirname(__DIR__).'/src/Admin/Menu/AdminMenu.php');
        $this->assertStringContainsString("register_taxonomy('bml_area'",$types); $this->assertStringContainsString("'hierarchical' => true",$types); $this->assertStringContainsString('taxonomy=bml_area',$menu);
    }
    public function testValidTypeSortAndActivePersistAndProvenanceSurvivesEdit(): void
    {
        $_POST=['bml_area_type'=>'district','bml_area_sort_order'=>'12','bml_area_active'=>'0']; (new AreaTermMeta())->save(3);
        $this->assertSame('district',get_term_meta(3,AreaTermMeta::TYPE,true)); $this->assertSame(12,get_term_meta(3,AreaTermMeta::SORT_ORDER,true)); $this->assertSame('0',get_term_meta(3,AreaTermMeta::ACTIVE,true)); $this->assertSame(9,get_term_meta(3,'_bml_migrated_from_city_term_id',true));
    }
    public function testTypeAllowlistAndSortNormalization(): void
    {
        $writer=new AreaTermMeta(); foreach(['country','region','city','district','custom'] as $type)$this->assertSame($type,$writer->type($type)); $this->assertSame('',$writer->type('foobar')); $this->assertSame(0,$writer->integer('invalid')); $this->assertSame(0,$writer->integer(-5)); $this->assertSame(7,$writer->integer('7'));
    }
    public function testUnsetDefaultsActiveAndInvalidTypeDoesNotPersist(): void
    {
        $_POST=['bml_area_type'=>'foobar']; (new AreaTermMeta())->save(2); $this->assertSame('',get_term_meta(2,AreaTermMeta::TYPE,true)); $this->assertSame('1',get_term_meta(2,AreaTermMeta::ACTIVE,true)); $this->assertSame(0,get_term_meta(2,AreaTermMeta::SORT_ORDER,true));
    }
    public function testParentAndCityFixturesRemainUntouchedByAreaWrite(): void
    {
        $_POST=['bml_area_type'=>'city','bml_area_sort_order'=>'1','bml_area_active'=>'1']; (new AreaTermMeta())->save(3); $this->assertSame(2,$GLOBALS['bml_test_terms']['bml_area'][3]->parent); $this->assertCount(1,$GLOBALS['bml_test_terms']['bml_city']); $this->assertSame('legacy-city',$GLOBALS['bml_test_terms']['bml_city'][9]->slug);
    }
    public function testNormalAreaDeletionDoesNotDeleteLocationFixture(): void
    {
        $post=new WP_Post();$post->ID=7;$post->post_type='bml_location';$GLOBALS['bml_test_posts']=[7=>$post];$GLOBALS['bml_test_post_terms']=[7=>['bml_area'=>[3]]]; wp_delete_term(3,'bml_area'); $this->assertSame($post,get_post(7));
    }
}
