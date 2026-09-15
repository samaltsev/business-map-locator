<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bml-toolbar"<?php echo (!$search && !$filters && !$geolocation) ? ' hidden' : ''; ?>>
    <label class="bml-search"<?php echo !$search ? ' hidden' : ''; ?>>
        <span class="screen-reader-text"><?php esc_html_e('Search locations', 'business-map-locator'); ?></span>
        <input type="search" class="bml-search-input" placeholder="<?php esc_attr_e('Search by title or address', 'business-map-locator'); ?>">
    </label>

    <div class="bml-toolbar__filters"<?php echo !$filters ? ' hidden' : ''; ?>>
        <?php if ($category_mode === 'visible') : ?>
            <label class="bml-filter-control">
                <span class="screen-reader-text"><?php esc_html_e('Filter by category', 'business-map-locator'); ?></span>
                <select class="bml-category-filter">
                    <option value=""><?php esc_html_e('All categories', 'business-map-locator'); ?></option>
                </select>
            </label>
        <?php endif; ?>

        <?php if ($area_mode === 'visible') : ?>
            <label class="bml-filter-control">
                <span class="screen-reader-text"><?php esc_html_e('Filter by area', 'business-map-locator'); ?></span>
                <select class="bml-area-filter bml-city-filter" data-bml-city-alias="area">
                    <option value=""><?php esc_html_e('All areas', 'business-map-locator'); ?></option>
                </select>
            </label>
        <?php endif; ?>
    </div>

    <div class="bml-toolbar__actions">
        <button type="button" class="bml-near-me"<?php echo !$geolocation ? ' hidden' : ''; ?>>
            <span aria-hidden="true">◎</span>
            <span><?php esc_html_e('Near me', 'business-map-locator'); ?></span>
        </button>
        <button type="button" class="bml-reset-filters">
            <span aria-hidden="true">↻</span>
            <span><?php esc_html_e('Reset', 'business-map-locator'); ?></span>
        </button>
    </div>
</div>
