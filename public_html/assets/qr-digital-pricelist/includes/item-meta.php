<?php
/**
 * Item meta fields handling
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta fields to item edit form
 */
function qr_digital_pricelist_item_meta_fields() {
    global $post;
    
    $enabled = get_post_meta($post->ID, 'qr_enabled', true);
    $sort_order = get_post_meta($post->ID, 'qr_sort_order', true);
    $variants = get_post_meta($post->ID, 'qr_variants', true);
    
    $enabled = $enabled !== '' ? (bool) $enabled : true;
    $sort_order = $sort_order !== '' ? (int) $sort_order : 0;
    $variants = is_array($variants) ? $variants : [];
    
    wp_nonce_field('qr_digital_pricelist_item_save', 'qr_digital_pricelist_item_nonce');
    
    // Get available units
    $units = get_option('qr_menu_units', []);
    ?>
    <div class="qr-digital-pricelist-meta">
        <div class="qr-digital-pricelist-field">
            <label for="qr_enabled"><?php _e('Enabled', 'qr-digital-pricelist'); ?></label>
            <input type="checkbox" id="qr_enabled" name="qr_enabled" value="1" <?php checked($enabled); ?> />
            <p class="description"><?php _e('Enable this item to be displayed on the frontend.', 'qr-digital-pricelist'); ?></p>
        </div>
        
        <div class="qr-digital-pricelist-field">
            <label for="qr_sort_order"><?php _e('Sort Order', 'qr-digital-pricelist'); ?></label>
            <input type="number" id="qr_sort_order" name="qr_sort_order" value="<?php echo esc_attr($sort_order); ?>" step="1" min="0" />
            <p class="description"><?php _e('Lower numbers appear first. Used for custom ordering.', 'qr-digital-pricelist'); ?></p>
        </div>
        
        <div class="qr-digital-pricelist-field">
            <h3><?php _e('Price Variants', 'qr-digital-pricelist'); ?></h3>
            <p class="description"><?php _e('Add different volumes/sizes and their prices for this item.', 'qr-digital-pricelist'); ?></p>
            
            <div id="qr-variants-container">
                <?php foreach ($variants as $index => $variant): ?>
                    <div class="qr-variant-row" data-index="<?php echo esc_attr($index); ?>">
                        <div class="qr-variant-fields">
                            <div class="qr-variant-field">
                                <label><?php _e('Volume', 'qr-digital-pricelist'); ?></label>
                                <input type="text" name="qr_variants[<?php echo $index; ?>][volume_value]" value="<?php echo esc_attr($variant['volume_value']); ?>" placeholder="0.5" />
                            </div>
                            
                            <div class="qr-variant-field">
                                <label><?php _e('Unit', 'qr-digital-pricelist'); ?></label>
                                <select name="qr_variants[<?php echo $index; ?>][unit_slug]">
                                    <?php foreach ($units as $slug => $label): ?>
                                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($variant['unit_slug'], $slug); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="qr-variant-field">
                                <label><?php _e('Price', 'qr-digital-pricelist'); ?></label>
                                <input type="text" name="qr_variants[<?php echo $index; ?>][price]" value="<?php echo esc_attr($variant['price']); ?>" placeholder="5.00" />
                            </div>
                            
                            <div class="qr-variant-field">
                                <label><?php _e('Enabled', 'qr-digital-pricelist'); ?></label>
                                <input type="checkbox" name="qr_variants[<?php echo $index; ?>][enabled]" value="1" <?php checked($variant['enabled']); ?> />
                            </div>
                            
                            <div class="qr-variant-field">
                                <label><?php _e('Sort Order', 'qr-digital-pricelist'); ?></label>
                                <input type="number" name="qr_variants[<?php echo $index; ?>][sort_order]" value="<?php echo esc_attr($variant['sort_order']); ?>" step="1" min="0" />
                            </div>
                            
                            <div class="qr-variant-actions">
                                <button type="button" class="button qr-remove-variant"><?php _e('Remove', 'qr-digital-pricelist'); ?></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="qr-add-variant" class="button"><?php _e('Add Variant', 'qr-digital-pricelist'); ?></button>
        </div>
    </div>
    
    <script type="text/html" id="qr-variant-template">
        <div class="qr-variant-row" data-index="{{index}}">
            <div class="qr-variant-fields">
                <div class="qr-variant-field">
                    <label><?php _e('Volume', 'qr-digital-pricelist'); ?></label>
                    <input type="text" name="qr_variants[{{index}}][volume_value]" placeholder="0.5" />
                </div>
                
                <div class="qr-variant-field">
                    <label><?php _e('Unit', 'qr-digital-pricelist'); ?></label>
                    <select name="qr_variants[{{index}}][unit_slug]">
                        <?php foreach ($units as $slug => $label): ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="qr-variant-field">
                    <label><?php _e('Price', 'qr-digital-pricelist'); ?></label>
                    <input type="text" name="qr_variants[{{index}}][price]" placeholder="5.00" />
                </div>
                
                <div class="qr-variant-field">
                    <label><?php _e('Enabled', 'qr-digital-pricelist'); ?></label>
                    <input type="checkbox" name="qr_variants[{{index}}][enabled]" value="1" checked />
                </div>
                
                <div class="qr-variant-field">
                    <label><?php _e('Sort Order', 'qr-digital-pricelist'); ?></label>
                    <input type="number" name="qr_variants[{{index}}][sort_order]" value="0" step="1" min="0" />
                </div>
                
                <div class="qr-variant-actions">
                    <button type="button" class="button qr-remove-variant"><?php _e('Remove', 'qr-digital-pricelist'); ?></button>
                </div>
            </div>
        </div>
    </script>
    <?php
}

add_action('add_meta_boxes', function() {
    add_meta_box(
        'qr_digital_pricelist_item_meta',
        __('Menu Item Settings', 'qr-digital-pricelist'),
        'qr_digital_pricelist_item_meta_fields',
        'qr_menu_item',
        'normal',
        'high'
    );
});

/**
 * Save item meta fields
 */
function qr_digital_pricelist_save_item_meta($post_id) {
    if (!isset($_POST['qr_digital_pricelist_item_nonce']) || 
        !wp_verify_nonce($_POST['qr_digital_pricelist_item_nonce'], 'qr_digital_pricelist_item_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save enabled status
    $enabled = isset($_POST['qr_enabled']) ? 1 : 0;
    update_post_meta($post_id, 'qr_enabled', $enabled);

    // Save sort order
    $sort_order = isset($_POST['qr_sort_order']) ? (int) $_POST['qr_sort_order'] : 0;
    update_post_meta($post_id, 'qr_sort_order', $sort_order);

    // Save variants
    $variants = isset($_POST['qr_variants']) ? $_POST['qr_variants'] : [];
    $sanitized_variants = [];

    foreach ($variants as $variant_data) {
        if (!is_array($variant_data)) {
            continue;
        }

        $sanitized_variant = [
            'volume_value' => isset($variant_data['volume_value']) ? sanitize_text_field($variant_data['volume_value']) : '',
            'unit_slug' => isset($variant_data['unit_slug']) ? sanitize_text_field($variant_data['unit_slug']) : '',
            'price' => isset($variant_data['price']) ? floatval($variant_data['price']) : 0.00,
            'enabled' => isset($variant_data['enabled']) ? 1 : 0,
            'sort_order' => isset($variant_data['sort_order']) ? (int) $variant_data['sort_order'] : 0,
        ];

        // Only add variant if it has at least volume and price
        if (!empty($sanitized_variant['volume_value']) && $sanitized_variant['price'] > 0) {
            $sanitized_variants[] = $sanitized_variant;
        }
    }

    update_post_meta($post_id, 'qr_variants', $sanitized_variants);
}

add_action('save_post_qr_menu_item', 'qr_digital_pricelist_save_item_meta');

/**
 * Add columns to items list
 */
function qr_digital_pricelist_item_columns($columns) {
    $new_columns = [];
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['qr_enabled'] = __('Enabled', 'qr-digital-pricelist');
            $new_columns['qr_sort_order'] = __('Sort Order', 'qr-digital-pricelist');
            $new_columns['qr_variants'] = __('Variants', 'qr-digital-pricelist');
        }
    }
    
    return $new_columns;
}

add_filter('manage_qr_menu_item_posts_columns', 'qr_digital_pricelist_item_columns');

/**
 * Display column data in items list
 */
function qr_digital_pricelist_item_column_content($column, $post_id) {
    switch ($column) {
        case 'qr_enabled':
            $enabled = get_post_meta($post_id, 'qr_enabled', true);
            $enabled = $enabled !== '' ? (bool) $enabled : true;
            echo $enabled ? 
                '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>' : 
                '<span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>';
            break;
            
        case 'qr_sort_order':
            $sort_order = get_post_meta($post_id, 'qr_sort_order', true);
            echo esc_html($sort_order !== '' ? $sort_order : '0');
            break;
            
        case 'qr_variants':
            $variants = get_post_meta($post_id, 'qr_variants', true);
            $variants = is_array($variants) ? $variants : [];
            $enabled_variants = array_filter($variants, function($variant) {
                return isset($variant['enabled']) && $variant['enabled'];
            });
            echo count($enabled_variants) . ' / ' . count($variants);
            break;
    }
}

add_action('manage_qr_menu_item_posts_custom_column', 'qr_digital_pricelist_item_column_content', 10, 2);

/**
 * Make item columns sortable
 */
function qr_digital_pricelist_item_sortable_columns($columns) {
    $columns['qr_sort_order'] = 'qr_sort_order';
    return $columns;
}

add_filter('manage_edit-qr_menu_item_sortable_columns', 'qr_digital_pricelist_item_sortable_columns');

/**
 * Custom sorting for items
 */
function qr_digital_pricelist_item_sort_query($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'qr_menu_item') {
        return;
    }
    
    $orderby = $query->get('orderby');
    if ($orderby === 'qr_sort_order') {
        $query->set('meta_key', 'qr_sort_order');
        $query->set('orderby', 'meta_value_num');
    }
}

add_action('pre_get_posts', 'qr_digital_pricelist_item_sort_query');
