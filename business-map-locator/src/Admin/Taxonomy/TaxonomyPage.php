<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Taxonomy;

use BusinessMapLocator\Admin\Shared\AdminShell;

if (!defined('ABSPATH')) {
    exit;
}

final class TaxonomyPage
{
    public function __construct(private AdminShell $shell)
    {
    }

    public function categories(): void
    {
        $this->renderCategories();
    }

    public function cities(): void
    {
        $this->renderCities();
    }

    private function renderCategories(): void
    {
        $editId = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editTerm = $editId ? get_term($editId, 'bml_category') : null;
        $editing = $editTerm && !is_wp_error($editTerm);
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $filter = isset($_GET['filter']) ? sanitize_key(wp_unslash($_GET['filter'])) : 'all';
        $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'manual';
        $order = strtoupper(sanitize_text_field(wp_unslash($_GET['order'] ?? 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';
        $paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $perPage = 20;

        if (!in_array($filter, ['all', 'used', 'unused', 'no-icon'], true)) {
            $filter = 'all';
        }
        if (!in_array($orderby, ['name', 'count', 'manual'], true)) {
            $orderby = 'manual';
        }

        $allTerms = get_terms([
            'taxonomy' => 'bml_category',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        $allTerms = is_wp_error($allTerms) ? [] : $allTerms;

        $usedCount = 0;
        $unusedCount = 0;
        $withoutIconCount = 0;
        $totalLocations = 0;
        foreach ($allTerms as $term) {
            $totalLocations += (int) $term->count;
            (int) $term->count > 0 ? $usedCount++ : $unusedCount++;
            if (!(int) get_term_meta($term->term_id, 'bml_icon_id', true)) {
                $withoutIconCount++;
            }
        }

        $searchNeedle = self::normalizeSearchText($search);
        $filteredTerms = array_values(array_filter($allTerms, static function ($term) use ($searchNeedle, $filter): bool {
            if ($searchNeedle !== '' && strpos(self::normalizeSearchText($term->name . ' ' . rawurldecode($term->slug) . ' ' . $term->description), $searchNeedle) === false) {
                return false;
            }
            if ($filter === 'used') {
                return (int) $term->count > 0;
            }
            if ($filter === 'unused') {
                return (int) $term->count === 0;
            }
            if ($filter === 'no-icon') {
                return !(int) get_term_meta($term->term_id, 'bml_icon_id', true);
            }
            return true;
        }));

        usort($filteredTerms, static function ($a, $b) use ($orderby, $order): int {
            if ($orderby === 'count') {
                $result = (int) $a->count <=> (int) $b->count;
            } elseif ($orderby === 'manual') {
                $result = (int) get_term_meta($a->term_id, 'bml_sort_order', true) <=> (int) get_term_meta($b->term_id, 'bml_sort_order', true);
                if ($result === 0) {
                    $result = strcasecmp($a->name, $b->name);
                }
            } else {
                $result = strcasecmp($a->name, $b->name);
            }
            return $order === 'DESC' ? -$result : $result;
        });

        $totalFiltered = count($filteredTerms);
        $totalPages = max(1, (int) ceil($totalFiltered / $perPage));
        $paged = min($paged, $totalPages);
        $terms = array_slice($filteredTerms, ($paged - 1) * $perPage, $perPage);
        $iconId = $editing ? (int) get_term_meta($editTerm->term_id, 'bml_icon_id', true) : 0;
        $sortOrder = $editing ? (int) get_term_meta($editTerm->term_id, 'bml_sort_order', true) : 0;

        $addUrl = admin_url('admin.php?page=bml-categories#bml-category-editor');
        $this->shell->start(
            __('Categories', 'business-map-locator'),
            __('Organize location types, map markers and frontend filters from one workspace.', 'business-map-locator'),
            __('Add category', 'business-map-locator'),
            $addUrl,
            __('View locations', 'business-map-locator'),
            admin_url('admin.php?page=bml-locations'),
            'dashicons-location'
        );
        ?>
        <section class="bml-category-metrics" aria-label="<?php esc_attr_e('Category overview', 'business-map-locator'); ?>">
            <?php $this->metricCard('dashicons-category', __('Categories', 'business-map-locator'), count($allTerms), __('Available in filters', 'business-map-locator'), 'blue'); ?>
            <?php $this->metricCard('dashicons-yes-alt', __('Used categories', 'business-map-locator'), $usedCount, __('Assigned to locations', 'business-map-locator'), 'green'); ?>
            <?php $this->metricCard('dashicons-warning', __('Unused categories', 'business-map-locator'), $unusedCount, __('Safe to review', 'business-map-locator'), 'amber'); ?>
            <?php $this->metricCard('dashicons-format-image', __('Without icon', 'business-map-locator'), $withoutIconCount, __('Using default marker', 'business-map-locator'), 'violet'); ?>
        </section>

        <section class="bml-category-attention <?php echo ($unusedCount + $withoutIconCount) === 0 ? 'is-complete' : ''; ?>">
            <div class="bml-category-attention__icon"><span class="dashicons <?php echo ($unusedCount + $withoutIconCount) === 0 ? 'dashicons-yes-alt' : 'dashicons-info-outline'; ?>"></span></div>
            <div>
                <span class="bml-eyebrow"><?php esc_html_e('Needs attention', 'business-map-locator'); ?></span>
                <?php if (($unusedCount + $withoutIconCount) === 0) : ?>
                    <h2><?php esc_html_e('Your category directory is complete', 'business-map-locator'); ?></h2>
                    <p><?php esc_html_e('Every category is used and has a custom map icon.', 'business-map-locator'); ?></p>
                <?php else : ?>
                    <h2><?php esc_html_e('A few items can be improved', 'business-map-locator'); ?></h2>
                    <div class="bml-category-attention__links">
                        <?php if ($unusedCount > 0) : ?><a href="<?php echo esc_url(admin_url('admin.php?page=bml-categories&filter=unused')); ?>"><?php echo esc_html(sprintf(_n('%d unused category', '%d unused categories', $unusedCount, 'business-map-locator'), $unusedCount)); ?></a><?php endif; ?>
                        <?php if ($withoutIconCount > 0) : ?><a href="<?php echo esc_url(admin_url('admin.php?page=bml-categories&filter=no-icon')); ?>"><?php echo esc_html(sprintf(_n('%d category without icon', '%d categories without icons', $withoutIconCount, 'business-map-locator'), $withoutIconCount)); ?></a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="bml-category-attention__total"><strong><?php echo esc_html(number_format_i18n($totalLocations)); ?></strong><small><?php esc_html_e('linked locations', 'business-map-locator'); ?></small></div>
        </section>

        <div class="bml-category-workspace">
            <article class="bml-panel bml-category-directory">
                <div class="bml-panel__head bml-panel__head--pad">
                    <div>
                        <span class="bml-eyebrow"><?php esc_html_e('Directory', 'business-map-locator'); ?></span>
                        <h2><?php echo esc_html(sprintf(_n('%d category', '%d categories', $totalFiltered, 'business-map-locator'), $totalFiltered)); ?></h2>
                    </div>
                    <span class="bml-category-directory__hint"><kbd>/</kbd> <?php esc_html_e('Search', 'business-map-locator'); ?></span>
                </div>

                <form class="bml-category-toolbar" method="get" data-bml-category-toolbar>
                    <input type="hidden" name="page" value="bml-categories">
                    <label class="bml-search bml-category-search">
                        <span class="dashicons dashicons-search"></span>
                        <input id="bml-category-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search by name, slug or description', 'business-map-locator'); ?>" autocomplete="off">
                    </label>
                    <select name="orderby" aria-label="<?php esc_attr_e('Sort categories', 'business-map-locator'); ?>">
                        <option value="manual" <?php selected($orderby, 'manual'); ?>><?php esc_html_e('Manual order', 'business-map-locator'); ?></option>
                        <option value="name" <?php selected($orderby, 'name'); ?>><?php esc_html_e('Alphabetical', 'business-map-locator'); ?></option>
                        <option value="count" <?php selected($orderby, 'count'); ?>><?php esc_html_e('Locations count', 'business-map-locator'); ?></option>
                    </select>
                    <select name="order" aria-label="<?php esc_attr_e('Sort direction', 'business-map-locator'); ?>">
                        <option value="ASC" <?php selected($order, 'ASC'); ?>><?php esc_html_e('Ascending', 'business-map-locator'); ?></option>
                        <option value="DESC" <?php selected($order, 'DESC'); ?>><?php esc_html_e('Descending', 'business-map-locator'); ?></option>
                    </select>
                    <button class="bml-btn bml-btn--secondary" type="submit"><?php esc_html_e('Apply', 'business-map-locator'); ?></button>
                </form>

                <nav class="bml-category-filters" aria-label="<?php esc_attr_e('Category filters', 'business-map-locator'); ?>">
                    <?php $this->filterChip('all', __('All', 'business-map-locator'), count($allTerms), $filter); ?>
                    <?php $this->filterChip('used', __('Used', 'business-map-locator'), $usedCount, $filter); ?>
                    <?php $this->filterChip('unused', __('Unused', 'business-map-locator'), $unusedCount, $filter); ?>
                    <?php $this->filterChip('no-icon', __('Without icon', 'business-map-locator'), $withoutIconCount, $filter); ?>
                </nav>

                <div class="bml-table-wrap">
                    <table class="bml-table bml-category-table" data-bml-category-table>
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Category', 'business-map-locator'); ?></th>
                                <th><?php esc_html_e('Locations', 'business-map-locator'); ?></th>
                                <th><?php esc_html_e('Status', 'business-map-locator'); ?></th>
                                <th><?php esc_html_e('Order', 'business-map-locator'); ?></th>
                                <th><span class="screen-reader-text"><?php esc_html_e('Actions', 'business-map-locator'); ?></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($terms) : foreach ($terms as $term) :
                                $termIconId = (int) get_term_meta($term->term_id, 'bml_icon_id', true);
                                $termOrder = (int) get_term_meta($term->term_id, 'bml_sort_order', true);
                                $editUrl = admin_url('admin.php?page=bml-categories&edit=' . $term->term_id . '#bml-category-editor');
                                $locationsUrl = admin_url('admin.php?page=bml-locations&category=' . rawurlencode($term->slug));
                                $duplicateUrl = wp_nonce_url(admin_url('admin-post.php?action=bml_duplicate_term&term_id=' . $term->term_id), 'bml_duplicate_term_' . $term->term_id);
                                $deleteUrl = wp_nonce_url(admin_url('admin-post.php?action=bml_delete_term&taxonomy=bml_category&term_id=' . $term->term_id), 'bml_handle_' . $term->term_id);
                                ?>
                                <tr data-category-search="<?php echo esc_attr(strtolower($term->name . ' ' . $term->slug . ' ' . $term->description)); ?>">
                                    <td>
                                        <div class="bml-category-identity">
                                            <span class="bml-category-identity__icon <?php echo $termIconId ? 'has-image' : ''; ?>">
                                                <?php echo $termIconId ? wp_get_attachment_image($termIconId, 'bml_category_icon', false, ['alt' => '']) : '<span class="dashicons dashicons-location-alt"></span>'; ?>
                                            </span>
                                            <span><strong><?php echo esc_html($term->name); ?></strong><small><?php echo esc_html($term->description ?: $term->slug); ?></small></span>
                                        </div>
                                    </td>
                                    <td><a class="bml-category-count" href="<?php echo esc_url($locationsUrl); ?>"><strong><?php echo esc_html(number_format_i18n((int) $term->count)); ?></strong><small><?php esc_html_e('View locations', 'business-map-locator'); ?></small></a></td>
                                    <td><span class="bml-status-pill <?php echo (int) $term->count > 0 ? 'is-active' : 'is-unused'; ?>"><?php echo (int) $term->count > 0 ? esc_html__('Active', 'business-map-locator') : esc_html__('Unused', 'business-map-locator'); ?></span></td>
                                    <td><span class="bml-order-badge"><?php echo esc_html((string) $termOrder); ?></span></td>
                                    <td>
                                        <div class="bml-row-primary-actions">
                                            <a href="<?php echo esc_url($editUrl); ?>"><?php esc_html_e('Edit', 'business-map-locator'); ?></a>
                                            <details>
                                                <summary aria-label="<?php esc_attr_e('More actions', 'business-map-locator'); ?>">⋮</summary>
                                                <div class="bml-row-menu">
                                                    <a href="<?php echo esc_url($locationsUrl); ?>"><?php esc_html_e('View locations', 'business-map-locator'); ?></a>
                                                    <a href="<?php echo esc_url($duplicateUrl); ?>"><?php esc_html_e('Duplicate', 'business-map-locator'); ?></a>
                                                    <?php if ((int) $term->count === 0) : ?><a class="bml-delete-link" href="<?php echo esc_url($deleteUrl); ?>"><?php esc_html_e('Delete', 'business-map-locator'); ?></a><?php else : ?><span class="bml-row-menu__disabled" title="<?php esc_attr_e('Reassign locations before deleting this category.', 'business-map-locator'); ?>"><?php esc_html_e('Delete unavailable', 'business-map-locator'); ?></span><?php endif; ?>
                                                </div>
                                            </details>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr class="bml-category-empty-row"><td colspan="5"><div class="bml-category-empty"><span class="dashicons dashicons-category"></span><strong><?php esc_html_e('No categories found', 'business-map-locator'); ?></strong><p><?php esc_html_e('Try another filter or create a new category.', 'business-map-locator'); ?></p><a class="bml-btn bml-btn--primary" href="<?php echo esc_url($addUrl); ?>"><?php esc_html_e('Add category', 'business-map-locator'); ?></a></div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bml-category-live-empty" hidden data-bml-category-empty-live><?php esc_html_e('No categories match your search.', 'business-map-locator'); ?></div>

                <?php if ($totalPages > 1) :
                    $paginationBase = add_query_arg(array_filter([
                        'page' => 'bml-categories',
                        's' => $search ?: null,
                        'filter' => $filter !== 'all' ? $filter : null,
                        'orderby' => $orderby !== 'manual' ? $orderby : null,
                        'order' => $order !== 'ASC' ? $order : null,
                        'paged' => '%#%',
                    ]), admin_url('admin.php'));
                    ?>
                    <div class="bml-pagination"><span><?php echo esc_html(sprintf(__('Page %1$d of %2$d', 'business-map-locator'), $paged, $totalPages)); ?></span><?php echo wp_kses_post(paginate_links(['base' => $paginationBase, 'format' => '', 'current' => $paged, 'total' => $totalPages, 'type' => 'list'])); ?></div>
                <?php endif; ?>
            </article>

            <aside class="bml-category-editor-column" id="bml-category-editor">
                <form class="bml-category-editor" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-bml-category-editor>
                    <input type="hidden" name="action" value="bml_save_term">
                    <input type="hidden" name="taxonomy" value="bml_category">
                    <input type="hidden" name="term_id" value="<?php echo esc_attr((string) ($editing ? $editTerm->term_id : 0)); ?>">
                    <?php wp_nonce_field('bml_handle'); ?>

                    <article class="bml-panel bml-category-editor-card">
                        <div class="bml-panel__head">
                            <div><span class="bml-eyebrow"><?php echo $editing ? esc_html__('Edit category', 'business-map-locator') : esc_html__('New category', 'business-map-locator'); ?></span><h2><?php esc_html_e('Category details', 'business-map-locator'); ?></h2></div>
                            <?php if ($editing) : ?><a class="bml-btn bml-btn--text" href="<?php echo esc_url(admin_url('admin.php?page=bml-categories#bml-category-editor')); ?>"><?php esc_html_e('Add new', 'business-map-locator'); ?></a><?php endif; ?>
                        </div>
                        <div class="bml-category-editor__body">
                            <label class="bml-field"><span><?php esc_html_e('Name', 'business-map-locator'); ?> *</span><input id="bml-category-name" name="name" required value="<?php echo esc_attr($editing ? $editTerm->name : ''); ?>" placeholder="<?php esc_attr_e('e.g. Pharmacy', 'business-map-locator'); ?>"></label>
                            <label class="bml-field"><span><?php esc_html_e('Slug', 'business-map-locator'); ?></span><input id="bml-category-slug" name="slug" value="<?php echo esc_attr($editing ? $editTerm->slug : ''); ?>" placeholder="<?php esc_attr_e('Generated automatically', 'business-map-locator'); ?>"><small><?php esc_html_e('Used in filter URLs and CSV imports.', 'business-map-locator'); ?></small></label>
                            <label class="bml-field"><span><?php esc_html_e('Description', 'business-map-locator'); ?></span><textarea id="bml-category-description" name="description" rows="3" placeholder="<?php esc_attr_e('Explain what locations belong to this category.', 'business-map-locator'); ?>"><?php echo esc_textarea($editing ? $editTerm->description : ''); ?></textarea></label>
                            <label class="bml-field"><span><?php esc_html_e('Manual order', 'business-map-locator'); ?></span><input name="sort_order" type="number" step="1" value="<?php echo esc_attr((string) $sortOrder); ?>"><small><?php esc_html_e('Lower values appear first when manual sorting is active.', 'business-map-locator'); ?></small></label>
                        </div>
                    </article>

                    <article class="bml-panel bml-category-editor-card">
                        <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Appearance', 'business-map-locator'); ?></span><h2><?php esc_html_e('Map icon', 'business-map-locator'); ?></h2></div></div>
                        <div class="bml-category-icon-workspace">
                            <input id="bml_category_icon_id" type="hidden" name="icon_id" value="<?php echo esc_attr((string) $iconId); ?>">
                            <div id="bml-category-icon-preview" class="bml-category-icon-preview bml-category-icon-preview--large">
                                <?php echo $iconId ? wp_get_attachment_image($iconId, 'bml_category_icon', false, ['alt' => '', 'class' => 'bml-category-icon-preview__image']) : '<span class="dashicons dashicons-location-alt"></span>'; ?>
                            </div>
                            <div><strong><?php esc_html_e('Marker icon', 'business-map-locator'); ?></strong><p><?php esc_html_e('A square transparent image works best. The plugin uses a cropped 64 × 64 version on the map.', 'business-map-locator'); ?></p><div class="bml-actions"><button id="bml-select-category-icon" class="bml-btn bml-btn--secondary" type="button"><?php esc_html_e('Select icon', 'business-map-locator'); ?></button><button id="bml-remove-category-icon" class="bml-btn bml-btn--text" type="button"><?php esc_html_e('Remove', 'business-map-locator'); ?></button></div></div>
                        </div>
                    </article>

                    <article class="bml-panel bml-category-preview-panel">
                        <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Live preview', 'business-map-locator'); ?></span><h2><?php esc_html_e('Frontend appearance', 'business-map-locator'); ?></h2></div></div>
                        <div class="bml-category-preview">
                            <span class="bml-category-preview__icon" data-bml-category-preview-icon><?php echo $iconId ? wp_get_attachment_image($iconId, 'bml_category_icon', false, ['alt' => '']) : '<span class="dashicons dashicons-location-alt"></span>'; ?></span>
                            <span><strong data-bml-category-preview-name><?php echo esc_html($editing ? $editTerm->name : __('Category name', 'business-map-locator')); ?></strong><small data-bml-category-preview-description><?php echo esc_html($editing && $editTerm->description ? $editTerm->description : __('Shown in map filters and location cards.', 'business-map-locator')); ?></small></span>
                            <span class="bml-category-preview__count"><?php echo esc_html(number_format_i18n($editing ? (int) $editTerm->count : 0)); ?></span>
                        </div>
                    </article>

                    <div class="bml-category-savebar">
                        <div><strong><?php echo $editing ? esc_html__('Editing category', 'business-map-locator') : esc_html__('Create category', 'business-map-locator'); ?></strong><small data-bml-category-save-state><?php esc_html_e('Changes are not saved yet.', 'business-map-locator'); ?></small></div>
                        <div class="bml-actions"><?php if ($editing) : ?><a class="bml-btn bml-btn--text" href="<?php echo esc_url(admin_url('admin.php?page=bml-categories')); ?>"><?php esc_html_e('Cancel', 'business-map-locator'); ?></a><?php endif; ?><button class="bml-btn bml-btn--primary" type="submit"><?php echo $editing ? esc_html__('Update category', 'business-map-locator') : esc_html__('Add category', 'business-map-locator'); ?></button></div>
                    </div>
                </form>
            </aside>
        </div>
        <?php
        $this->shell->end();
    }

    private function metricCard(string $icon, string $label, int $value, string $note, string $tone): void
    {
        ?><div class="bml-category-metric bml-category-metric--<?php echo esc_attr($tone); ?>"><span class="dashicons <?php echo esc_attr($icon); ?>"></span><div><small><?php echo esc_html($label); ?></small><strong><?php echo esc_html(number_format_i18n($value)); ?></strong><em><?php echo esc_html($note); ?></em></div></div><?php
    }

    private function filterChip(string $value, string $label, int $count, string $active): void
    {
        $url = add_query_arg(['page' => 'bml-categories', 'filter' => $value === 'all' ? null : $value], admin_url('admin.php'));
        ?><a class="<?php echo $active === $value ? 'is-active' : ''; ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?><span><?php echo esc_html(number_format_i18n($count)); ?></span></a><?php
    }


    private function renderCities(): void
    {
        $taxonomy = 'bml_city';
        $editId = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editTerm = $editId ? get_term($editId, $taxonomy) : null;
        $editing = $editTerm && !is_wp_error($editTerm);
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $filter = isset($_GET['filter']) ? sanitize_key(wp_unslash($_GET['filter'])) : 'all';
        $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'name';
        $order = strtoupper(sanitize_text_field(wp_unslash($_GET['order'] ?? 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';
        $paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $perPage = 25;

        if (!in_array($filter, ['all', 'used', 'unused', 'large'], true)) { $filter = 'all'; }
        if (!in_array($orderby, ['name', 'count', 'manual'], true)) { $orderby = 'name'; }

        $allTerms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        $allTerms = is_wp_error($allTerms) ? [] : $allTerms;
        $usedCount = 0; $unusedCount = 0; $largeCount = 0; $linkedLocations = 0;
        foreach ($allTerms as $term) {
            $count = (int) $term->count;
            $linkedLocations += $count;
            $count > 0 ? $usedCount++ : $unusedCount++;
            if ($count >= 20) { $largeCount++; }
        }

        $searchNeedle = self::normalizeSearchText($search);
        $filteredTerms = array_values(array_filter($allTerms, static function ($term) use ($searchNeedle, $filter): bool {
            if ($searchNeedle !== '' && strpos(self::normalizeSearchText($term->name . ' ' . rawurldecode($term->slug) . ' ' . $term->description), $searchNeedle) === false) { return false; }
            $count = (int) $term->count;
            if ($filter === 'used') { return $count > 0; }
            if ($filter === 'unused') { return $count === 0; }
            if ($filter === 'large') { return $count >= 20; }
            return true;
        }));

        usort($filteredTerms, static function ($a, $b) use ($orderby, $order): int {
            if ($orderby === 'count') { $result = (int) $a->count <=> (int) $b->count; }
            elseif ($orderby === 'manual') {
                $result = (int) get_term_meta($a->term_id, 'bml_sort_order', true) <=> (int) get_term_meta($b->term_id, 'bml_sort_order', true);
                if ($result === 0) { $result = strcasecmp($a->name, $b->name); }
            } else { $result = strcasecmp($a->name, $b->name); }
            return $order === 'DESC' ? -$result : $result;
        });

        $totalFiltered = count($filteredTerms);
        $totalPages = max(1, (int) ceil($totalFiltered / $perPage));
        $paged = min($paged, $totalPages);
        $terms = array_slice($filteredTerms, ($paged - 1) * $perPage, $perPage);
        $sortOrder = $editing ? (int) get_term_meta($editTerm->term_id, 'bml_sort_order', true) : 0;

        $this->shell->start(
            __('Cities', 'business-map-locator'),
            __('Manage the geographic directory used by locations, filters and CSV imports.', 'business-map-locator'),
            __('Add city', 'business-map-locator'),
            admin_url('admin.php?page=bml-cities#bml-city-editor'),
            __('View locations', 'business-map-locator'),
            admin_url('admin.php?page=bml-locations'),
            'dashicons-location'
        );
        ?>
        <section class="bml-city-metrics" aria-label="<?php esc_attr_e('City overview', 'business-map-locator'); ?>">
            <?php $this->cityMetric('dashicons-admin-site-alt3', __('Cities', 'business-map-locator'), count($allTerms), __('Available in filters', 'business-map-locator'), 'blue'); ?>
            <?php $this->cityMetric('dashicons-yes-alt', __('Used cities', 'business-map-locator'), $usedCount, __('Contain locations', 'business-map-locator'), 'green'); ?>
            <?php $this->cityMetric('dashicons-warning', __('Unused cities', 'business-map-locator'), $unusedCount, __('Safe to review', 'business-map-locator'), 'amber'); ?>
            <?php $this->cityMetric('dashicons-location-alt', __('Linked locations', 'business-map-locator'), $linkedLocations, __('Across all cities', 'business-map-locator'), 'violet'); ?>
        </section>

        <section class="bml-city-attention <?php echo $unusedCount === 0 ? 'is-complete' : ''; ?>">
            <span class="bml-city-attention__icon dashicons <?php echo $unusedCount === 0 ? 'dashicons-yes-alt' : 'dashicons-info-outline'; ?>"></span>
            <div><span class="bml-eyebrow"><?php esc_html_e('Directory health', 'business-map-locator'); ?></span>
                <?php if ($unusedCount === 0) : ?><h2><?php esc_html_e('Every city is connected to a location', 'business-map-locator'); ?></h2><p><?php esc_html_e('The city directory is clean and ready for frontend filters.', 'business-map-locator'); ?></p>
                <?php else : ?><h2><?php echo esc_html(sprintf(_n('%d unused city needs review', '%d unused cities need review', $unusedCount, 'business-map-locator'), $unusedCount)); ?></h2><p><?php esc_html_e('Unused cities can be deleted safely or kept for upcoming imports.', 'business-map-locator'); ?></p><?php endif; ?>
            </div>
            <a class="bml-btn bml-btn--secondary" href="<?php echo esc_url(admin_url('admin.php?page=bml-cities&filter=unused')); ?>"><?php esc_html_e('Review unused', 'business-map-locator'); ?></a>
        </section>

        <div class="bml-city-workspace">
            <article class="bml-panel bml-city-directory">
                <div class="bml-panel__head bml-panel__head--pad"><div><span class="bml-eyebrow"><?php esc_html_e('Directory', 'business-map-locator'); ?></span><h2><?php echo esc_html(sprintf(_n('%d city', '%d cities', $totalFiltered, 'business-map-locator'), $totalFiltered)); ?></h2></div><span class="bml-city-shortcut"><kbd>/</kbd> <?php esc_html_e('Search', 'business-map-locator'); ?></span></div>
                <form class="bml-city-toolbar" method="get" data-bml-city-toolbar>
                    <input type="hidden" name="page" value="bml-cities">
                    <label class="bml-search"><span class="dashicons dashicons-search"></span><input id="bml-city-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search cities by name or slug', 'business-map-locator'); ?>" autocomplete="off"></label>
                    <select name="orderby"><option value="name" <?php selected($orderby, 'name'); ?>><?php esc_html_e('Alphabetical', 'business-map-locator'); ?></option><option value="count" <?php selected($orderby, 'count'); ?>><?php esc_html_e('Locations count', 'business-map-locator'); ?></option><option value="manual" <?php selected($orderby, 'manual'); ?>><?php esc_html_e('Manual order', 'business-map-locator'); ?></option></select>
                    <select name="order"><option value="ASC" <?php selected($order, 'ASC'); ?>><?php esc_html_e('Ascending', 'business-map-locator'); ?></option><option value="DESC" <?php selected($order, 'DESC'); ?>><?php esc_html_e('Descending', 'business-map-locator'); ?></option></select>
                    <button class="bml-btn bml-btn--secondary" type="submit"><?php esc_html_e('Apply', 'business-map-locator'); ?></button>
                </form>
                <nav class="bml-city-filters" aria-label="<?php esc_attr_e('City filters', 'business-map-locator'); ?>">
                    <?php $this->cityFilter('all', __('All', 'business-map-locator'), count($allTerms), $filter); ?>
                    <?php $this->cityFilter('used', __('Used', 'business-map-locator'), $usedCount, $filter); ?>
                    <?php $this->cityFilter('unused', __('Unused', 'business-map-locator'), $unusedCount, $filter); ?>
                    <?php $this->cityFilter('large', __('20+ locations', 'business-map-locator'), $largeCount, $filter); ?>
                </nav>
                <div class="bml-table-wrap"><table class="bml-table bml-city-table"><thead><tr><th><?php esc_html_e('City', 'business-map-locator'); ?></th><th><?php esc_html_e('Slug', 'business-map-locator'); ?></th><th><?php esc_html_e('Locations', 'business-map-locator'); ?></th><th><?php esc_html_e('Status', 'business-map-locator'); ?></th><th><?php esc_html_e('Order', 'business-map-locator'); ?></th><th></th></tr></thead><tbody>
                <?php if (!$terms) : ?><tr><td colspan="6"><div class="bml-city-empty"><span class="dashicons dashicons-admin-site-alt3"></span><strong><?php esc_html_e('No cities found', 'business-map-locator'); ?></strong><p><?php esc_html_e('Try another search or create a new city.', 'business-map-locator'); ?></p></div></td></tr>
                <?php else : foreach ($terms as $term) : $count=(int)$term->count; $orderValue=(int)get_term_meta($term->term_id,'bml_sort_order',true); ?>
                    <tr data-city-name="<?php echo esc_attr(strtolower($term->name . ' ' . $term->slug)); ?>"><td><div class="bml-city-identity"><span class="dashicons dashicons-location-alt"></span><span><strong><?php echo esc_html($term->name); ?></strong><small><?php echo $count ? esc_html(sprintf(_n('%d location', '%d locations', $count, 'business-map-locator'), $count)) : esc_html__('No linked locations', 'business-map-locator'); ?></small></span></div></td><td><code><?php echo esc_html($term->slug); ?></code></td><td><a class="bml-city-count" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&city=' . rawurlencode($term->slug))); ?>"><strong><?php echo esc_html(number_format_i18n($count)); ?></strong><small><?php esc_html_e('View locations', 'business-map-locator'); ?></small></a></td><td><span class="bml-status-pill <?php echo $count ? 'is-active' : 'is-unused'; ?>"><?php echo $count ? esc_html__('Used', 'business-map-locator') : esc_html__('Unused', 'business-map-locator'); ?></span></td><td><span class="bml-order-badge"><?php echo esc_html((string)$orderValue); ?></span></td><td><div class="bml-row-actions"><a href="<?php echo esc_url(admin_url('admin.php?page=bml-cities&edit=' . $term->term_id . '#bml-city-editor')); ?>"><?php esc_html_e('Edit', 'business-map-locator'); ?></a><?php if ($count===0) : ?><a class="bml-delete-link" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bml_delete_term&taxonomy=' . $taxonomy . '&term_id=' . $term->term_id), 'bml_handle_' . $term->term_id)); ?>"><?php esc_html_e('Delete', 'business-map-locator'); ?></a><?php endif; ?></div></td></tr>
                <?php endforeach; endif; ?></tbody></table></div>
                <?php if ($totalPages > 1) : ?><div class="bml-pagination"><span><?php echo esc_html(sprintf(__('%d cities found', 'business-map-locator'), $totalFiltered)); ?></span><?php echo wp_kses_post(paginate_links(['base'=>add_query_arg('paged','%#%'),'format'=>'','current'=>$paged,'total'=>$totalPages,'type'=>'list'])); ?></div><?php endif; ?>
            </article>

            <aside id="bml-city-editor" class="bml-city-editor-column">
                <form class="bml-city-editor" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-bml-city-editor>
                    <input type="hidden" name="action" value="bml_save_term"><input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>"><input type="hidden" name="term_id" value="<?php echo esc_attr((string)($editing ? $editTerm->term_id : 0)); ?>"><?php wp_nonce_field('bml_handle'); ?>
                    <article class="bml-panel"><div class="bml-panel__head"><div><span class="bml-eyebrow"><?php echo $editing ? esc_html__('Edit city', 'business-map-locator') : esc_html__('New city', 'business-map-locator'); ?></span><h2><?php esc_html_e('City details', 'business-map-locator'); ?></h2></div></div><div class="bml-city-editor__body">
                        <label class="bml-field"><span><?php esc_html_e('Name', 'business-map-locator'); ?> *</span><input id="bml-city-name" name="name" required value="<?php echo esc_attr($editing ? $editTerm->name : ''); ?>" placeholder="<?php esc_attr_e('e.g. Helsinki', 'business-map-locator'); ?>"><small><?php esc_html_e('Displayed in frontend filters and location cards.', 'business-map-locator'); ?></small></label>
                        <label class="bml-field"><span><?php esc_html_e('Slug', 'business-map-locator'); ?></span><input id="bml-city-slug" name="slug" value="<?php echo esc_attr($editing ? $editTerm->slug : ''); ?>" placeholder="<?php esc_attr_e('Generated automatically', 'business-map-locator'); ?>"><small><?php esc_html_e('Used in URLs, imports and API filters.', 'business-map-locator'); ?></small></label>
                        <label class="bml-field"><span><?php esc_html_e('Description', 'business-map-locator'); ?></span><textarea id="bml-city-description" name="description" rows="4" placeholder="<?php esc_attr_e('Optional internal note', 'business-map-locator'); ?>"><?php echo esc_textarea($editing ? $editTerm->description : ''); ?></textarea></label>
                        <label class="bml-field"><span><?php esc_html_e('Manual order', 'business-map-locator'); ?></span><input name="sort_order" type="number" value="<?php echo esc_attr((string)$sortOrder); ?>"><small><?php esc_html_e('Lower values appear first when manual sorting is used.', 'business-map-locator'); ?></small></label>
                    </div></article>
                    <article class="bml-panel"><div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Live preview', 'business-map-locator'); ?></span><h2><?php esc_html_e('Frontend filter', 'business-map-locator'); ?></h2></div></div><div class="bml-city-preview"><span class="dashicons dashicons-location-alt"></span><span><strong data-bml-city-preview-name><?php echo esc_html($editing ? $editTerm->name : __('City name', 'business-map-locator')); ?></strong><small data-bml-city-preview-slug><?php echo esc_html($editing ? $editTerm->slug : __('city-slug', 'business-map-locator')); ?></small></span><span class="bml-city-preview__count"><?php echo esc_html(number_format_i18n($editing ? (int)$editTerm->count : 0)); ?></span></div></article>
                    <?php if ($editing && (int)$editTerm->count > 0) : ?><article class="bml-panel bml-city-usage"><div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Usage', 'business-map-locator'); ?></span><h2><?php echo esc_html(sprintf(_n('%d linked location', '%d linked locations', (int)$editTerm->count, 'business-map-locator'), (int)$editTerm->count)); ?></h2></div></div><a class="bml-btn bml-btn--secondary" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&city=' . rawurlencode($editTerm->slug))); ?>"><?php esc_html_e('View locations', 'business-map-locator'); ?></a></article><?php endif; ?>
                    <div class="bml-city-savebar"><div><strong><?php echo $editing ? esc_html__('Editing city', 'business-map-locator') : esc_html__('Create city', 'business-map-locator'); ?></strong><small data-bml-city-save-state><?php esc_html_e('Changes are not saved yet.', 'business-map-locator'); ?></small></div><div class="bml-actions"><?php if ($editing) : ?><a class="bml-btn bml-btn--text" href="<?php echo esc_url(admin_url('admin.php?page=bml-cities')); ?>"><?php esc_html_e('Cancel', 'business-map-locator'); ?></a><?php endif; ?><button class="bml-btn bml-btn--primary" type="submit"><?php echo $editing ? esc_html__('Update city', 'business-map-locator') : esc_html__('Add city', 'business-map-locator'); ?></button></div></div>
                </form>
            </aside>
        </div>
        <?php
        $this->shell->end();
    }

    private function cityMetric(string $icon, string $label, int $value, string $note, string $tone): void
    {
        ?><div class="bml-city-metric bml-city-metric--<?php echo esc_attr($tone); ?>"><span class="dashicons <?php echo esc_attr($icon); ?>"></span><div><small><?php echo esc_html($label); ?></small><strong><?php echo esc_html(number_format_i18n($value)); ?></strong><em><?php echo esc_html($note); ?></em></div></div><?php
    }

    private function cityFilter(string $value, string $label, int $count, string $active): void
    {
        $url = add_query_arg(['page' => 'bml-cities', 'filter' => $value === 'all' ? null : $value], admin_url('admin.php'));
        ?><a class="<?php echo $active === $value ? 'is-active' : ''; ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?><span><?php echo esc_html(number_format_i18n($count)); ?></span></a><?php
    }

    private function renderClassicTaxonomy(string $taxonomy, string $title, string $description): void
    {
        $editId = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editTerm = $editId ? get_term($editId, $taxonomy) : null;
        $editing = $editTerm && !is_wp_error($editTerm);
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        $terms = is_wp_error($terms) ? [] : $terms;
        $this->shell->start($title, $description);
        ?>
        <div class="bml-grid bml-grid--terms">
            <article class="bml-panel">
                <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php echo $editing ? esc_html__('Edit item', 'business-map-locator') : esc_html__('New item', 'business-map-locator'); ?></span><h2><?php esc_html_e('City details', 'business-map-locator'); ?></h2></div></div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bml_save_term"><input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>"><input type="hidden" name="term_id" value="<?php echo esc_attr((string) ($editing ? $editTerm->term_id : 0)); ?>"><?php wp_nonce_field('bml_handle'); ?>
                    <div class="bml-form-grid bml-form-grid--single"><label class="bml-field"><span><?php esc_html_e('Name', 'business-map-locator'); ?> *</span><input name="name" required value="<?php echo esc_attr($editing ? $editTerm->name : ''); ?>"></label><label class="bml-field"><span><?php esc_html_e('Slug', 'business-map-locator'); ?></span><input name="slug" value="<?php echo esc_attr($editing ? $editTerm->slug : ''); ?>"></label><label class="bml-field"><span><?php esc_html_e('Order', 'business-map-locator'); ?></span><input name="sort_order" type="number" value="<?php echo esc_attr((string) ($editing ? (int) get_term_meta($editTerm->term_id, 'bml_sort_order', true) : 0)); ?>"></label></div>
                    <div class="bml-actions"><button class="bml-btn bml-btn--primary" type="submit"><?php echo $editing ? esc_html__('Update', 'business-map-locator') : esc_html__('Add', 'business-map-locator'); ?></button><?php if ($editing) : ?><a class="bml-btn bml-btn--text" href="<?php echo esc_url(admin_url('admin.php?page=bml-cities')); ?>"><?php esc_html_e('Cancel', 'business-map-locator'); ?></a><?php endif; ?></div>
                </form>
            </article>
            <article class="bml-panel bml-panel--flush"><div class="bml-panel__head bml-panel__head--pad"><div><span class="bml-eyebrow"><?php esc_html_e('Directory', 'business-map-locator'); ?></span><h2><?php echo esc_html(sprintf(_n('%d city', '%d cities', count($terms), 'business-map-locator'), count($terms))); ?></h2></div></div><div class="bml-table-wrap"><table class="bml-table"><thead><tr><th><?php esc_html_e('Name', 'business-map-locator'); ?></th><th><?php esc_html_e('Slug', 'business-map-locator'); ?></th><th><?php esc_html_e('Locations', 'business-map-locator'); ?></th><th></th></tr></thead><tbody><?php foreach ($terms as $term) : ?><tr><td><strong><?php echo esc_html($term->name); ?></strong></td><td><code><?php echo esc_html($term->slug); ?></code></td><td><a class="bml-link" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&city=' . rawurlencode($term->slug))); ?>"><?php echo esc_html(number_format_i18n((int) $term->count)); ?></a></td><td><div class="bml-row-actions"><a href="<?php echo esc_url(admin_url('admin.php?page=bml-cities&edit=' . $term->term_id)); ?>"><?php esc_html_e('Edit', 'business-map-locator'); ?></a><?php if ((int) $term->count === 0) : ?><a class="bml-delete-link" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bml_delete_term&taxonomy=' . $taxonomy . '&term_id=' . $term->term_id), 'bml_handle_' . $term->term_id)); ?>"><?php esc_html_e('Delete', 'business-map-locator'); ?></a><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></article>
        </div>
        <?php
        $this->shell->end();
    }
    private static function normalizeSearchText(string $value): string
    {
        $value = rawurldecode($value);
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?: '');
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

}
