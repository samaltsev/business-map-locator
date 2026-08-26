<?php
declare(strict_types=1);
namespace BusinessMapLocator\Admin\Shared;
if (!defined('ABSPATH')) { exit; }
final class AdminShell
{
    private function navItems(): array {
        return [
            'business-map-locator' => ['dashicons-dashboard', __('Overview', 'business-map-locator')],
            'bml-locations' => ['dashicons-location', __('Locations', 'business-map-locator')],
            'bml-categories' => ['dashicons-category', __('Categories', 'business-map-locator')],
            'bml-cities' => ['dashicons-admin-site-alt3', __('Cities', 'business-map-locator')],
            'bml-import' => ['dashicons-migrate', __('Import / Export', 'business-map-locator')],
            'bml-settings' => ['dashicons-admin-generic', __('Settings', 'business-map-locator')],
        ];
    }
    public function start(string $title, string $description = '', string $primary_label = '', string $primary_url = '', string $secondary_label = '', string $secondary_url = '', string $secondary_icon = 'dashicons-upload'): void {
        $current = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'business-map-locator';
        if (in_array($current, ['bml-providers', 'bml-display', 'bml-embed'], true)) { $current = 'bml-settings'; }
        ?>
        <div class="wrap bml-admin-root">
            <div class="bml-admin-shell" data-bml-page="<?php echo esc_attr($current); ?>">
                <aside class="bml-admin-sidebar" id="bml-admin-sidebar">
                    <div class="bml-admin-brand">
                        <span class="bml-admin-brand__mark">BML</span>
                        <span><strong>Business Map Locator</strong><small><?php esc_html_e('Location platform', 'business-map-locator'); ?></small></span>
                    </div>
                    <nav class="bml-admin-nav">
                        <span class="bml-nav-label"><?php esc_html_e('Workspace', 'business-map-locator'); ?></span>
                        <?php foreach (array_slice($this->navItems(), 0, 2, true) as $slug => $item) : ?>
                            <a class="<?php echo $current === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>" title="<?php echo esc_attr($item[1]); ?>" aria-label="<?php echo esc_attr($item[1]); ?>">
                                <span class="dashicons <?php echo esc_attr($item[0]); ?>"></span><span><?php echo esc_html($item[1]); ?></span>
                            </a>
                        <?php endforeach; ?>
                        <span class="bml-nav-label"><?php esc_html_e('Manage', 'business-map-locator'); ?></span>
                        <?php foreach (array_slice($this->navItems(), 2, 3, true) as $slug => $item) : ?>
                            <a class="<?php echo $current === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>" title="<?php echo esc_attr($item[1]); ?>" aria-label="<?php echo esc_attr($item[1]); ?>">
                                <span class="dashicons <?php echo esc_attr($item[0]); ?>"></span><span><?php echo esc_html($item[1]); ?></span>
                            </a>
                        <?php endforeach; ?>
                        <span class="bml-nav-label"><?php esc_html_e('Configure', 'business-map-locator'); ?></span>
                        <?php $settings_item = $this->navItems()['bml-settings']; ?>
                        <a class="<?php echo $current === 'bml-settings' ? 'is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=bml-settings')); ?>" title="<?php echo esc_attr($settings_item[1]); ?>" aria-label="<?php echo esc_attr($settings_item[1]); ?>">
                            <span class="dashicons <?php echo esc_attr($settings_item[0]); ?>"></span><span><?php echo esc_html($settings_item[1]); ?></span>
                        </a>
                    </nav>
                    <div class="bml-sidebar-resources">
                        <span class="bml-nav-label"><?php esc_html_e('Resources', 'business-map-locator'); ?></span>
                        <a href="https://wordpress.org/support/" target="_blank" rel="noopener"><span class="dashicons dashicons-book-alt"></span><span><?php esc_html_e('Documentation', 'business-map-locator'); ?></span></a>
                        <a href="https://wordpress.org/support/" target="_blank" rel="noopener"><span class="dashicons dashicons-sos"></span><span><?php esc_html_e('Support', 'business-map-locator'); ?></span></a>
                    </div>
                    <div class="bml-admin-sidebar__footer"><span class="bml-admin-brand__mini">BML</span><span><strong><?php echo esc_html(\BML_VERSION); ?></strong><small><?php esc_html_e('System operational', 'business-map-locator'); ?></small></span></div>
                    <button type="button" class="bml-sidebar-collapse" data-bml-sidebar-toggle><span class="dashicons dashicons-arrow-left-alt2"></span><span><?php esc_html_e('Collapse', 'business-map-locator'); ?></span></button>
                </aside>
                <main class="bml-admin-main">
                    <header class="bml-admin-topbar">
                        <div><span class="bml-eyebrow">Business Map Locator</span><h1><?php echo esc_html($title); ?></h1><?php if ($description) : ?><p><?php echo esc_html($description); ?></p><?php endif; ?></div>
                        <div class="bml-topbar-actions">
                            <?php if ($secondary_label && $secondary_url) : ?><a class="bml-btn bml-btn--secondary" href="<?php echo esc_url($secondary_url); ?>"><span class="dashicons <?php echo esc_attr($secondary_icon); ?>"></span><?php echo esc_html($secondary_label); ?></a><?php else : ?><a class="bml-btn bml-btn--secondary" href="https://wordpress.org/support/" target="_blank" rel="noopener"><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Need help?', 'business-map-locator'); ?></a><?php endif; ?>
                            <?php if ($primary_label && $primary_url) : ?><a class="bml-btn bml-btn--primary" href="<?php echo esc_url($primary_url); ?>"><span class="dashicons dashicons-plus-alt2"></span><?php echo esc_html($primary_label); ?></a><?php endif; ?>
                        </div>
                    </header>
                    <div class="bml-admin-content">
        <?php
    }
    public function end(): void {
        echo '</div></main></div></div>';
    }
}
