<?php
/**
 * Fullscreen page template registration
 *
 * @package QR_Digital_Pricelist
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('QR_DIGITAL_PRICELIST_FULLSCREEN_TEMPLATE')) {
    define('QR_DIGITAL_PRICELIST_FULLSCREEN_TEMPLATE', 'qr-digital-pricelist-fullscreen.php');
}

if (!defined('QR_DIGITAL_PRICELIST_BLOCK_TEMPLATE_SLUG')) {
    define('QR_DIGITAL_PRICELIST_BLOCK_TEMPLATE_SLUG', 'qr-pricelist-fullscreen');
}

if (!defined('QR_DIGITAL_PRICELIST_FULLSCREEN_LABEL')) {
    define('QR_DIGITAL_PRICELIST_FULLSCREEN_LABEL', __('QR Pricelist – Fullscreen', 'qr-digital-pricelist'));
}

/**
 * Register fullscreen template with the current theme.
 */
function qr_digital_pricelist_register_fullscreen_template($templates) {
    if (!is_array($templates)) {
        $templates = [];
    }

    $templates[QR_DIGITAL_PRICELIST_FULLSCREEN_TEMPLATE] = QR_DIGITAL_PRICELIST_FULLSCREEN_LABEL;

    return $templates;
}
add_filter('theme_page_templates', 'qr_digital_pricelist_register_fullscreen_template');

/**
 * Swap the page template when the fullscreen layout is selected.
 */
function qr_digital_pricelist_load_fullscreen_template($template) {
    if (!is_singular('page')) {
        return $template;
    }

    $selected_template = get_page_template_slug(get_queried_object_id());

    if ($selected_template !== QR_DIGITAL_PRICELIST_FULLSCREEN_TEMPLATE) {
        return $template;
    }

    $plugin_template = trailingslashit(QR_DIGITAL_PRICELIST_PLUGIN_DIR) . 'templates/' . QR_DIGITAL_PRICELIST_FULLSCREEN_TEMPLATE;

    if (file_exists($plugin_template)) {
        return $plugin_template;
    }

    return $template;
}
add_filter('template_include', 'qr_digital_pricelist_load_fullscreen_template');

/**
 * Get the block markup for the fullscreen template (used by block themes).
 */
function qr_digital_pricelist_get_block_template_content() {
    return '<!-- wp:group {"tagName":"main","align":"full","layout":{"type":"constrained"},"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
<main class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
<!-- wp:shortcode -->[qr_digital_pricelist]<!-- /wp:shortcode -->
</main>
<!-- /wp:group -->';
}

/**
 * Flag that a block template exists so sites can optionally opt-in.
 * Users can manually create a template via the Site Editor using the provided markup.
 */
function qr_digital_pricelist_register_block_template_support() {
    if (!function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
        return;
    }

    add_theme_support('qr-digital-pricelist-fullscreen-template');
}
add_action('after_setup_theme', 'qr_digital_pricelist_register_block_template_support');
