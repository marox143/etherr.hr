<?php
/**
 * Custom Post Types and Taxonomies registration
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom post type for menu items
 */
function qr_digital_pricelist_register_cpt() {
    $labels = [
        'name' => __('Menu Items', 'qr-digital-pricelist'),
        'singular_name' => __('Menu Item', 'qr-digital-pricelist'),
        'menu_name' => __('Menu Items', 'qr-digital-pricelist'),
        'name_admin_bar' => __('Menu Item', 'qr-digital-pricelist'),
        'add_new' => __('Add New', 'qr-digital-pricelist'),
        'add_new_item' => __('Add New Menu Item', 'qr-digital-pricelist'),
        'new_item' => __('New Menu Item', 'qr-digital-pricelist'),
        'edit_item' => __('Edit Menu Item', 'qr-digital-pricelist'),
        'view_item' => __('View Menu Item', 'qr-digital-pricelist'),
        'all_items' => __('All Menu Items', 'qr-digital-pricelist'),
        'search_items' => __('Search Menu Items', 'qr-digital-pricelist'),
        'parent_item_colon' => __('Parent Menu Item:', 'qr-digital-pricelist'),
        'not_found' => __('No menu items found.', 'qr-digital-pricelist'),
        'not_found_in_trash' => __('No menu items found in Trash.', 'qr-digital-pricelist'),
        'featured_image' => __('Item Image', 'qr-digital-pricelist'),
        'set_featured_image' => __('Set item image', 'qr-digital-pricelist'),
        'remove_featured_image' => __('Remove item image', 'qr-digital-pricelist'),
        'use_featured_image' => __('Use as item image', 'qr-digital-pricelist'),
        'archives' => __('Menu Item archives', 'qr-digital-pricelist'),
        'insert_into_item' => __('Insert into menu item', 'qr-digital-pricelist'),
        'uploaded_to_this_item' => __('Uploaded to this menu item', 'qr-digital-pricelist'),
        'filter_items_list' => __('Filter menu items list', 'qr-digital-pricelist'),
        'items_list_navigation' => __('Menu items list navigation', 'qr-digital-pricelist'),
        'items_list' => __('Menu items list', 'qr-digital-pricelist'),
    ];

    $args = [
        'label' => __('Menu Item', 'qr-digital-pricelist'),
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'qr-menu-item'],
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 25,
        'menu_icon' => 'dashicons-food',
        'supports' => ['title'],
        'show_in_rest' => false,
    ];

    register_post_type('qr_menu_item', $args);
}

/**
 * Register custom taxonomy for menu categories
 */
function qr_digital_pricelist_register_taxonomy() {
    $labels = [
        'name' => __('Menu Categories', 'qr-digital-pricelist'),
        'singular_name' => __('Menu Category', 'qr-digital-pricelist'),
        'menu_name' => __('Categories', 'qr-digital-pricelist'),
        'all_items' => __('All Categories', 'qr-digital-pricelist'),
        'edit_item' => __('Edit Category', 'qr-digital-pricelist'),
        'view_item' => __('View Category', 'qr-digital-pricelist'),
        'update_item' => __('Update Category', 'qr-digital-pricelist'),
        'add_new_item' => __('Add New Category', 'qr-digital-pricelist'),
        'new_item_name' => __('New Category Name', 'qr-digital-pricelist'),
        'parent_item' => __('Parent Category', 'qr-digital-pricelist'),
        'parent_item_colon' => __('Parent Category:', 'qr-digital-pricelist'),
        'search_items' => __('Search Categories', 'qr-digital-pricelist'),
        'popular_items' => __('Popular Categories', 'qr-digital-pricelist'),
        'separate_items_with_commas' => __('Separate categories with commas', 'qr-digital-pricelist'),
        'add_or_remove_items' => __('Add or remove categories', 'qr-digital-pricelist'),
        'choose_from_most_used' => __('Choose from the most used categories', 'qr-digital-pricelist'),
        'not_found' => __('No categories found.', 'qr-digital-pricelist'),
        'no_terms' => __('No categories', 'qr-digital-pricelist'),
        'items_list_navigation' => __('Categories list navigation', 'qr-digital-pricelist'),
        'items_list' => __('Categories list', 'qr-digital-pricelist'),
    ];

    $args = [
        'label' => __('Menu Category', 'qr-digital-pricelist'),
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'qr-menu-category'],
        'show_admin_column' => true,
        'show_in_rest' => false,
    ];

    register_taxonomy('qr_menu_category', ['qr_menu_item'], $args);
}

/**
 * Initialize CPT and taxonomy registration
 */
function qr_digital_pricelist_init_cpt_taxonomy() {
    qr_digital_pricelist_register_cpt();
    qr_digital_pricelist_register_taxonomy();
}

add_action('init', 'qr_digital_pricelist_init_cpt_taxonomy');

/**
 * Disable block editor for menu items
 */
function qr_digital_pricelist_disable_block_editor($use_block_editor, $post_type) {
    if ('qr_menu_item' === $post_type) {
        return false;
    }

    return $use_block_editor;
}

add_filter('use_block_editor_for_post_type', 'qr_digital_pricelist_disable_block_editor', 10, 2);
