<?php
/**
 * Admin menu setup
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu items
 */
function qr_digital_pricelist_admin_menu() {
    // Main menu
    add_menu_page(
        __('QR Digital Pricelist', 'qr-digital-pricelist'),
        __('QR Digital Pricelist', 'qr-digital-pricelist'),
        'edit_posts',
        'qr-digital-pricelist',
        'qr_digital_pricelist_dashboard_page',
        'dashicons-menu-alt3',
        30
    );

    // Submenu: Dashboard
    add_submenu_page(
        'qr-digital-pricelist',
        __('Dashboard', 'qr-digital-pricelist'),
        __('Dashboard', 'qr-digital-pricelist'),
        'edit_posts',
        'qr-digital-pricelist',
        'qr_digital_pricelist_dashboard_page'
    );

    // Submenu: Categories
    add_submenu_page(
        'qr-digital-pricelist',
        __('Categories', 'qr-digital-pricelist'),
        __('Categories', 'qr-digital-pricelist'),
        'edit_posts',
        'edit-tags.php?taxonomy=qr_menu_category&post_type=qr_menu_item'
    );

    // Submenu: Items
    add_submenu_page(
        'qr-digital-pricelist',
        __('Items', 'qr-digital-pricelist'),
        __('Items', 'qr-digital-pricelist'),
        'edit_posts',
        'edit.php?post_type=qr_menu_item'
    );

    // Submenu: Units
    add_submenu_page(
        'qr-digital-pricelist',
        __('Units', 'qr-digital-pricelist'),
        __('Units', 'qr-digital-pricelist'),
        'manage_options',
        'qr-digital-pricelist-units',
        'qr_digital_pricelist_units_page'
    );

    // Submenu: Settings
    add_submenu_page(
        'qr-digital-pricelist',
        __('Settings', 'qr-digital-pricelist'),
        __('Settings', 'qr-digital-pricelist'),
        'manage_options',
        'qr-digital-pricelist-settings',
        'qr_digital_pricelist_settings_page'
    );
}

add_action('admin_menu', 'qr_digital_pricelist_admin_menu');

/**
 * Dashboard page callback
 */
function qr_digital_pricelist_dashboard_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="qr-digital-pricelist-dashboard">
            <div class="qr-digital-pricelist-welcome">
                <h2><?php _e('Welcome to QR Digital Pricelist', 'qr-digital-pricelist'); ?></h2>
                <p><?php _e('Manage your bar\'s digital menu and pricing with ease. Create categories, add items with variants, and display them using shortcodes or Gutenberg blocks.', 'qr-digital-pricelist'); ?></p>
            </div>

            <div class="qr-digital-pricelist-stats">
                <div class="qr-digital-pricelist-stat">
                    <h3><?php _e('Categories', 'qr-digital-pricelist'); ?></h3>
                    <div class="stat-number"><?php echo wp_count_terms('qr_menu_category'); ?></div>
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=qr_menu_category&post_type=qr_menu_item')); ?>" class="button"><?php _e('Manage Categories', 'qr-digital-pricelist'); ?></a>
                </div>

                <div class="qr-digital-pricelist-stat">
                    <h3><?php _e('Menu Items', 'qr-digital-pricelist'); ?></h3>
                    <div class="stat-number"><?php echo wp_count_posts('qr_menu_item')->publish; ?></div>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=qr_menu_item')); ?>" class="button"><?php _e('Manage Items', 'qr-digital-pricelist'); ?></a>
                </div>

                <div class="qr-digital-pricelist-stat">
                    <h3><?php _e('Units', 'qr-digital-pricelist'); ?></h3>
                    <div class="stat-number"><?php echo count(get_option('qr_menu_units', [])); ?></div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=qr-digital-pricelist-units')); ?>" class="button"><?php _e('Manage Units', 'qr-digital-pricelist'); ?></a>
                </div>
            </div>

            <div class="qr-digital-pricelist-quick-actions">
                <h3><?php _e('Quick Actions', 'qr-digital-pricelist'); ?></h3>
                <div class="quick-actions-grid">
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=qr_menu_item')); ?>" class="quick-action">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <span><?php _e('Add New Item', 'qr-digital-pricelist'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=qr_menu_category&post_type=qr_menu_item')); ?>" class="quick-action">
                        <span class="dashicons dashicons-category"></span>
                        <span><?php _e('Add Category', 'qr-digital-pricelist'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=qr-digital-pricelist-settings')); ?>" class="quick-action">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span><?php _e('Configure Settings', 'qr-digital-pricelist'); ?></span>
                    </a>
                </div>
            </div>

            <div class="qr-digital-pricelist-usage">
                <h3><?php _e('How to Display Your Menu', 'qr-digital-pricelist'); ?></h3>
                <div class="usage-instructions">
                    <div class="usage-method">
                        <h4><?php _e('Shortcode Method', 'qr-digital-pricelist'); ?></h4>
                        <p><?php _e('Add this shortcode to any page or post:', 'qr-digital-pricelist'); ?></p>
                        <code>[qr_digital_pricelist]</code>
                        <p><?php _e('Optional attributes:', 'qr-digital-pricelist'); ?></p>
                        <ul>
                            <li><code>category="slug"</code> - <?php _e('Show only specific category', 'qr-digital-pricelist'); ?></li>
                            <li><code>show_disabled="1"</code> - <?php _e('Include disabled items', 'qr-digital-pricelist'); ?></li>
                        </ul>
                    </div>
                    <div class="usage-method">
                        <h4><?php _e('Gutenberg Block', 'qr-digital-pricelist'); ?></h4>
                        <p><?php _e('Use the "QR Digital Pricelist" block in the WordPress block editor.', 'qr-digital-pricelist'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
