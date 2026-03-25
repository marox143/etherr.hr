<?php
/**
 * Units management
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Units page callback
 */
function qr_digital_pricelist_units_page() {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';
    
    switch ($action) {
        case 'add':
            qr_digital_pricelist_units_add_page();
            break;
        case 'edit':
            qr_digital_pricelist_units_edit_page();
            break;
        case 'delete':
            qr_digital_pricelist_units_handle_delete();
            break;
        case 'save':
            qr_digital_pricelist_units_handle_save();
            break;
        default:
            qr_digital_pricelist_units_list_page();
            break;
    }
}

/**
 * Units list page
 */
function qr_digital_pricelist_units_list_page() {
    $units = get_option('qr_menu_units', []);
    ?>
    <div class="wrap">
        <h1><?php _e('Measurement Units', 'qr-digital-pricelist'); ?> 
            <a href="<?php echo esc_url(add_query_arg(['action' => 'add'])); ?>" class="page-title-action"><?php _e('Add New', 'qr-digital-pricelist'); ?></a>
        </h1>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Slug', 'qr-digital-pricelist'); ?></th>
                    <th><?php _e('Label', 'qr-digital-pricelist'); ?></th>
                    <th><?php _e('Actions', 'qr-digital-pricelist'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($units as $slug => $label): ?>
                    <tr>
                        <td><code><?php echo esc_html($slug); ?></code></td>
                        <td><?php echo esc_html($label); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'slug' => $slug])); ?>" class="button"><?php _e('Edit', 'qr-digital-pricelist'); ?></a>
                            <?php if (!in_array($slug, ['ml', 'l', 'cup', 'shot', 'glass', 'bottle'])): ?>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'delete', 'slug' => $slug]), 'qr_digital_pricelist_delete_unit_' . $slug)); ?>" class="button" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this unit?', 'qr-digital-pricelist')); ?>');"><?php _e('Delete', 'qr-digital-pricelist'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($units)): ?>
            <p><?php _e('No units found. Add your first measurement unit.', 'qr-digital-pricelist'); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Units add page
 */
function qr_digital_pricelist_units_add_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Add New Unit', 'qr-digital-pricelist'); ?></h1>
        
        <form method="post" action="">
            <input type="hidden" name="action" value="save">
            <?php wp_nonce_field('qr_digital_pricelist_save_unit'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="unit_slug"><?php _e('Slug', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="unit_slug" name="unit_slug" required pattern="[a-z0-9_-]+" title="<?php esc_attr_e('Only lowercase letters, numbers, hyphens, and underscores allowed', 'qr-digital-pricelist'); ?>" />
                        <p class="description"><?php _e('Unique identifier used in code. Only lowercase letters, numbers, hyphens, and underscores allowed.', 'qr-digital-pricelist'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="unit_label"><?php _e('Label', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="unit_label" name="unit_label" required />
                        <p class="description"><?php _e('Display name for this unit (e.g., "ml", "liter", "shot").', 'qr-digital-pricelist'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Add Unit', 'qr-digital-pricelist')); ?>
        </form>
    </div>
    <?php
}

/**
 * Units edit page
 */
function qr_digital_pricelist_units_edit_page() {
    $slug = isset($_GET['slug']) ? sanitize_text_field($_GET['slug']) : '';
    $units = get_option('qr_menu_units', []);
    
    if (!isset($units[$slug])) {
        wp_die(__('Unit not found.', 'qr-digital-pricelist'));
    }
    
    $label = $units[$slug];
    ?>
    <div class="wrap">
        <h1><?php _e('Edit Unit', 'qr-digital-pricelist'); ?></h1>
        
        <form method="post" action="">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="original_slug" value="<?php echo esc_attr($slug); ?>">
            <?php wp_nonce_field('qr_digital_pricelist_save_unit'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="unit_slug"><?php _e('Slug', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="unit_slug" name="unit_slug" value="<?php echo esc_attr($slug); ?>" required pattern="[a-z0-9_-]+" title="<?php esc_attr_e('Only lowercase letters, numbers, hyphens, and underscores allowed', 'qr-digital-pricelist'); ?>" />
                        <p class="description"><?php _e('Unique identifier used in code. Only lowercase letters, numbers, hyphens, and underscores allowed.', 'qr-digital-pricelist'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="unit_label"><?php _e('Label', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="unit_label" name="unit_label" value="<?php echo esc_attr($label); ?>" required />
                        <p class="description"><?php _e('Display name for this unit (e.g., "ml", "liter", "shot").', 'qr-digital-pricelist'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Update Unit', 'qr-digital-pricelist')); ?>
        </form>
    </div>
    <?php
}

/**
 * Handle unit save
 */
function qr_digital_pricelist_units_handle_save() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'qr_digital_pricelist_save_unit')) {
        wp_die(__('Security check failed.', 'qr-digital-pricelist'));
    }
    
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions.', 'qr-digital-pricelist'));
    }
    
    $slug = isset($_POST['unit_slug']) ? sanitize_text_field($_POST['unit_slug']) : '';
    $label = isset($_POST['unit_label']) ? sanitize_text_field($_POST['unit_label']) : '';
    $original_slug = isset($_POST['original_slug']) ? sanitize_text_field($_POST['original_slug']) : '';
    
    if (empty($slug) || empty($label)) {
        wp_die(__('Slug and label are required.', 'qr-digital-pricelist'));
    }
    
    if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
        wp_die(__('Invalid slug format.', 'qr-digital-pricelist'));
    }
    
    $units = get_option('qr_menu_units', []);
    
    // Check if slug already exists (except when editing the same slug)
    if (isset($units[$slug]) && $slug !== $original_slug) {
        wp_die(__('This slug already exists.', 'qr-digital-pricelist'));
    }
    
    // Remove old slug if editing
    if ($original_slug && $original_slug !== $slug) {
        unset($units[$original_slug]);
    }
    
    // Add/update unit
    $units[$slug] = $label;
    update_option('qr_menu_units', $units);
    
    // Redirect to list page
    wp_redirect(add_query_arg(['page' => 'qr-digital-pricelist-units'], admin_url('admin.php')));
    exit;
}

/**
 * Handle unit delete
 */
function qr_digital_pricelist_units_handle_delete() {
    $slug = isset($_GET['slug']) ? sanitize_text_field($_GET['slug']) : '';
    
    if (empty($slug)) {
        wp_die(__('Unit not specified.', 'qr-digital-pricelist'));
    }
    
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'qr_digital_pricelist_delete_unit_' . $slug)) {
        wp_die(__('Security check failed.', 'qr-digital-pricelist'));
    }
    
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions.', 'qr-digital-pricelist'));
    }
    
    // Don't allow deletion of default units
    if (in_array($slug, ['ml', 'l', 'cup', 'shot', 'glass', 'bottle'])) {
        wp_die(__('Cannot delete default units.', 'qr-digital-pricelist'));
    }
    
    $units = get_option('qr_menu_units', []);
    
    if (isset($units[$slug])) {
        unset($units[$slug]);
        update_option('qr_menu_units', $units);
    }
    
    // Redirect to list page
    wp_redirect(add_query_arg(['page' => 'qr-digital-pricelist-units'], admin_url('admin.php')));
    exit;
}

/**
 * Get all units
 */
function qr_digital_pricelist_get_units() {
    return get_option('qr_menu_units', []);
}

/**
 * Get unit label by slug
 */
function qr_digital_pricelist_get_unit_label($slug) {
    $units = qr_digital_pricelist_get_units();
    return isset($units[$slug]) ? $units[$slug] : $slug;
}
