<?php
/**
 * Helper functions
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all enabled categories
 */
function qr_digital_pricelist_get_enabled_categories() {
    $args = [
        'taxonomy' => 'qr_menu_category',
        'hide_empty' => true,
        'meta_query' => [
            [
                'key' => 'qr_enabled',
                'value' => '1',
                'compare' => '=',
            ],
        ],
        'orderby' => [
            'meta_value_num' => 'ASC',
            'name' => 'ASC',
        ],
        'meta_key' => 'qr_sort_order',
    ];
    
    $terms = get_terms($args);
    
    if (is_wp_error($terms)) {
        return [];
    }
    
    return $terms;
}

/**
 * Get all enabled items in a category
 */
function qr_digital_pricelist_get_enabled_items($category_id) {
    $args = [
        'post_type' => 'qr_menu_item',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'qr_menu_category',
                'field' => 'term_id',
                'terms' => $category_id,
            ],
        ],
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'qr_enabled',
                'value' => '1',
                'compare' => '=',
            ],
            [
                'relation' => 'OR',
                [
                    'key' => 'qr_sort_order',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => 'qr_sort_order',
                    'value' => 0,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ],
            ],
        ],
        'orderby' => [
            'meta_value_num' => 'ASC',
            'title' => 'ASC',
        ],
        'meta_key' => 'qr_sort_order',
    ];
    
    return get_posts($args);
}

/**
 * Get enabled variants for an item
 */
function qr_digital_pricelist_get_enabled_variants($item_id) {
    $variants = get_post_meta($item_id, 'qr_variants', true);
    $variants = is_array($variants) ? $variants : [];
    
    $enabled_variants = array_filter($variants, function($variant) {
        return isset($variant['enabled']) && $variant['enabled'];
    });
    
    // Sort by sort_order then volume
    usort($enabled_variants, function($a, $b) {
        $sort_a = isset($a['sort_order']) ? (int) $a['sort_order'] : 0;
        $sort_b = isset($b['sort_order']) ? (int) $b['sort_order'] : 0;
        
        if ($sort_a !== $sort_b) {
            return $sort_a - $sort_b;
        }
        
        $vol_a = isset($a['volume_value']) ? floatval($a['volume_value']) : 0;
        $vol_b = isset($b['volume_value']) ? floatval($b['volume_value']) : 0;
        
        return $vol_a - $vol_b;
    });
    
    return array_values($enabled_variants);
}

/**
 * Format price
 */
function qr_digital_pricelist_format_price($price) {
    $currency_symbol = qr_digital_pricelist_get_currency_symbol();
    return $currency_symbol . number_format((float) $price, 2);
}

/**
 * Get item count for category
 */
function qr_digital_pricelist_get_category_item_count($category_id) {
    $args = [
        'post_type' => 'qr_menu_item',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'qr_menu_category',
                'field' => 'term_id',
                'terms' => $category_id,
            ],
        ],
        'meta_query' => [
            [
                'key' => 'qr_enabled',
                'value' => '1',
                'compare' => '=',
            ],
        ],
        'fields' => 'ids',
    ];
    
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Check if category has enabled items
 */
function qr_digital_pricelist_category_has_items($category_id) {
    return qr_digital_pricelist_get_category_item_count($category_id) > 0;
}

/**
 * Get all menu data as array (for API/JSON use)
 */
function qr_digital_pricelist_get_menu_data($category_slug = '', $include_disabled = false) {
    $categories = [];
    
    $args = [
        'taxonomy' => 'qr_menu_category',
        'hide_empty' => true,
        'orderby' => 'meta_value_num',
        'meta_key' => 'qr_sort_order',
        'order' => 'ASC',
    ];
    
    if (!empty($category_slug)) {
        $args['slug'] = $category_slug;
    }
    
    $terms = get_terms($args);
    
    if (is_wp_error($terms)) {
        return [];
    }
    
    foreach ($terms as $term) {
        $enabled = get_term_meta($term->term_id, 'qr_enabled', true);
        $enabled = $enabled !== '' ? (bool) $enabled : true;
        
        if (!$include_disabled && !$enabled) {
            continue;
        }
        
        $items = qr_digital_pricelist_get_category_items_data($term->term_id, $include_disabled);
        
        if (empty($items)) {
            continue;
        }
        
        $categories[] = [
            'id' => $term->term_id,
            'slug' => $term->slug,
            'name' => $term->name,
            'description' => $term->description,
            'enabled' => $enabled,
            'sort_order' => (int) get_term_meta($term->term_id, 'qr_sort_order', true),
            'items' => $items,
        ];
    }
    
    return $categories;
}

/**
 * Get category items data
 */
function qr_digital_pricelist_get_category_items_data($category_id, $include_disabled = false) {
    $args = [
        'post_type' => 'qr_menu_item',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'qr_menu_category',
                'field' => 'term_id',
                'terms' => $category_id,
            ],
        ],
        'orderby' => [
            'meta_value_num' => 'ASC',
            'title' => 'ASC',
        ],
        'meta_key' => 'qr_sort_order',
    ];
    
    $query = new WP_Query($args);
    $items = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $enabled = get_post_meta($post_id, 'qr_enabled', true);
            $enabled = $enabled !== '' ? (bool) $enabled : true;
            
            if (!$include_disabled && !$enabled) {
                continue;
            }
            
            $variants = get_post_meta($post_id, 'qr_variants', true);
            $variants = is_array($variants) ? $variants : [];
            
            if (!$include_disabled) {
                $variants = array_filter($variants, function($variant) {
                    return isset($variant['enabled']) && $variant['enabled'];
                });
            }
            
            // Sort variants
            usort($variants, function($a, $b) {
                $sort_a = isset($a['sort_order']) ? (int) $a['sort_order'] : 0;
                $sort_b = isset($b['sort_order']) ? (int) $b['sort_order'] : 0;
                
                if ($sort_a !== $sort_b) {
                    return $sort_a - $sort_b;
                }
                
                $vol_a = isset($a['volume_value']) ? floatval($a['volume_value']) : 0;
                $vol_b = isset($b['volume_value']) ? floatval($b['volume_value']) : 0;
                
                return $vol_a - $vol_b;
            });
            
            $items[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'description' => get_the_excerpt(),
                'enabled' => $enabled,
                'sort_order' => (int) get_post_meta($post_id, 'qr_sort_order', true),
                'variants' => array_values($variants),
            ];
        }
    }
    
    wp_reset_postdata();
    
    return $items;
}

/**
 * Debug function to display menu structure
 */
function qr_digital_pricelist_debug_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $menu_data = qr_digital_pricelist_get_menu_data('', true);
    
    echo '<pre>';
    print_r($menu_data);
    echo '</pre>';
}

/**
 * Check if plugin is properly configured
 */
function qr_digital_pricelist_is_configured() {
    $categories = get_terms([
        'taxonomy' => 'qr_menu_category',
        'hide_empty' => false,
        'number' => 1,
    ]);
    
    if (is_wp_error($categories) || empty($categories)) {
        return false;
    }
    
    $items = get_posts([
        'post_type' => 'qr_menu_item',
        'post_status' => 'publish',
        'numberposts' => 1,
    ]);
    
    return !empty($items);
}
