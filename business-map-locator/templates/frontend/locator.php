<?php if (!defined('ABSPATH')) { exit; } ?>
<div
    id="<?php echo esc_attr($id); ?>"
    class="bml-locator bml-layout-<?php echo esc_attr($layout); ?><?php echo !empty($preview_mode) ? ' bml-locator--preview' : ''; ?>"
    tabindex="-1"
    data-category="<?php echo esc_attr($category); ?>"
    data-city="<?php echo esc_attr($city); ?>"
    data-category-mode="<?php echo esc_attr($category_mode); ?>"
    data-city-mode="<?php echo esc_attr($city_mode); ?>"
    data-preview="<?php echo !empty($preview_mode) ? '1' : '0'; ?>"
    data-settings="<?php echo esc_attr(wp_json_encode($settings)); ?>"
    style="--bml-map-height:<?php echo esc_attr((string) $height); ?>px;--bml-list-width:<?php echo esc_attr((string) $list_width); ?>%"
>
    <?php if ($layout !== 'split') : ?>
        <header class="bml-locator__header">
            <?php echo BML_Locator_Renderer::template('toolbar.php', compact('search', 'filters', 'geolocation', 'category_mode', 'city_mode')); ?>
        </header>
    <?php endif; ?>

    <div class="bml-mobile-view-switch" role="group" aria-label="<?php esc_attr_e('Choose locator view', 'business-map-locator'); ?>">
        <button type="button" class="bml-mobile-view-switch__button is-active" data-bml-mobile-view="list" aria-pressed="true"><?php esc_html_e('List', 'business-map-locator'); ?></button>
        <button type="button" class="bml-mobile-view-switch__button" data-bml-mobile-view="map" aria-pressed="false"><?php esc_html_e('Map', 'business-map-locator'); ?></button>
    </div>

    <div class="bml-locator-body" data-bml-mobile-active-view="list">
        <section class="bml-directory" aria-label="<?php esc_attr_e('Business locations', 'business-map-locator'); ?>">
            <?php if ($layout === 'split') : ?>
                <div class="bml-directory__toolbar">
                    <?php echo BML_Locator_Renderer::template('toolbar.php', compact('search', 'filters', 'geolocation', 'category_mode', 'city_mode')); ?>
                </div>
            <?php endif; ?>
            <?php echo BML_Locator_Renderer::template('list.php', compact('id')); ?>
        </section>

        <section class="bml-map-region" aria-label="<?php esc_attr_e('Locations map', 'business-map-locator'); ?>">
            <?php echo BML_Locator_Renderer::template('map.php'); ?>
        </section>

        <div class="bml-detail-backdrop" data-bml-detail-action="close" hidden aria-hidden="true"></div>
        <aside class="bml-detail-panel" role="dialog" aria-modal="true" hidden aria-hidden="true" aria-labelledby="<?php echo esc_attr($id); ?>-detail-title">
            <div class="bml-detail-panel__header">
                <h2 id="<?php echo esc_attr($id); ?>-detail-title" tabindex="-1"></h2>
                <button type="button" class="bml-detail-panel__close" data-bml-detail-action="close" aria-label="<?php esc_attr_e('Close', 'business-map-locator'); ?>">&times;</button>
            </div>
            <div class="bml-detail-panel__body" aria-busy="false" aria-live="polite"></div>
        </aside>
    </div>

    <?php echo BML_Locator_Renderer::template('popup.php'); ?>
</div>
