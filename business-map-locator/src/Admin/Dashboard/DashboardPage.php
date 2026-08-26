<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Dashboard;

use BusinessMapLocator\Admin\Shared\AdminShell;

if (!defined('ABSPATH')) { exit; }

final class DashboardPage
{
    public function __construct(
        private AdminShell $shell,
        private object $table,
        private object $tableData
    ) {}

    public function render(): void
    {
        $counts = wp_count_posts('bml_location');
        $published = isset($counts->publish) ? (int) $counts->publish : 0;
        $drafts = isset($counts->draft) ? (int) $counts->draft : 0;
        $total = $published + $drafts;
        $categories = $this->termCount('bml_category');
        $cities = $this->termCount('bml_city');
        $unusedCategories = $this->unusedTermCount('bml_category');
        $unusedCities = $this->unusedTermCount('bml_city');
        $quality = $this->qualitySummary();
        $recent = get_posts([
            'post_type' => 'bml_location',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => 5,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        $settings = \BML_Plugin::settings();
        $provider = ($settings['provider'] ?? 'osm') === 'google' ? 'Google Maps' : 'OpenStreetMap';
        $providerReady = ($settings['provider'] ?? 'osm') === 'osm' || !empty($settings['google_key']);
        $publishRate = $total > 0 ? (int) round(($published / $total) * 100) : 0;

        $this->shell->start(
            __('Overview', 'business-map-locator'),
            __('Manage and publish your location network.', 'business-map-locator'),
            __('Add location', 'business-map-locator'),
            admin_url('admin.php?page=bml-location-edit'),
            __('Import CSV', 'business-map-locator'),
            admin_url('admin.php?page=bml-import'),
            'dashicons-upload'
        );
        ?>
        <section class="bml-network-summary">
            <div class="bml-network-summary__main">
                <span class="bml-eyebrow"><?php esc_html_e('Location network', 'business-map-locator'); ?></span>
                <div class="bml-network-summary__number"><?php echo esc_html((string) $total); ?></div>
                <p><?php esc_html_e('locations across your published directory', 'business-map-locator'); ?></p>
                <div class="bml-network-summary__actions">
                    <a class="bml-btn bml-btn--secondary" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations')); ?>"><?php esc_html_e('View locations', 'business-map-locator'); ?></a>
                    <a class="bml-link" href="<?php echo esc_url(admin_url('admin.php?page=bml-settings&tab=embed')); ?>"><?php esc_html_e('Embed locator', 'business-map-locator'); ?> →</a>
                </div>
            </div>
            <div class="bml-network-summary__stats">
                <div><span class="bml-state-dot bml-state-dot--success"></span><span><?php esc_html_e('Published', 'business-map-locator'); ?></span><strong><?php echo esc_html((string) $published); ?></strong></div>
                <div><span class="bml-state-dot"></span><span><?php esc_html_e('Draft', 'business-map-locator'); ?></span><strong><?php echo esc_html((string) $drafts); ?></strong></div>
                <div><span class="bml-state-dot bml-state-dot--warning"></span><span><?php esc_html_e('Need review', 'business-map-locator'); ?></span><strong><?php echo esc_html((string) $quality['incomplete']); ?></strong></div>
                <div class="bml-network-summary__rate"><span><?php esc_html_e('Published rate', 'business-map-locator'); ?></span><strong><?php echo esc_html((string) $publishRate); ?>%</strong></div>
            </div>
        </section>

        <div class="bml-dashboard-metrics">
            <?php $this->metricCard('dashicons-location', __('Locations', 'business-map-locator'), $total, sprintf(__('%d published', 'business-map-locator'), $published), admin_url('admin.php?page=bml-locations'), 'blue'); ?>
            <?php $this->metricCard('dashicons-admin-site-alt3', __('Cities', 'business-map-locator'), $cities, sprintf(__('%d unused', 'business-map-locator'), $unusedCities), admin_url('admin.php?page=bml-cities'), 'violet'); ?>
            <?php $this->metricCard('dashicons-category', __('Categories', 'business-map-locator'), $categories, sprintf(__('%d unused', 'business-map-locator'), $unusedCategories), admin_url('admin.php?page=bml-categories'), 'amber'); ?>
            <?php $this->metricCard('dashicons-yes-alt', __('Data quality', 'business-map-locator'), $quality['percent'] . '%', sprintf(__('%d need review', 'business-map-locator'), $quality['incomplete']), admin_url('admin.php?page=bml-locations'), $quality['incomplete'] > 0 ? 'warning' : 'green'); ?>
        </div>

        <div class="bml-dashboard-columns">
            <article class="bml-panel bml-panel--flush bml-recent-panel">
                <div class="bml-panel__head bml-panel__head--pad">
                    <div><span class="bml-eyebrow"><?php esc_html_e('Latest updates', 'business-map-locator'); ?></span><h2><?php esc_html_e('Recent locations', 'business-map-locator'); ?></h2></div>
                    <a class="bml-link" href="<?php echo esc_url(admin_url('admin.php?page=bml-locations')); ?>"><?php esc_html_e('View all', 'business-map-locator'); ?> →</a>
                </div>
                <?php $this->renderRecent($recent); ?>
            </article>

            <aside class="bml-dashboard-side">
                <article class="bml-panel bml-attention-card">
                    <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Data review', 'business-map-locator'); ?></span><h2><?php esc_html_e('Needs attention', 'business-map-locator'); ?></h2></div></div>
                    <?php $this->renderAttention($drafts, $quality, $unusedCategories, $unusedCities); ?>
                </article>

                <article class="bml-panel bml-quick-actions-compact">
                    <div class="bml-panel__head"><div><span class="bml-eyebrow"><?php esc_html_e('Shortcuts', 'business-map-locator'); ?></span><h2><?php esc_html_e('Quick actions', 'business-map-locator'); ?></h2></div></div>
                    <div class="bml-quick-action-grid">
                        <?php $this->quickAction('dashicons-plus-alt2', __('Add location', 'business-map-locator'), admin_url('admin.php?page=bml-location-edit')); ?>
                        <?php $this->quickAction('dashicons-upload', __('Import CSV', 'business-map-locator'), admin_url('admin.php?page=bml-import')); ?>
                        <?php $this->quickAction('dashicons-editor-code', __('Embed locator', 'business-map-locator'), admin_url('admin.php?page=bml-settings&tab=embed')); ?>
                        <?php $this->quickAction('dashicons-admin-generic', __('Map settings', 'business-map-locator'), admin_url('admin.php?page=bml-settings')); ?>
                    </div>
                </article>
            </aside>
        </div>

        <section class="bml-system-strip" aria-label="<?php esc_attr_e('System status', 'business-map-locator'); ?>">
            <div class="bml-system-strip__title"><span class="dashicons dashicons-shield-alt"></span><strong><?php esc_html_e('System status', 'business-map-locator'); ?></strong></div>
            <div class="bml-system-strip__items">
                <span><i class="bml-state-dot <?php echo $providerReady ? 'bml-state-dot--success' : 'bml-state-dot--warning'; ?>"></i><?php echo esc_html(sprintf(__('%s active', 'business-map-locator'), $provider)); ?></span>
                <span><i class="bml-state-dot bml-state-dot--success"></i><?php esc_html_e('REST API operational', 'business-map-locator'); ?></span>
                <span><i class="bml-state-dot bml-state-dot--success"></i><?php esc_html_e('Gutenberg block available', 'business-map-locator'); ?></span>
                <?php if (!empty($settings['google_key']) && $provider !== 'Google Maps') : ?><span><i class="bml-state-dot"></i><?php esc_html_e('Google Maps configured, inactive', 'business-map-locator'); ?></span><?php endif; ?>
            </div>
            <a class="bml-link" href="<?php echo esc_url(admin_url('admin.php?page=bml-settings')); ?>"><?php esc_html_e('Diagnostics', 'business-map-locator'); ?> →</a>
        </section>

        <?php if ($total === 0) : ?>
            <form class="bml-demo-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bml_install_demo">
                <?php wp_nonce_field('bml_install_demo'); ?>
                <button class="bml-btn bml-btn--text" type="submit"><?php esc_html_e('Install demo locations', 'business-map-locator'); ?></button>
            </form>
        <?php endif; ?>
        <?php
        $this->shell->end();
    }

    private function qualitySummary(): array
    {
        $ids = get_posts([
            'post_type' => 'bml_location',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        $missingPhone = 0;
        $missingCoordinates = 0;
        $missingAddress = 0;
        $missingCategory = 0;
        $missingCity = 0;
        $incomplete = 0;

        foreach ($ids as $id) {
            $addressMissing = trim((string) get_post_meta((int) $id, 'bml_address', true)) === '';
            $phoneMissing = trim((string) get_post_meta((int) $id, 'bml_phone', true)) === '';
            $coordinatesMissing = get_post_meta((int) $id, 'bml_lat', true) === '' || get_post_meta((int) $id, 'bml_lng', true) === '';
            $categoryMissing = !has_term('', 'bml_category', (int) $id);
            $cityMissing = !has_term('', 'bml_city', (int) $id);
            $missingAddress += (int) $addressMissing;
            $missingPhone += (int) $phoneMissing;
            $missingCoordinates += (int) $coordinatesMissing;
            $missingCategory += (int) $categoryMissing;
            $missingCity += (int) $cityMissing;
            $incomplete += (int) ($addressMissing || $coordinatesMissing || $categoryMissing || $cityMissing);
        }

        $total = count($ids);
        return [
            'missing_phone' => $missingPhone,
            'missing_coordinates' => $missingCoordinates,
            'missing_address' => $missingAddress,
            'missing_category' => $missingCategory,
            'missing_city' => $missingCity,
            'incomplete' => $incomplete,
            'percent' => $total > 0 ? max(0, (int) round((($total - $incomplete) / $total) * 100)) : 100,
        ];
    }

    private function renderRecent(array $posts): void
    {
        if (!$posts) {
            echo '<div class="bml-empty-state"><span class="dashicons dashicons-location-alt"></span><h3>' . esc_html__('No locations yet', 'business-map-locator') . '</h3><p>' . esc_html__('Create one manually or import an existing CSV file.', 'business-map-locator') . '</p></div>';
            return;
        }
        ?>
        <div class="bml-table-wrap"><table class="bml-table bml-recent-table">
            <thead><tr><th><?php esc_html_e('Location', 'business-map-locator'); ?></th><th><?php esc_html_e('Category / City', 'business-map-locator'); ?></th><th><?php esc_html_e('Updated', 'business-map-locator'); ?></th><th><?php esc_html_e('Status', 'business-map-locator'); ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($posts as $post) :
                $category = wp_get_post_terms($post->ID, 'bml_category', ['fields' => 'names']);
                $city = wp_get_post_terms($post->ID, 'bml_city', ['fields' => 'names']);
                $categoryName = !is_wp_error($category) && $category ? (string) $category[0] : __('Uncategorized', 'business-map-locator');
                $cityName = !is_wp_error($city) && $city ? (string) $city[0] : __('No city', 'business-map-locator');
                $editUrl = admin_url('admin.php?page=bml-location-edit&id=' . $post->ID);
            ?>
                <tr>
                    <td><div class="bml-location-cell"><span class="bml-location-placeholder dashicons dashicons-location-alt"></span><span><strong><?php echo esc_html($post->post_title); ?></strong><small><?php echo esc_html((string) get_post_meta($post->ID, 'bml_address', true)); ?></small></span></div></td>
                    <td><strong class="bml-table-primary"><?php echo esc_html($categoryName); ?></strong><small class="bml-table-secondary"><?php echo esc_html($cityName); ?></small></td>
                    <td><time datetime="<?php echo esc_attr(get_post_modified_time(DATE_W3C, true, $post)); ?>"><?php echo esc_html(human_time_diff((int) get_post_modified_time('U', true, $post), current_time('timestamp', true)) . ' ' . __('ago', 'business-map-locator')); ?></time></td>
                    <td><span class="bml-badge <?php echo $post->post_status === 'publish' ? 'bml-badge--success' : ''; ?>"><?php echo esc_html($post->post_status === 'publish' ? __('Published', 'business-map-locator') : __('Draft', 'business-map-locator')); ?></span></td>
                    <td><div class="bml-row-primary-actions"><a href="<?php echo esc_url($editUrl); ?>"><?php esc_html_e('Edit', 'business-map-locator'); ?></a><details><summary aria-label="<?php esc_attr_e('More actions', 'business-map-locator'); ?>">⋮</summary><div class="bml-row-menu"><a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bml_duplicate_location&id=' . $post->ID), 'bml_duplicate_location_' . $post->ID)); ?>"><?php esc_html_e('Duplicate', 'business-map-locator'); ?></a><a class="bml-delete-link" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bml_delete_location&id=' . $post->ID), 'bml_delete_location_' . $post->ID)); ?>"><?php esc_html_e('Delete', 'business-map-locator'); ?></a></div></details></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php
    }

    private function renderAttention(int $drafts, array $quality, int $unusedCategories, int $unusedCities): void
    {
        $items = [
            [$drafts, __('Draft locations', 'business-map-locator'), 'dashicons-edit', admin_url('admin.php?page=bml-locations')],
            [$quality['missing_coordinates'], __('Missing coordinates', 'business-map-locator'), 'dashicons-location-alt', admin_url('admin.php?page=bml-locations')],
            [$quality['missing_phone'], __('Missing phone', 'business-map-locator'), 'dashicons-phone', admin_url('admin.php?page=bml-locations')],
            [$quality['missing_category'], __('Missing category', 'business-map-locator'), 'dashicons-category', admin_url('admin.php?page=bml-locations')],
            [$unusedCategories, __('Unused categories', 'business-map-locator'), 'dashicons-tag', admin_url('admin.php?page=bml-categories')],
            [$unusedCities, __('Unused cities', 'business-map-locator'), 'dashicons-admin-site-alt3', admin_url('admin.php?page=bml-cities')],
        ];
        $visible = array_values(array_filter($items, static fn(array $item): bool => $item[0] > 0));
        if (!$visible) {
            echo '<div class="bml-attention-empty"><span class="dashicons dashicons-yes-alt"></span><strong>' . esc_html__('Your location data is complete', 'business-map-locator') . '</strong><p>' . esc_html__('No items require attention.', 'business-map-locator') . '</p></div>';
            return;
        }
        echo '<div class="bml-attention-list">';
        foreach (array_slice($visible, 0, 5) as $item) {
            echo '<a href="' . esc_url($item[3]) . '"><span class="dashicons ' . esc_attr($item[2]) . '"></span><span>' . esc_html($item[1]) . '</span><strong>' . esc_html((string) $item[0]) . '</strong><span class="dashicons dashicons-arrow-right-alt2"></span></a>';
        }
        echo '</div>';
    }

    private function metricCard(string $icon, string $label, int|string $value, string $note, string $url, string $tone): void
    {
        echo '<a class="bml-dashboard-metric bml-dashboard-metric--' . esc_attr($tone) . '" href="' . esc_url($url) . '"><span class="bml-dashboard-metric__icon dashicons ' . esc_attr($icon) . '"></span><span><small>' . esc_html($label) . '</small><strong>' . esc_html((string) $value) . '</strong><em>' . esc_html($note) . '</em></span></a>';
    }

    private function quickAction(string $icon, string $label, string $url): void
    {
        echo '<a href="' . esc_url($url) . '"><span class="dashicons ' . esc_attr($icon) . '"></span><strong>' . esc_html($label) . '</strong></a>';
    }

    private function termCount(string $taxonomy): int
    {
        $count = wp_count_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        return is_wp_error($count) ? 0 : (int) $count;
    }

    private function unusedTermCount(string $taxonomy): int
    {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'all']);
        if (is_wp_error($terms)) { return 0; }
        return count(array_filter($terms, static fn($term): bool => (int) $term->count === 0));
    }
}
