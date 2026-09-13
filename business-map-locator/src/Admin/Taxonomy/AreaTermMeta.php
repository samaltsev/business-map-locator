<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Taxonomy;

use BusinessMapLocator\WordPress\Capabilities;

if (!defined('ABSPATH')) { exit; }

/** Canonical write owner for normal bml_area administrative metadata. */
final class AreaTermMeta
{
    public const TYPE = 'bml_area_type'; public const SORT_ORDER = 'bml_sort_order'; public const ACTIVE = 'bml_area_active';
    /** @var list<string> */ private const TYPES = ['country', 'region', 'city', 'district', 'custom'];
    public function register(): void { add_action('bml_area_add_form_fields', [$this, 'addFields']); add_action('bml_area_edit_form_fields', [$this, 'editFields']); add_action('created_bml_area', [$this, 'save']); add_action('edited_bml_area', [$this, 'save']); }
    public function addFields(): void { $this->fields(0); }
    public function editFields(object $term): void { $this->fields((int) $term->term_id, true); }
    public function save(int $termId): void { if (!current_user_can(Capabilities::EDIT_AREAS) || !term_exists($termId, 'bml_area')) return; $type=$this->type(isset($_POST['bml_area_type']) ? wp_unslash($_POST['bml_area_type']) : ''); if ($type==='') delete_term_meta($termId,self::TYPE); else update_term_meta($termId,self::TYPE,$type); update_term_meta($termId,self::SORT_ORDER,$this->integer($_POST['bml_area_sort_order']??0)); update_term_meta($termId,self::ACTIVE,isset($_POST['bml_area_active']) ? ((string)$_POST['bml_area_active']==='1'?'1':'0') : '1'); }
    public function type(mixed $value): string { $value=sanitize_key((string)$value); return in_array($value,self::TYPES,true)?$value:''; }
    public function integer(mixed $value): int { return max(0,(int)$value); }
    private function fields(int $termId, bool $edit=false): void { $type=$termId?(string)get_term_meta($termId,self::TYPE,true):''; $order=$termId?(int)get_term_meta($termId,self::SORT_ORDER,true):0; $active=!$termId||(string)get_term_meta($termId,self::ACTIVE,true)!=='0'; $html='<label for="bml_area_type">'.esc_html__('Type','business-map-locator').'</label><select id="bml_area_type" name="bml_area_type"><option value="">'.esc_html__('Not set','business-map-locator').'</option>'; foreach(self::TYPES as $option)$html.='<option value="'.esc_attr($option).'"'.selected($type,$option,false).'>'.esc_html(ucfirst($option)).'</option>'; $html.='</select><p class="description">'.esc_html__('Optional Area classification.','business-map-locator').'</p><label for="bml_area_sort_order">'.esc_html__('Sort order','business-map-locator').'</label><input id="bml_area_sort_order" name="bml_area_sort_order" type="number" min="0" value="'.esc_attr((string)$order).'"><input name="bml_area_active" type="hidden" value="0"><label><input name="bml_area_active" type="checkbox" value="1"'.checked($active,true,false).'> '.esc_html__('Active','business-map-locator').'</label>'; echo $edit?'<tr class="form-field"><th scope="row">'.$html.'</th><td></td></tr>':'<div class="form-field">'.$html.'</div>'; }
}
