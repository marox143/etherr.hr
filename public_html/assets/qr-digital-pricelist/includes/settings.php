<?php
/**
 * Settings page
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings page callback
 */
function qr_digital_pricelist_settings_page() {
    if (isset($_POST['submit'])) {
        qr_digital_pricelist_handle_settings_save();
    }
    
    $currency_symbol = get_option('qr_digital_pricelist_currency_symbol', '€');
    $venue_name = get_option('qr_digital_pricelist_venue_name', '');
    $logo_url = get_option('qr_digital_pricelist_logo_url', '');
    $background_url = get_option('qr_digital_pricelist_background_url', '');
    $font_url = get_option('qr_digital_pricelist_font_url', '');
    $info_text = get_option('qr_digital_pricelist_info_text', '');
    ?>
    <div class="wrap">
        <h1><?php _e('QR Digital Pricelist Settings', 'qr-digital-pricelist'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('qr_digital_pricelist_settings_save'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="currency_symbol"><?php _e('Currency Symbol', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="currency_symbol" name="currency_symbol" value="<?php echo esc_attr($currency_symbol); ?>" maxlength="3" />
                        <p class="description"><?php _e('Currency symbol to display before prices (e.g., €, $, £).', 'qr-digital-pricelist'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="venue_name"><?php _e('Venue Name', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="venue_name" name="venue_name" value="<?php echo esc_attr($venue_name); ?>" />
                        <p class="description"><?php _e('Optional: Display name for your bar/venue. Leave empty to hide.', 'qr-digital-pricelist'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="logo_url"><?php _e('Logo Image URL', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="logo_url" name="logo_url" value="<?php echo esc_attr($logo_url); ?>" class="regular-text" />
                        <p class="description">
                            <?php _e('Paste the URL of a PNG/SVG uploaded to the Media Library. When set, the logo replaces the text header.', 'qr-digital-pricelist'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="background_url"><?php _e('Background Image URL', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="background_url" name="background_url" value="<?php echo esc_attr($background_url); ?>" class="regular-text" />
                        <p class="description">
                            <?php _e('Optional: Paste the URL of an image to use as the fullscreen wallpaper behind the menu. Leave empty to use the default texture.', 'qr-digital-pricelist'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="font_url"><?php _e('Custom Font File URL', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="font_url" name="font_url" value="<?php echo esc_attr($font_url); ?>" class="regular-text" />
                        <p class="description">
                            <?php _e('Optional: Paste the URL to a .woff2/.ttf font file. When set, the entire menu will use this font.', 'qr-digital-pricelist'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="info_text"><?php _e('Info Popup Text', 'qr-digital-pricelist'); ?></label>
                    </th>
                    <td>
                        <textarea id="info_text" name="info_text" rows="4" class="large-text code"><?php echo esc_textarea($info_text); ?></textarea>
                        <p class="description">
                            <?php _e('Content displayed inside the information popup. Supports line breaks and <strong>bold</strong> tags.', 'qr-digital-pricelist'); ?>
                        </p>
                    </td>
                </tr>

            </table>
            
            <?php submit_button(__('Save Settings', 'qr-digital-pricelist')); ?>
        </form>
        
        <hr />
        
        <h2><?php _e('Usage Instructions', 'qr-digital-pricelist'); ?></h2>
        
        <div class="qr-digital-pricelist-usage-info">
            <h3><?php _e('Display Your Menu', 'qr-digital-pricelist'); ?></h3>
            
            <h4><?php _e('Method 1: Shortcode', 'qr-digital-pricelist'); ?></h4>
            <p><?php _e('Add this shortcode to any page, post, or widget:', 'qr-digital-pricelist'); ?></p>
            <code>[qr_digital_pricelist]</code>
            
            <p><?php _e('Optional attributes:', 'qr-digital-pricelist'); ?></p>
            <ul>
                <li><code>category="slug"</code> - <?php _e('Display only items from a specific category', 'qr-digital-pricelist'); ?></li>
                <li><code>show_disabled="1"</code> - <?php _e('Include disabled items and categories', 'qr-digital-pricelist'); ?></li>
            </ul>
            
            <p><strong><?php _e('Examples:', 'qr-digital-pricelist'); ?></strong></p>
            <ul>
                <li><code>[qr_digital_pricelist category="beers"]</code> - <?php _e('Show only beer items', 'qr-digital-pricelist'); ?></li>
                <li><code>[qr_digital_pricelist show_disabled="1"]</code> - <?php _e('Show all items including disabled ones', 'qr-digital-pricelist'); ?></li>
            </ul>
            
            <h4><?php _e('Method 2: Gutenberg Block', 'qr-digital-pricelist'); ?></h4>
            <p><?php _e('In the WordPress block editor, search for and add the "QR Digital Pricelist" block.', 'qr-digital-pricelist'); ?></p>
            
            <h3><?php _e('Recommended Setup', 'qr-digital-pricelist'); ?></h3>
            <ol>
                <li><?php _e('Create categories for your menu sections (e.g., Beers, Wines, Spirits)', 'qr-digital-pricelist'); ?></li>
                <li><?php _e('Add menu items with their price variants', 'qr-digital-pricelist'); ?></li>
                <li><?php _e('Create a new page called "Menu" or "Price List"', 'qr-digital-pricelist'); ?></li>
                <li><?php _e('Add the shortcode or block to that page', 'qr-digital-pricelist'); ?></li>
                <li><?php _e('Generate a QR code pointing to that page', 'qr-digital-pricelist'); ?></li>
                <li><?php _e('Print and display the QR code for customers', 'qr-digital-pricelist'); ?></li>
            </ol>
            
            <h3><?php _e('Tips', 'qr-digital-pricelist'); ?></h3>
            <ul>
                <li><?php _e('Use sort order to organize categories and items exactly how you want them', 'qr-digital-pricelist'); ?></li>
                <li><?php _e('Disable items temporarily instead of deleting them', 'qr-digital-pricelist'); ?></li>
                <li><?php _e('Add multiple variants for the same item (e.g., small/large sizes)', 'qr-digital-pricelist'); ?></li>
                <li><?php _e('The menu is mobile-first and works great on phones', 'qr-digital-pricelist'); ?></li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Handle settings save
 */
function qr_digital_pricelist_handle_settings_save() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'qr_digital_pricelist_settings_save')) {
        wp_die(__('Security check failed.', 'qr-digital-pricelist'));
    }
    
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions.', 'qr-digital-pricelist'));
    }
    
    $currency_symbol = isset($_POST['currency_symbol']) ? sanitize_text_field($_POST['currency_symbol']) : '€';
    $venue_name = isset($_POST['venue_name']) ? sanitize_text_field($_POST['venue_name']) : '';
    $logo_url = isset($_POST['logo_url']) ? esc_url_raw(trim($_POST['logo_url'])) : '';
    $background_url = isset($_POST['background_url']) ? esc_url_raw(trim($_POST['background_url'])) : '';
    $font_url = isset($_POST['font_url']) ? esc_url_raw(trim($_POST['font_url'])) : '';
    $info_text_raw = isset($_POST['info_text']) ? wp_unslash($_POST['info_text']) : '';
    $allowed_info_tags = function_exists('qr_digital_pricelist_get_info_allowed_tags') ? qr_digital_pricelist_get_info_allowed_tags() : [];
    $info_text = wp_kses($info_text_raw, $allowed_info_tags);
    
    // Validate currency symbol (max 3 characters)
    if (strlen($currency_symbol) > 3) {
        $currency_symbol = substr($currency_symbol, 0, 3);
    }
    
    update_option('qr_digital_pricelist_currency_symbol', $currency_symbol);
    update_option('qr_digital_pricelist_venue_name', $venue_name);
    update_option('qr_digital_pricelist_logo_url', $logo_url);
    update_option('qr_digital_pricelist_background_url', $background_url);
    update_option('qr_digital_pricelist_font_url', $font_url);
    update_option('qr_digital_pricelist_info_text', $info_text);
    
    // Show success message
    add_settings_error('qr_digital_pricelist_settings', 'settings_saved', __('Settings saved successfully.', 'qr-digital-pricelist'), 'updated');
    set_transient('settings_errors', get_settings_errors(), 30);
    
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg(['page' => 'qr-digital-pricelist-settings', 'settings-updated' => 'true'], admin_url('admin.php')));
    exit;
}

/**
 * Display admin notices
 */
function qr_digital_pricelist_admin_notices() {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        settings_errors('qr_digital_pricelist_settings');
    }
}

add_action('admin_notices', 'qr_digital_pricelist_admin_notices');

/**
 * Get currency symbol
 */
function qr_digital_pricelist_get_currency_symbol() {
    return get_option('qr_digital_pricelist_currency_symbol', '€');
}

/**
 * Get venue name
 */
function qr_digital_pricelist_get_venue_name() {
    return get_option('qr_digital_pricelist_venue_name', '');
}

/**
 * Get logo URL
 */
function qr_digital_pricelist_get_logo_url() {
    return get_option('qr_digital_pricelist_logo_url', '');
}

/**
 * Get background image URL
 */
function qr_digital_pricelist_get_background_url() {
    return get_option('qr_digital_pricelist_background_url', '');
}

/**
 * Get custom font URL
 */
function qr_digital_pricelist_get_font_url() {
    return get_option('qr_digital_pricelist_font_url', '');
}

/**
 * Get info popup text
 */
function qr_digital_pricelist_get_info_text() {
    return get_option('qr_digital_pricelist_info_text', '');
}

/**
 * Allowed tags for info popup text formatting
 */
function qr_digital_pricelist_get_info_allowed_tags() {
    return [
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'br' => [],
    ];
}

