<?php
/**
 * Category meta fields handling
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta fields to category edit form
 */
function qr_digital_pricelist_category_meta_fields($term) {
    $enabled = get_term_meta($term->term_id, 'qr_enabled', true);
    $sort_order = get_term_meta($term->term_id, 'qr_sort_order', true);
    $enable_subsections = get_term_meta($term->term_id, 'qr_enable_subsections', true);

    $enabled = $enabled !== '' ? (bool) $enabled : true;
    $sort_order = $sort_order !== '' ? (int) $sort_order : 0;
    $enable_subsections = !empty($enable_subsections);
    
    wp_nonce_field('qr_digital_pricelist_category_save', 'qr_digital_pricelist_category_nonce');
    ?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="qr_enabled"><?php _e('Enabled', 'qr-digital-pricelist'); ?></label>
        </th>
        <td>
            <input type="checkbox" id="qr_enabled" name="qr_enabled" value="1" <?php checked($enabled); ?> />
            <p class="description"><?php _e('Enable this category to be displayed on the frontend.', 'qr-digital-pricelist'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="qr_sort_order"><?php _e('Sort Order', 'qr-digital-pricelist'); ?></label>
        </th>
        <td>
            <input type="number" id="qr_sort_order" name="qr_sort_order" value="<?php echo esc_attr($sort_order); ?>" step="1" min="0" />
            <p class="description"><?php _e('Lower numbers appear first. Used for custom ordering.', 'qr-digital-pricelist'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="qr_enable_subsections"><?php _e('Enable Subsections', 'qr-digital-pricelist'); ?></label>
        </th>
        <td>
            <input type="checkbox" id="qr_enable_subsections" name="qr_enable_subsections" value="1" <?php checked($enable_subsections); ?> />
            <p class="description"><?php _e('If checked, child categories will be shown as subsections before the menu items.', 'qr-digital-pricelist'); ?></p>
        </td>
    </tr>
    <?php
}

add_action('qr_menu_category_edit_form_fields', 'qr_digital_pricelist_category_meta_fields', 10, 1);

/**
 * Add meta fields to category add form
 */
function qr_digital_pricelist_category_add_meta_fields() {
    wp_nonce_field('qr_digital_pricelist_category_save', 'qr_digital_pricelist_category_nonce');
    ?>
    <div class="form-field">
        <label for="qr_enabled"><?php _e('Enabled', 'qr-digital-pricelist'); ?></label>
        <input type="checkbox" id="qr_enabled" name="qr_enabled" value="1" checked />
        <p class="description"><?php _e('Enable this category to be displayed on the frontend.', 'qr-digital-pricelist'); ?></p>
    </div>
    <div class="form-field">
        <label for="qr_sort_order"><?php _e('Sort Order', 'qr-digital-pricelist'); ?></label>
        <input type="number" id="qr_sort_order" name="qr_sort_order" value="0" step="1" min="0" />
        <p class="description"><?php _e('Lower numbers appear first. Used for custom ordering.', 'qr-digital-pricelist'); ?></p>
    </div>
    <div class="form-field">
        <label for="qr_enable_subsections"><?php _e('Enable Subsections', 'qr-digital-pricelist'); ?></label>
        <input type="checkbox" id="qr_enable_subsections" name="qr_enable_subsections" value="1" />
        <p class="description"><?php _e('If checked, child categories will appear as subsections before this category’s items.', 'qr-digital-pricelist'); ?></p>
    </div>
    <?php
}

add_action('qr_menu_category_add_form_fields', 'qr_digital_pricelist_category_add_meta_fields');

/**
 * Save category meta fields
 */
function qr_digital_pricelist_save_category_meta($term_id) {
    if (!isset($_POST['qr_digital_pricelist_category_nonce']) || 
        !wp_verify_nonce($_POST['qr_digital_pricelist_category_nonce'], 'qr_digital_pricelist_category_save')) {
        return;
    }

    if (!current_user_can('manage_categories')) {
        return;
    }

    // Save enabled status
    $enabled = isset($_POST['qr_enabled']) ? 1 : 0;
    update_term_meta($term_id, 'qr_enabled', $enabled);

    // Save sort order
    $sort_order = isset($_POST['qr_sort_order']) ? (int) $_POST['qr_sort_order'] : 0;
    update_term_meta($term_id, 'qr_sort_order', $sort_order);

    // Save subsections toggle
    $enable_subsections = isset($_POST['qr_enable_subsections']) ? 1 : 0;
    update_term_meta($term_id, 'qr_enable_subsections', $enable_subsections);
}

add_action('edited_qr_menu_category', 'qr_digital_pricelist_save_category_meta');
add_action('create_qr_menu_category', 'qr_digital_pricelist_save_category_meta');

/**
 * Add columns to category list
 */
function qr_digital_pricelist_category_columns($columns) {
    $new_columns = [];
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'name') {
            $new_columns['qr_enabled'] = __('Enabled', 'qr-digital-pricelist');
            $new_columns['qr_sort_order'] = __('Sort Order', 'qr-digital-pricelist');
        }
    }
    
    return $new_columns;
}

add_filter('manage_edit-qr_menu_category_columns', 'qr_digital_pricelist_category_columns');

/**
 * Display column data in category list
 */
function qr_digital_pricelist_category_column_content($content, $column_name, $term_id) {
    switch ($column_name) {
        case 'qr_enabled':
            $enabled = get_term_meta($term_id, 'qr_enabled', true);
            $enabled = $enabled !== '' ? (bool) $enabled : true;
            echo $enabled ? 
                '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>' : 
                '<span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>';
            break;
            
        case 'qr_sort_order':
            $sort_order = get_term_meta($term_id, 'qr_sort_order', true);
            echo esc_html($sort_order !== '' ? $sort_order : '0');
            break;
    }
    
    return $content;
}

add_filter('manage_qr_menu_category_custom_column', 'qr_digital_pricelist_category_column_content', 10, 3);

/**
 * Make category columns sortable
 */
function qr_digital_pricelist_category_sortable_columns($columns) {
    $columns['qr_sort_order'] = 'qr_sort_order';
    return $columns;
}

add_filter('manage_edit-qr_menu_category_sortable_columns', 'qr_digital_pricelist_category_sortable_columns');

/**
 * Custom sorting for categories
 */
function qr_digital_pricelist_category_sort_query($pieces) {
    global $wpdb, $pagenow;
    
    if (!is_admin() || $pagenow !== 'edit-tags.php' || !isset($_GET['taxonomy']) || 
        $_GET['taxonomy'] !== 'qr_menu_category' || !isset($_GET['orderby']) || 
        $_GET['orderby'] !== 'qr_sort_order') {
        return $pieces;
    }
    
    $pieces['join'] .= " LEFT JOIN {$wpdb->termmeta} AS tm ON {$wpdb->terms}.term_id = tm.term_id AND tm.meta_key = 'qr_sort_order'";
    $pieces['orderby'] = "ORDER BY CAST(tm.meta_value AS UNSIGNED) " . (isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC');
    
    return $pieces;
}

add_filter('terms_clauses', 'qr_digital_pricelist_category_sort_query');
