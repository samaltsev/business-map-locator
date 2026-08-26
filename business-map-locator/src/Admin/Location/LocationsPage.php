<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Location;
use BusinessMapLocator\Admin\Shared\AdminShell;
use BusinessMapLocator\Admin\Location\View\LocationTableRenderer;
use BusinessMapLocator\Admin\Taxonomy\View\TermSelectRenderer;
use BusinessMapLocator\Admin\Taxonomy\TaxonomyOptionsProvider;
if (!defined('ABSPATH')) { exit; }
final class LocationsPage
{
    public function __construct(private AdminShell $shell, private LocationTableRenderer $table, private LocationTableDataProvider $tableData, private TermSelectRenderer $termSelect, private TaxonomyOptionsProvider $taxonomyOptions) {}
    public function render(): void {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $category = isset($_GET['category']) ? sanitize_title(wp_unslash($_GET['category'])) : '';
        $city = isset($_GET['city']) ? sanitize_title(wp_unslash($_GET['city'])) : '';
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $quality = isset($_GET['quality']) ? sanitize_key(wp_unslash($_GET['quality'])) : '';
        $paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $counts = wp_count_posts('bml_location');
        $published = isset($counts->publish) ? (int) $counts->publish : 0;
        $drafts = isset($counts->draft) ? (int) $counts->draft : 0;
        $total = $published + $drafts;
        $qualitySummary = $this->qualitySummary();
        $args = ['post_type' => 'bml_location', 'post_status' => ['publish', 'draft'], 'posts_per_page' => 20, 'paged' => $paged, 's' => $search, 'orderby' => 'modified', 'order' => 'DESC'];
        if (in_array($status, ['publish', 'draft'], true)) $args['post_status'] = $status;
        $taxQuery = [];
        if ($category) $taxQuery[] = ['taxonomy' => 'bml_category', 'field' => 'slug', 'terms' => $category];
        if ($city) $taxQuery[] = ['taxonomy' => 'bml_city', 'field' => 'slug', 'terms' => $city];
        if ($taxQuery) $args['tax_query'] = $taxQuery;
        if (in_array($quality, ['missing-address', 'missing-coordinates', 'missing-phone'], true)) {
            $metaQuery = [];
            if ($quality === 'missing-address') $metaQuery[] = ['key' => 'bml_address', 'compare' => 'NOT EXISTS'];
            if ($quality === 'missing-phone') $metaQuery[] = ['key' => 'bml_phone', 'compare' => 'NOT EXISTS'];
            if ($quality === 'missing-coordinates') $metaQuery = ['relation' => 'OR', ['key' => 'bml_lat', 'compare' => 'NOT EXISTS'], ['key' => 'bml_lng', 'compare' => 'NOT EXISTS']];
            $args['meta_query'] = $metaQuery;
        }
        $query = new \WP_Query($args);
        $rows = $this->tableData->rows($query->posts);
        $this->shell->start(__('Locations', 'business-map-locator'), __('Search, review and manage your complete location network.', 'business-map-locator'), __('Add location', 'business-map-locator'), admin_url('admin.php?page=bml-location-edit'), __('Import CSV', 'business-map-locator'), admin_url('admin.php?page=bml-import'), 'dashicons-upload');
        ?>
        <section class="bml-location-metrics">
            <?php $this->metric('dashicons-location-alt', __('All locations', 'business-map-locator'), $total, __('Across the entire directory', 'business-map-locator'), 'blue'); ?>
            <?php $this->metric('dashicons-yes-alt', __('Published', 'business-map-locator'), $published, sprintf(__('%d%% of all locations', 'business-map-locator'), $total > 0 ? (int) round(($published / $total) * 100) : 0), 'green'); ?>
            <?php $this->metric('dashicons-edit', __('Drafts', 'business-map-locator'), $drafts, __('Not visible to visitors', 'business-map-locator'), 'violet'); ?>
            <?php $this->metric('dashicons-warning', __('Need review', 'business-map-locator'), $qualitySummary['incomplete'], sprintf(__('%d missing coordinates', 'business-map-locator'), $qualitySummary['missing_coordinates']), 'amber'); ?>
        </section>

        <section class="bml-location-attention <?php echo $qualitySummary['incomplete'] === 0 ? 'is-complete' : ''; ?>">
            <span class="bml-location-attention__icon dashicons <?php echo $qualitySummary['incomplete'] === 0 ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
            <div><span class="bml-eyebrow"><?php esc_html_e('Data review', 'business-map-locator'); ?></span><h2><?php echo esc_html($qualitySummary['incomplete'] === 0 ? __('Your location data is complete', 'business-map-locator') : __('Needs attention', 'business-map-locator')); ?></h2><p><?php echo esc_html($qualitySummary['incomplete'] === 0 ? __('No locations require review.', 'business-map-locator') : __('Resolve missing data before publishing or exporting your directory.', 'business-map-locator')); ?></p><?php if ($qualitySummary['incomplete'] > 0) : ?><div class="bml-location-attention__links"><a href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&quality=missing-address')); ?>"><?php echo esc_html(sprintf(__('%d without address', 'business-map-locator'), $qualitySummary['missing_address'])); ?></a><a href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&quality=missing-coordinates')); ?>"><?php echo esc_html(sprintf(__('%d without coordinates', 'business-map-locator'), $qualitySummary['missing_coordinates'])); ?></a><a href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&quality=missing-phone')); ?>"><?php echo esc_html(sprintf(__('%d without phone', 'business-map-locator'), $qualitySummary['missing_phone'])); ?></a></div><?php endif; ?></div>
            <div class="bml-location-attention__score"><strong><?php echo esc_html((string) $qualitySummary['percent']); ?>%</strong><small><?php esc_html_e('Data quality', 'business-map-locator'); ?></small></div>
        </section>

        <article class="bml-panel bml-panel--flush bml-locations-directory">
            <div class="bml-panel__head bml-panel__head--pad"><div><span class="bml-eyebrow"><?php esc_html_e('Directory', 'business-map-locator'); ?></span><h2><?php echo esc_html(sprintf(__('%d locations', 'business-map-locator'), $query->found_posts)); ?></h2></div><span class="bml-locations-shortcut"><kbd>/</kbd> <?php esc_html_e('Search', 'business-map-locator'); ?></span></div>
            <form class="bml-locations-toolbar" method="get"><input type="hidden" name="page" value="bml-locations"><label class="bml-search"><span class="dashicons dashicons-search"></span><input id="bml-locations-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search name, address, city or phone', 'business-map-locator'); ?>"></label><?php $this->termSelect->render($this->taxonomyOptions->options('bml_category'), 'category', $category, __('All categories', 'business-map-locator')); ?><?php $this->termSelect->render($this->taxonomyOptions->options('bml_city'), 'city', $city, __('All cities', 'business-map-locator')); ?><select name="status"><option value=""><?php esc_html_e('All statuses', 'business-map-locator'); ?></option><option value="publish" <?php selected($status, 'publish'); ?>><?php esc_html_e('Published', 'business-map-locator'); ?></option><option value="draft" <?php selected($status, 'draft'); ?>><?php esc_html_e('Draft', 'business-map-locator'); ?></option></select><button class="bml-btn bml-btn--secondary" type="submit"><?php esc_html_e('Apply filters', 'business-map-locator'); ?></button><?php if ($search || $category || $city || $status || $quality) : ?><a class="bml-btn bml-btn--text" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations')); ?>"><?php esc_html_e('Reset', 'business-map-locator'); ?></a><?php endif; ?></form>
            <div class="bml-location-filter-chips"><a class="<?php echo $status === '' && $quality === '' ? 'is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations')); ?>"><?php esc_html_e('All', 'business-map-locator'); ?><span><?php echo esc_html((string) $total); ?></span></a><a class="<?php echo $status === 'publish' ? 'is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&status=publish')); ?>"><?php esc_html_e('Published', 'business-map-locator'); ?><span><?php echo esc_html((string) $published); ?></span></a><a class="<?php echo $status === 'draft' ? 'is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&status=draft')); ?>"><?php esc_html_e('Drafts', 'business-map-locator'); ?><span><?php echo esc_html((string) $drafts); ?></span></a><a class="<?php echo $quality !== '' ? 'is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations&quality=missing-coordinates')); ?>"><?php esc_html_e('Need review', 'business-map-locator'); ?><span><?php echo esc_html((string) $qualitySummary['incomplete']); ?></span></a></div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bml_bulk_locations"><?php wp_nonce_field('bml_bulk_locations'); ?><div class="bml-locations-bulkbar"><select name="bulk_action"><option value=""><?php esc_html_e('Bulk actions', 'business-map-locator'); ?></option><option value="publish"><?php esc_html_e('Publish', 'business-map-locator'); ?></option><option value="draft"><?php esc_html_e('Move to draft', 'business-map-locator'); ?></option><option value="delete"><?php esc_html_e('Delete permanently', 'business-map-locator'); ?></option></select><button class="bml-btn bml-btn--secondary bml-btn--small" type="submit"><?php esc_html_e('Apply', 'business-map-locator'); ?></button><span data-bml-selected-count><?php esc_html_e('No locations selected', 'business-map-locator'); ?></span></div><?php $this->table->render($rows, true); ?><div class="bml-pagination"><span><?php echo esc_html(sprintf(__('%d locations found', 'business-map-locator'), $query->found_posts)); ?></span><?php echo wp_kses_post(paginate_links(['base' => add_query_arg(array_filter(['page' => 'bml-locations', 's' => $search, 'category' => $category, 'city' => $city, 'status' => $status, 'quality' => $quality, 'paged' => '%#%']), admin_url('admin.php')), 'format' => '', 'current' => $paged, 'total' => max(1, $query->max_num_pages), 'type' => 'list'])); ?></div></form>
        </article>
        <?php
        $this->shell->end();
    }

    private function qualitySummary(): array
    {
        $ids = get_posts(['post_type' => 'bml_location', 'post_status' => ['publish', 'draft'], 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true]);
        $missingAddress = $missingCoordinates = $missingPhone = $missingCategory = $missingCity = $incomplete = 0;
        foreach ($ids as $id) {
            $addressMissing = trim((string) get_post_meta((int) $id, 'bml_address', true)) === '';
            $phoneMissing = trim((string) get_post_meta((int) $id, 'bml_phone', true)) === '';
            $coordinatesMissing = get_post_meta((int) $id, 'bml_lat', true) === '' || get_post_meta((int) $id, 'bml_lng', true) === '';
            $categoryMissing = !has_term('', 'bml_category', (int) $id);
            $cityMissing = !has_term('', 'bml_city', (int) $id);
            $missingAddress += (int) $addressMissing; $missingPhone += (int) $phoneMissing; $missingCoordinates += (int) $coordinatesMissing; $missingCategory += (int) $categoryMissing; $missingCity += (int) $cityMissing;
            $incomplete += (int) ($addressMissing || $coordinatesMissing || $categoryMissing || $cityMissing);
        }
        $total = count($ids);
        return ['missing_address' => $missingAddress, 'missing_coordinates' => $missingCoordinates, 'missing_phone' => $missingPhone, 'missing_category' => $missingCategory, 'missing_city' => $missingCity, 'incomplete' => $incomplete, 'percent' => $total > 0 ? max(0, (int) round((($total - $incomplete) / $total) * 100)) : 100];
    }

    private function metric(string $icon, string $label, int $value, string $note, string $tone): void
    {
        echo '<div class="bml-location-metric bml-location-metric--' . esc_attr($tone) . '"><span class="dashicons ' . esc_attr($icon) . '"></span><span><small>' . esc_html($label) . '</small><strong>' . esc_html((string) $value) . '</strong><em>' . esc_html($note) . '</em></span></div>';
    }
}
