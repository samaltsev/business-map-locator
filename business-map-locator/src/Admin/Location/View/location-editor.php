<?php
if (!defined('ABSPATH')) { exit; }
extract($data, EXTR_SKIP);
$is_edit = (bool) $post;
$back_url = admin_url('admin.php?page=bml-locations');
$display_title = $title ?: __('Untitled location', 'business-map-locator');
$operational_status = $meta['operational_status'] ?: 'open';
$primary_label = $is_edit ? __('Update location', 'business-map-locator') : __('Create location', 'business-map-locator');
$created = $post ? get_the_date('', $post) : __('Not created yet', 'business-map-locator');
$updated = $post ? get_the_modified_date('', $post) . ' ' . get_the_modified_time('', $post) : __('Not saved yet', 'business-map-locator');
$duplicate_url = $is_edit ? wp_nonce_url(admin_url('admin-post.php?action=bml_duplicate_location&id=' . $id), 'bml_duplicate_location_' . $id) : '';
$delete_url = $is_edit ? wp_nonce_url(admin_url('admin-post.php?action=bml_delete_location&id=' . $id), 'bml_delete_location_' . $id) : '';
$card_settings = \BML_Plugin::settings();
?>
<div class="wrap bml-editor-page" data-editor-version="beta17">
    <form id="bml-location-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate autocomplete="off" data-1p-ignore data-lpignore="true" data-form-type="other" data-location-id="<?php echo esc_attr((string) $id); ?>">
        <input type="hidden" name="action" value="bml_save_location_custom">
        <input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>">
        <?php wp_nonce_field('bml_save_location_custom'); ?>

        <header class="bml-editor-header">
            <div class="bml-editor-heading">
                <a class="bml-editor-back" href="<?php echo esc_url($back_url); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span><?php esc_html_e('Locations', 'business-map-locator'); ?></a>
                <div class="bml-editor-titleline">
                    <div>
                        <span class="bml-eyebrow">Business Map Locator</span>
                        <h1><?php echo esc_html($is_edit ? __('Edit location', 'business-map-locator') : __('Add location', 'business-map-locator')); ?></h1>
                        <p id="bml-workspace-subtitle"><?php echo esc_html($display_title); ?></p>
                    </div>
                    <span id="bml-save-state" class="bml-save-state is-saved" aria-live="polite"><i></i><span><?php esc_html_e('Saved', 'business-map-locator'); ?></span></span>
                </div>
            </div>
            <div class="bml-editor-actions">
                <button class="bml-btn bml-btn--secondary" type="button" data-preview-focus><span class="dashicons dashicons-visibility"></span><?php esc_html_e('Preview', 'business-map-locator'); ?></button>
                <button class="bml-btn bml-btn--secondary" type="button" data-bml-save="draft"><?php esc_html_e('Save draft', 'business-map-locator'); ?></button>
                <button class="bml-btn bml-btn--primary" type="button" data-bml-save="publish"><?php echo esc_html($primary_label); ?></button>
            </div>
        </header>

        <div id="bml-editor-notices" class="bml-editor-notices" role="status" aria-live="polite" aria-atomic="true"></div>

        <div class="bml-editor-layout">
            <main class="bml-editor-main">
                <section class="bml-editor-section is-open" data-section-card="basic">
                    <button class="bml-section-toggle" type="button" aria-expanded="true" aria-controls="bml-section-basic-body">
                        <span class="bml-section-icon dashicons dashicons-admin-home"></span>
                        <span><strong><?php esc_html_e('Basic information', 'business-map-locator'); ?></strong><small><?php esc_html_e('Name and operational status.', 'business-map-locator'); ?></small></span>
                        <span class="bml-section-state" data-section-state="basic"></span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div id="bml-section-basic-body" class="bml-section-body">
                        <label class="bml-field bml-field--wide"><span><?php esc_html_e('Location name', 'business-map-locator'); ?> *</span><input id="bml_title" name="title" value="<?php echo esc_attr($title); ?>" required placeholder="<?php esc_attr_e('Main office', 'business-map-locator'); ?>"><small><?php esc_html_e('Shown in the locator, search results and location card.', 'business-map-locator'); ?></small><em class="bml-field-error"></em></label>
                        <label class="bml-field"><span><?php esc_html_e('Operational status', 'business-map-locator'); ?></span><select name="operational_status"><option value="active" <?php selected($operational_status, 'active'); ?>><?php esc_html_e('Active', 'business-map-locator'); ?></option><option value="temporarily_closed" <?php selected($operational_status, 'temporarily_closed'); ?>><?php esc_html_e('Temporarily closed', 'business-map-locator'); ?></option><option value="hidden" <?php selected($operational_status, 'hidden'); ?>><?php esc_html_e('Hidden from locator', 'business-map-locator'); ?></option></select></label>
                    </div>
                </section>

                <section class="bml-editor-section is-open" data-section-card="address">
                    <button class="bml-section-toggle" type="button" aria-expanded="true" aria-controls="bml-section-address-body">
                        <span class="bml-section-icon dashicons dashicons-location-alt"></span>
                        <span><strong><?php esc_html_e('Address & map', 'business-map-locator'); ?></strong><small><?php esc_html_e('Find the address and fine-tune the marker.', 'business-map-locator'); ?></small></span>
                        <span class="bml-section-state" data-section-state="address"></span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div id="bml-section-address-body" class="bml-section-body">
                        <div class="bml-address-search-panel">
                            <label for="bml-address-search"><strong><?php esc_html_e('Smart address search', 'business-map-locator'); ?></strong><span class="bml-help" tabindex="0" data-tooltip="<?php esc_attr_e('Press Enter to search. The form will not be submitted.', 'business-map-locator'); ?>">i</span></label>
                            <div class="bml-map-searchbar"><input id="bml-address-search" type="search" autocomplete="off" data-bml-no-address-save="1" data-1p-ignore data-lpignore="true" data-form-type="other" placeholder="<?php esc_attr_e('Street and house number', 'business-map-locator'); ?>"><button id="bml-find-address" class="bml-btn bml-btn--primary" type="button"><span class="dashicons dashicons-search"></span><?php esc_html_e('Search', 'business-map-locator'); ?></button></div>
                            <small class="bml-search-hint"><?php esc_html_e('Select a city to improve search results.', 'business-map-locator'); ?></small>
                            <div id="bml-geocode-results" class="bml-geocode-results"></div>
                            <div id="bml-auto-address-note" class="bml-geocode-state" data-state="idle"><span class="dashicons dashicons-info-outline"></span><div><strong><?php esc_html_e('Address not verified', 'business-map-locator'); ?></strong><p><?php esc_html_e('Search for an address or place the marker manually.', 'business-map-locator'); ?></p></div></div>
                        </div>
                        <div class="bml-address-layout">
                            <div class="bml-address-fields">
                                <label class="bml-field"><span><?php esc_html_e('Street address', 'business-map-locator'); ?> *</span><input id="bml_address" name="bml_location_address" data-bml-field="address" autocomplete="off" data-bml-no-address-save="1" data-1p-ignore data-lpignore="true" data-form-type="other" value="<?php echo esc_attr((string) $meta['address']); ?>" required><em class="bml-field-error"></em></label>
                                <div class="bml-form-grid"><label class="bml-field"><span><?php esc_html_e('Region', 'business-map-locator'); ?></span><input id="bml_region" name="bml_location_region" data-bml-field="region" autocomplete="off" data-bml-no-address-save="1" data-1p-ignore data-lpignore="true" data-form-type="other" value="<?php echo esc_attr((string) $meta['region']); ?>"></label><label class="bml-field"><span><?php esc_html_e('Country', 'business-map-locator'); ?></span><input id="bml_country" name="bml_location_country" data-bml-field="country" autocomplete="off" data-bml-no-address-save="1" data-1p-ignore data-lpignore="true" data-form-type="other" value="<?php echo esc_attr((string) $meta['country']); ?>"></label><label class="bml-field"><span><?php esc_html_e('Postal code', 'business-map-locator'); ?></span><input id="bml_postcode" name="bml_location_postcode" data-bml-field="postcode" autocomplete="off" data-bml-no-address-save="1" data-1p-ignore data-lpignore="true" data-form-type="other" value="<?php echo esc_attr((string) $meta['postcode']); ?>"></label></div>
                                <details class="bml-coordinate-details"><summary><?php esc_html_e('Coordinates', 'business-map-locator'); ?></summary><div class="bml-form-grid"><label class="bml-field"><span><?php esc_html_e('Latitude', 'business-map-locator'); ?></span><input id="bml_lat" name="lat" type="number" step="any" value="<?php echo esc_attr((string) $meta['lat']); ?>"></label><label class="bml-field"><span><?php esc_html_e('Longitude', 'business-map-locator'); ?></span><input id="bml_lng" name="lng" type="number" step="any" value="<?php echo esc_attr((string) $meta['lng']); ?>"></label></div><button type="button" class="button" id="bml-copy-coordinates"><?php esc_html_e('Copy coordinates', 'business-map-locator'); ?></button></details>
                            </div>
                            <div class="bml-map-column"><div class="bml-map-skeleton" aria-hidden="true"></div><div id="bml-admin-map" class="bml-admin-map bml-admin-map--workspace"></div><p class="bml-map-help"><span class="dashicons dashicons-move"></span><?php esc_html_e('Click the map or drag the marker to adjust the position.', 'business-map-locator'); ?></p></div>
                        </div>
                    </div>
                </section>

                <section class="bml-editor-section is-open" data-section-card="classification">
                    <button class="bml-section-toggle" type="button" aria-expanded="true" aria-controls="bml-section-classification-body">
                        <span class="bml-section-icon dashicons dashicons-category"></span>
                        <span><strong><?php esc_html_e('Classification', 'business-map-locator'); ?></strong><small><?php esc_html_e('Category and city used by filters.', 'business-map-locator'); ?></small></span>
                        <span class="bml-section-state" data-section-state="classification"></span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div id="bml-section-classification-body" class="bml-section-body"><div class="bml-form-grid">
                        <?php foreach ([['bml_category','category_id',__('Category','business-map-locator'),$categories,$category_ids,__('Create category','business-map-locator')],['bml_city','city_id',__('City','business-map-locator'),$cities,$city_ids,__('Create city','business-map-locator')]] as $term_data) : [$taxonomy,$field_name,$field_label,$terms,$selected_ids,$create_label] = $term_data; ?>
                            <div class="bml-term-field" data-term-field data-taxonomy="<?php echo esc_attr($taxonomy); ?>"><label class="bml-field"><span><?php echo esc_html($field_label); ?> *</span><div class="bml-select-action"><select name="<?php echo esc_attr($field_name); ?>" required><option value=""><?php printf(esc_html__('Select %s', 'business-map-locator'), esc_html(strtolower($field_label))); ?></option><?php foreach ($terms as $term) : ?><option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected((string) ($selected_ids[0] ?? ''), (string) $term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select><?php if ($can_manage_terms) : ?><button type="button" class="bml-icon-button" data-add-term aria-label="<?php echo esc_attr($create_label); ?>"><span class="dashicons dashicons-plus-alt2"></span></button><?php endif; ?></div><em class="bml-field-error"></em></label><?php if ($can_manage_terms) : ?><div class="bml-inline-term" hidden><strong><?php echo esc_html($create_label); ?></strong><label><span><?php esc_html_e('Name', 'business-map-locator'); ?> *</span><input type="text" data-term-name></label><label><span><?php esc_html_e('Slug', 'business-map-locator'); ?></span><input type="text" data-term-slug></label><div><button type="button" class="button" data-cancel-term><?php esc_html_e('Cancel', 'business-map-locator'); ?></button><button type="button" class="button button-primary" data-create-term><?php echo esc_html($create_label); ?></button></div><p class="bml-inline-term-message" role="status"></p></div><?php endif; ?></div>
                        <?php endforeach; ?>
                    </div></div>
                </section>

                <section class="bml-editor-section" data-section-card="contact">
                    <button class="bml-section-toggle" type="button" aria-expanded="false" aria-controls="bml-section-contact-body">
                        <span class="bml-section-icon dashicons dashicons-phone"></span>
                        <span><strong><?php esc_html_e('Contact', 'business-map-locator'); ?></strong><small><?php esc_html_e('Phone and automatic navigation.', 'business-map-locator'); ?></small></span>
                        <span class="bml-section-state" data-section-state="contact"></span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div id="bml-section-contact-body" class="bml-section-body" hidden><div class="bml-form-grid"><label class="bml-field"><span><?php esc_html_e('Phone', 'business-map-locator'); ?></span><input name="bml_location_phone" data-bml-field="phone" autocomplete="off" data-bml-no-address-save="1" data-1p-ignore data-lpignore="true" data-form-type="other" value="<?php echo esc_attr((string) $meta['phone']); ?>" inputmode="tel" placeholder="+48 123 456 789"><small><?php esc_html_e('Optional. Use the direct branch number.', 'business-map-locator'); ?></small></label><div class="bml-field bml-navigation-info"><span><?php esc_html_e('Navigation', 'business-map-locator'); ?></span><div class="bml-navigation-note"><span class="bml-channel-icon"><?php echo \BusinessMapLocator\Admin\Location\View\ContactIcon::render('navigation'); ?></span><p><strong><?php esc_html_e('Automatic', 'business-map-locator'); ?></strong><br><?php esc_html_e('Uses the saved coordinates and the visitor’s device.', 'business-map-locator'); ?></p></div></div></div></div>
                </section>
                <section class="bml-editor-section is-open" data-section-card="location-contract">
                    <button class="bml-section-toggle" type="button" aria-expanded="true" aria-controls="bml-section-location-contract-body">
                        <span class="bml-section-icon dashicons dashicons-admin-links"></span>
                        <span><strong><?php esc_html_e('Contact and description', 'business-map-locator'); ?></strong><small><?php esc_html_e('Optional public location details.', 'business-map-locator'); ?></small></span>
                    </button>
                    <div id="bml-section-location-contract-body" class="bml-section-body"><div class="bml-form-grid">
                        <label class="bml-field"><span><?php esc_html_e('Email', 'business-map-locator'); ?></span><input name="bml_location_email" type="email" value="<?php echo esc_attr((string) $meta['email']); ?>"></label>
                        <label class="bml-field"><span><?php esc_html_e('Website', 'business-map-locator'); ?></span><input name="bml_location_website" type="url" value="<?php echo esc_attr((string) $meta['website']); ?>"></label>
                        <label class="bml-field bml-field--wide"><span><?php esc_html_e('Hours', 'business-map-locator'); ?></span><textarea name="bml_location_hours" rows="3"><?php echo esc_textarea((string) $meta['hours']); ?></textarea></label>
                        <label class="bml-field bml-field--wide"><span><?php esc_html_e('Excerpt', 'business-map-locator'); ?></span><textarea name="excerpt" rows="2"><?php echo esc_textarea((string) ($post?->post_excerpt ?? '')); ?></textarea></label>
                        <label class="bml-field bml-field--wide"><span><?php esc_html_e('Description', 'business-map-locator'); ?></span><textarea name="content" rows="5"><?php echo esc_textarea((string) ($post?->post_content ?? '')); ?></textarea></label>
                    </div></div>
                </section>
            </main>

            <aside class="bml-editor-sidebar">
                <div class="bml-editor-sidebar__sticky">
                    <section class="bml-sidebar-card bml-preview-card" id="bml-live-preview-card"><div class="bml-sidebar-title"><h2><?php esc_html_e('Live preview', 'business-map-locator'); ?></h2><span class="bml-badge"><?php esc_html_e('Card', 'business-map-locator'); ?></span></div><div class="bml-preview-skeleton" aria-hidden="true"></div><div class="bml-live-card bml-live-card--free"><div class="bml-live-card-body"><span id="bml-preview-status" class="bml-live-status"><?php esc_html_e('Open now', 'business-map-locator'); ?></span><h3 id="bml-preview-title"><?php echo esc_html($display_title); ?></h3><p id="bml-preview-category"></p><p id="bml-preview-address" <?php echo empty($card_settings['show_address']) ? 'hidden' : ''; ?>><?php echo esc_html($meta['address'] ?: __('Address will appear here', 'business-map-locator')); ?></p><div id="bml-preview-contact-icons" class="bml-preview-contact-icons"><span id="bml-preview-navigation" class="bml-preview-contact-icon bml-preview-contact-icon--navigation" <?php echo empty($card_settings['show_navigation']) ? 'data-navigation-disabled="1" hidden' : 'hidden'; ?>><?php echo \BusinessMapLocator\Admin\Location\View\ContactIcon::render('navigation'); ?><span><?php esc_html_e('Navigation', 'business-map-locator'); ?></span></span></div></div></div></section>

                    <section class="bml-sidebar-card bml-completion-card"><div class="bml-sidebar-title"><h2><?php esc_html_e('Ready checklist', 'business-map-locator'); ?></h2><strong id="bml-completion-percent"><?php echo esc_html((string) $completeness['percent']); ?>%</strong></div><div class="bml-progress"><span id="bml-completion-bar" style="width:<?php echo esc_attr((string) $completeness['percent']); ?>%"></span></div><ul id="bml-completion-list"><?php foreach ($completeness['checks'] as $key => $check) : ?><li data-check="<?php echo esc_attr($key); ?>" class="<?php echo $check[1] ? 'is-complete' : ''; ?>"><span class="dashicons <?php echo $check[1] ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>"></span><?php echo esc_html($check[0]); ?></li><?php endforeach; ?></ul></section>

                    <section class="bml-sidebar-card bml-publish-card"><div class="bml-sidebar-title"><h2><?php esc_html_e('Publication', 'business-map-locator'); ?></h2></div><?php if ($can_publish) : ?><label class="bml-field"><span><?php esc_html_e('Status', 'business-map-locator'); ?></span><select name="status"><option value="publish" <?php selected($status, 'publish'); ?>><?php esc_html_e('Published', 'business-map-locator'); ?></option><option value="draft" <?php selected($status, 'draft'); ?>><?php esc_html_e('Draft', 'business-map-locator'); ?></option></select></label><?php else : ?><input type="hidden" name="status" value="draft"><?php endif; ?><dl class="bml-editor-meta"><div><dt><?php esc_html_e('Created', 'business-map-locator'); ?></dt><dd><?php echo esc_html($created); ?></dd></div><div><dt><?php esc_html_e('Updated', 'business-map-locator'); ?></dt><dd><?php echo esc_html($updated); ?></dd></div><div><dt><?php esc_html_e('Visibility', 'business-map-locator'); ?></dt><dd><?php esc_html_e('Public', 'business-map-locator'); ?></dd></div></dl><button class="bml-btn bml-btn--primary bml-btn--block" type="button" data-bml-save="publish"><?php echo esc_html($primary_label); ?></button></section>

                    <section class="bml-sidebar-card"><div class="bml-sidebar-title"><h2><?php esc_html_e('Quick actions', 'business-map-locator'); ?></h2></div><div class="bml-quick-actions"><button type="button" data-open-map><span class="dashicons dashicons-location-alt"></span><?php esc_html_e('Open on map', 'business-map-locator'); ?></button><button type="button" data-preview-focus><span class="dashicons dashicons-visibility"></span><?php esc_html_e('Preview', 'business-map-locator'); ?></button><?php if ($is_edit) : ?><a href="<?php echo esc_url($duplicate_url); ?>"><span class="dashicons dashicons-admin-page"></span><?php esc_html_e('Duplicate', 'business-map-locator'); ?></a><a class="is-danger bml-delete-link" href="<?php echo esc_url($delete_url); ?>"><span class="dashicons dashicons-trash"></span><?php esc_html_e('Delete', 'business-map-locator'); ?></a><?php endif; ?></div></section>
                </div>
            </aside>
        </div>

        <div id="bml-sticky-savebar" class="bml-sticky-savebar" hidden><span><i></i><?php esc_html_e('Unsaved changes', 'business-map-locator'); ?></span><div><button type="button" class="bml-btn bml-btn--secondary" data-discard-changes><?php esc_html_e('Discard', 'business-map-locator'); ?></button><button type="button" class="bml-btn bml-btn--secondary" data-bml-save="draft"><?php esc_html_e('Save draft', 'business-map-locator'); ?></button><button type="button" class="bml-btn bml-btn--primary" data-bml-save="publish"><?php echo esc_html($primary_label); ?></button></div></div>
    </form>
</div>
