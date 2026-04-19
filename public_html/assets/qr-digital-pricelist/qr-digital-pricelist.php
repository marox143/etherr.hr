<?php
/**
 * Plugin Name: QR Digital Pricelist for Bars
 * Plugin URI: https://etherr.com
 * Description: Mobile-first digital pricelist/menu plugin for bars, accessible via QR code. Manage menu items, categories, and pricing through WordPress admin.
 * Version: 1.0.0
 * Author: Etherr
 * Author URI: https://etherr.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: qr-digital-pricelist
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

define('QR_DIGITAL_PRICELIST_VERSION', '1.0.0');
define('QR_DIGITAL_PRICELIST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QR_DIGITAL_PRICELIST_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class QR_Digital_Pricelist {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        $this->load_includes();
        $this->init_hooks();
        
        // Load text domain
        load_plugin_textdomain('qr-digital-pricelist', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Load required files
     */
    private function load_includes() {
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/cpt-taxonomies.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/admin-menu.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/category-meta.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/item-meta.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/units.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/settings.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/save-handlers.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/shortcode.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/helpers.php';
        require_once QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'includes/fullscreen-template.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Frontend assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Register shortcode
        add_shortcode('qr_digital_pricelist', ['QR_Digital_Pricelist_Shortcode', 'render']);
        
        // Register Gutenberg block (if available)
        if (function_exists('register_block_type')) {
            add_action('init', [$this, 'register_gutenberg_block']);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default units
        $this->create_default_units();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create default measurement units
     */
    private function create_default_units() {
        $default_units = [
            'ml' => 'ml',
            'l' => 'l',
            'cup' => 'cup',
            'shot' => 'shot',
            'glass' => 'glass',
            'bottle' => 'bottle'
        ];
        
        $existing_units = get_option('qr_menu_units', []);
        
        if (empty($existing_units)) {
            update_option('qr_menu_units', $default_units);
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        $should_enqueue = strpos($hook, 'qr-digital-pricelist') !== false;

        if (!$should_enqueue && $screen instanceof WP_Screen) {
            $should_enqueue = in_array($screen->post_type ?? '', ['qr_menu_item'], true);
        }

        if (!$should_enqueue) {
            return;
        }

        wp_enqueue_script(
            'qr-digital-pricelist-admin',
            QR_DIGITAL_PRICELIST_PLUGIN_URL . 'assets/admin.js',
            ['jquery', 'jquery-ui-sortable'],
            QR_DIGITAL_PRICELIST_VERSION,
            true
        );

        wp_localize_script(
            'qr-digital-pricelist-admin',
            'qrDigitalPricelistAdmin',
            [
                'nonce' => wp_create_nonce('qr_digital_pricelist_admin_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
            ]
        );

        wp_enqueue_style(
            'qr-digital-pricelist-admin',
            QR_DIGITAL_PRICELIST_PLUGIN_URL . 'assets/admin.css',
            [],
            QR_DIGITAL_PRICELIST_VERSION
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'qr-digital-pricelist-frontend',
            QR_DIGITAL_PRICELIST_PLUGIN_URL . 'assets/frontend.css',
            [],
            QR_DIGITAL_PRICELIST_VERSION
        );

        wp_enqueue_script(
            'qr-digital-pricelist-frontend',
            QR_DIGITAL_PRICELIST_PLUGIN_URL . 'assets/frontend.js',
            [],
            QR_DIGITAL_PRICELIST_VERSION,
            true
        );

        $inline_css = '';

        if (function_exists('qr_digital_pricelist_get_background_url')) {
            $background_url = trim(qr_digital_pricelist_get_background_url());
            if (!empty($background_url)) {
                $inline_css .= sprintf(
                    'body.qr-pricelist-fullscreen { --qr-pricelist-wallpaper: url("%s"); }',
                    esc_url($background_url)
                );
            }
        }

        if (function_exists('qr_digital_pricelist_get_font_url')) {
            $font_url = trim(qr_digital_pricelist_get_font_url());
            if (!empty($font_url)) {
                $ext = strtolower(pathinfo(parse_url($font_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $format = 'truetype';
                if ($ext === 'woff2') {
                    $format = 'woff2';
                } elseif ($ext === 'woff') {
                    $format = 'woff';
                }

                $inline_css .= sprintf(
                    "@font-face { font-family: 'QRCustomFont'; src: url('%s') format('%s'); font-display: swap; } :root { --qr-font-stack: 'QRCustomFont', 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }",
                    esc_url($font_url),
                    esc_attr($format)
                );
            }
        }

        if (!empty($inline_css)) {
            wp_add_inline_style('qr-digital-pricelist-frontend', $inline_css);
        }
    }
    
    /**
     * Register Gutenberg block
     */
    public function register_gutenberg_block() {
        register_block_type('qr-digital-pricelist/pricelist', [
            'editor_script' => 'qr-digital-pricelist-block-editor',
            'editor_style'  => 'qr-digital-pricelist-block-editor',
            'style'         => 'qr-digital-pricelist-block',
            'render_callback' => ['QR_Digital_Pricelist_Shortcode', 'render'],
            'attributes' => [
                'category' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'showDisabled' => [
                    'type' => 'boolean',
                    'default' => false
                ]
            ]
        ]);
        
        // Register block assets
        wp_register_script(
            'qr-digital-pricelist-block-editor',
            QR_DIGITAL_PRICELIST_PLUGIN_URL . 'assets/block-editor.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor'],
            QR_DIGITAL_PRICELIST_VERSION
        );
        
        wp_register_style(
            'qr-digital-pricelist-block-editor',
            QR_DIGITAL_PRICELIST_PLUGIN_URL . 'assets/block-editor.css',
            [],
            QR_DIGITAL_PRICELIST_VERSION
        );
        
        wp_register_style(
            'qr-digital-pricelist-block',
            QR_DIGITAL_PRICELIST_PLUGIN_URL . 'assets/block.css',
            [],
            QR_DIGITAL_PRICELIST_VERSION
        );
    }
}

// Initialize plugin
new QR_Digital_Pricelist();
