<?php if (!defined('ABSPATH')) { exit; } ?>
<template class="bml-popup-template">
    <article class="bml-popup-card">
        <div class="bml-popup-card__image" data-bml-popup="image" hidden></div>
        <div class="bml-popup-card__body">
            <span class="bml-popup-card__status" data-bml-popup="status"><?php esc_html_e('Open now', 'business-map-locator'); ?></span>
            <strong data-bml-popup="title"></strong>
            <span class="bml-popup-card__address" data-bml-popup="address"></span>
            <div class="bml-popup-card__contacts" data-bml-popup="contacts" aria-label="<?php esc_attr_e('Contact channels', 'business-map-locator'); ?>">
                <a class="bml-preview-contact-icon bml-preview-contact-icon--navigation" data-bml-popup="navigation" target="_blank" rel="noopener" aria-label="<?php esc_attr_e('Navigation', 'business-map-locator'); ?>" title="<?php esc_attr_e('Navigation', 'business-map-locator'); ?>"><span><?php esc_html_e('Navigation', 'business-map-locator'); ?></span></a>
            </div>
        </div>
    </article>
</template>
