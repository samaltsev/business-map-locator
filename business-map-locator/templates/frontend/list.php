<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bml-directory__summary">
    <div class="bml-directory__count">
        <div class="bml-result-count" aria-live="polite" data-bml-count-template="<?php echo esc_attr__('Showing %1$d of %2$d results', 'business-map-locator'); ?>"></div>
    </div>
    <label class="bml-sort-control">
        <span class="screen-reader-text"><?php esc_html_e('Sort locations', 'business-map-locator'); ?></span>
        <span class="bml-sort-control__label" aria-hidden="true"><?php esc_html_e('Sort:', 'business-map-locator'); ?></span>
        <select class="bml-sort-filter">
            <option value="default"><?php esc_html_e('Default', 'business-map-locator'); ?></option>
            <option value="title-asc"><?php esc_html_e('Title (A–Z)', 'business-map-locator'); ?></option>
            <option value="title-desc"><?php esc_html_e('Title (Z–A)', 'business-map-locator'); ?></option>
        </select>
    </label>
</div>
<div id="<?php echo esc_attr($id . '-results'); ?>" class="bml-results" aria-live="polite">
    <div class="bml-loading"><?php esc_html_e('Loading locations...', 'business-map-locator'); ?></div>
</div>
<div class="bml-load-more-wrap" aria-live="polite">
    <button type="button" class="bml-load-more" aria-controls="<?php echo esc_attr($id . '-results'); ?>" hidden><?php esc_html_e('Load more locations', 'business-map-locator'); ?></button>
    <span class="screen-reader-text bml-load-more-status"></span>
</div>
