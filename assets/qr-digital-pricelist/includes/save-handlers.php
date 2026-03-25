<?php
/**
 * Save handlers and validation
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validate and sanitize variant data
 */
function qr_digital_pricelist_validate_variants($variants) {
    if (!is_array($variants)) {
        return [];
    }
    
    $validated_variants = [];
    
    foreach ($variants as $variant) {
        if (!is_array($variant)) {
            continue;
        }
        
        $validated_variant = [
            'volume_value' => isset($variant['volume_value']) ? sanitize_text_field($variant['volume_value']) : '',
            'unit_slug' => isset($variant['unit_slug']) ? sanitize_text_field($variant['unit_slug']) : '',
            'price' => isset($variant['price']) ? floatval($variant['price']) : 0.00,
            'enabled' => isset($variant['enabled']) ? 1 : 0,
            'sort_order' => isset($variant['sort_order']) ? (int) $variant['sort_order'] : 0,
        ];
        
        // Validate unit exists
        $units = qr_digital_pricelist_get_units();
        if (!isset($units[$validated_variant['unit_slug']])) {
            $validated_variant['unit_slug'] = '';
        }
        
        // Only add variant if it has valid data
        if (!empty($validated_variant['volume_value']) && $validated_variant['price'] >= 0) {
            $validated_variants[] = $validated_variant;
        }
    }
    
    return $validated_variants;
}

/**
 * Sanitize sort order value
 */
function qr_digital_pricelist_sanitize_sort_order($value) {
    return max(0, (int) $value);
}

/**
 * Sanitize boolean value
 */
function qr_digital_pricelist_sanitize_boolean($value) {
    return $value ? 1 : 0;
}

/**
 * Validate category data
 */
function qr_digital_pricelist_validate_category_data($term_id, $tt_id) {
    // Ensure category has required meta
    $enabled = get_term_meta($term_id, 'qr_enabled', true);
    $sort_order = get_term_meta($term_id, 'qr_sort_order', true);
    
    if ($enabled === '') {
        update_term_meta($term_id, 'qr_enabled', 1);
    }
    
    if ($sort_order === '') {
        update_term_meta($term_id, 'qr_sort_order', 0);
    }
}

add_action('created_qr_menu_category', 'qr_digital_pricelist_validate_category_data', 10, 2);
add_action('edited_qr_menu_category', 'qr_digital_pricelist_validate_category_data', 10, 2);

/**
 * Validate item data
 */
function qr_digital_pricelist_validate_item_data($post_id, $post, $update) {
    if ($post->post_type !== 'qr_menu_item') {
        return;
    }
    
    // Ensure item has required meta
    $enabled = get_post_meta($post_id, 'qr_enabled', true);
    $sort_order = get_post_meta($post_id, 'qr_sort_order', true);
    $variants = get_post_meta($post_id, 'qr_variants', true);
    
    if ($enabled === '') {
        update_post_meta($post_id, 'qr_enabled', 1);
    }
    
    if ($sort_order === '') {
        update_post_meta($post_id, 'qr_sort_order', 0);
    }
    
    if (!is_array($variants)) {
        update_post_meta($post_id, 'qr_variants', []);
    }
}

add_action('save_post_qr_menu_item', 'qr_digital_pricelist_validate_item_data', 10, 3);

/**
 * AJAX handler for adding variant
 */
function qr_digital_pricelist_ajax_add_variant() {
    check_ajax_referer('qr_digital_pricelist_admin_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions.', 'qr-digital-pricelist'));
    }
    
    $index = isset($_POST['index']) ? (int) $_POST['index'] : 0;
    $units = qr_digital_pricelist_get_units();
    
    ob_start();
    ?>
    <div class="qr-variant-row" data-index="<?php echo esc_attr($index); ?>">
        <div class="qr-variant-fields">
            <div class="qr-variant-field">
                <label><?php _e('Volume', 'qr-digital-pricelist'); ?></label>
                <input type="text" name="qr_variants[<?php echo $index; ?>][volume_value]" placeholder="0.5" />
            </div>
            
            <div class="qr-variant-field">
                <label><?php _e('Unit', 'qr-digital-pricelist'); ?></label>
                <select name="qr_variants[<?php echo $index; ?>][unit_slug]">
                    <?php foreach ($units as $slug => $label): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="qr-variant-field">
                <label><?php _e('Price', 'qr-digital-pricelist'); ?></label>
                <input type="text" name="qr_variants[<?php echo $index; ?>][price]" placeholder="5.00" />
            </div>
            
            <div class="qr-variant-field">
                <label><?php _e('Enabled', 'qr-digital-pricelist'); ?></label>
                <input type="checkbox" name="qr_variants[<?php echo $index; ?>][enabled]" value="1" checked />
            </div>
            
            <div class="qr-variant-field">
                <label><?php _e('Sort Order', 'qr-digital-pricelist'); ?></label>
                <input type="number" name="qr_variants[<?php echo $index; ?>][sort_order]" value="0" step="1" min="0" />
            </div>
            
            <div class="qr-variant-actions">
                <button type="button" class="button qr-remove-variant"><?php _e('Remove', 'qr-digital-pricelist'); ?></button>
            </div>
        </div>
    </div>
    <?php
    
    $html = ob_get_clean();
    
    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_qr_digital_pricelist_add_variant', 'qr_digital_pricelist_ajax_add_variant');

/**
 * AJAX handler for removing variant
 */
function qr_digital_pricelist_ajax_remove_variant() {
    check_ajax_referer('qr_digital_pricelist_admin_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions.', 'qr-digital-pricelist'));
    }
    
    wp_send_json_success();
}

add_action('wp_ajax_qr_digital_pricelist_remove_variant', 'qr_digital_pricelist_ajax_remove_variant');

/**
 * Bulk actions for categories
 */
function qr_digital_pricelist_bulk_category_actions() {
    if (!isset($_POST['action']) && !isset($_POST['action2'])) {
        return;
    }
    
    $action = $_POST['action'] !== '-1' ? $_POST['action'] : $_POST['action2'];
    
    if ($action === 'qr_enable_categories' || $action === 'qr_disable_categories') {
        check_admin_referer('bulk-tags');
        
        if (!current_user_can('manage_categories')) {
            wp_die(__('You do not have sufficient permissions.', 'qr-digital-pricelist'));
        }
        
        if (isset($_POST['delete_tags']) && is_array($_POST['delete_tags'])) {
            $enabled = $action === 'qr_enable_categories' ? 1 : 0;
            
            foreach ($_POST['delete_tags'] as $term_id) {
                update_term_meta((int) $term_id, 'qr_enabled', $enabled);
            }
        }
    }
}

add_action('admin_init', 'qr_digital_pricelist_bulk_category_actions');

/**
 * Add bulk actions to category dropdown
 */
function qr_digital_pricelist_category_bulk_actions($actions) {
    $actions['qr_enable_categories'] = __('Enable', 'qr-digital-pricelist');
    $actions['qr_disable_categories'] = __('Disable', 'qr-digital-pricelist');
    return $actions;
}

add_filter('bulk_actions-edit-qr_menu_category', 'qr_digital_pricelist_category_bulk_actions');

/**
 * Bulk actions for items
 */
function qr_digital_pricelist_bulk_item_actions() {
    if (!isset($_POST['action']) && !isset($_POST['action2'])) {
        return;
    }
    
    $action = $_POST['action'] !== '-1' ? $_POST['action'] : $_POST['action2'];
    
    if ($action === 'qr_enable_items' || $action === 'qr_disable_items') {
        check_admin_referer('bulk-posts');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions.', 'qr-digital-pricelist'));
        }
        
        if (isset($_POST['post']) && is_array($_POST['post'])) {
            $enabled = $action === 'qr_enable_items' ? 1 : 0;
            
            foreach ($_POST['post'] as $post_id) {
                update_post_meta((int) $post_id, 'qr_enabled', $enabled);
            }
        }
    }
}

add_action('admin_init', 'qr_digital_pricelist_bulk_item_actions');

/**
 * Add bulk actions to items dropdown
 */
function qr_digital_pricelist_item_bulk_actions($actions) {
    $actions['qr_enable_items'] = __('Enable', 'qr-digital-pricelist');
    $actions['qr_disable_items'] = __('Disable', 'qr-digital-pricelist');
    return $actions;
}

add_filter('bulk_actions-edit-qr_menu_item', 'qr_digital_pricelist_item_bulk_actions');
