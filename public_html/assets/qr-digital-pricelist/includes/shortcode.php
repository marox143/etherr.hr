<?php
/**
 * Shortcode and frontend rendering
 *
 * @package QR_Digital_Pricelist
 * @copyright Copyright (c) 2026 Etherr
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode class
 */
class QR_Digital_Pricelist_Shortcode {
    
    /**
     * Render shortcode
     */
    public static function render($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'show_disabled' => 0,
        ], $atts, 'qr_digital_pricelist');
        
        $category_slug = sanitize_text_field($atts['category']);
        $show_disabled = (bool) $atts['show_disabled'];
        
        ob_start();
        
        // Get categories
        $categories = self::get_categories($category_slug, $show_disabled);
        
        if (empty($categories)) {
            echo '<p class="qr-digital-pricelist-empty">' . esc_html__('No menu items found.', 'qr-digital-pricelist') . '</p>';
            return ob_get_clean();
        }
        
        $venue_name = qr_digital_pricelist_get_venue_name();
        $logo_url   = qr_digital_pricelist_get_logo_url();
        $info_text  = trim(qr_digital_pricelist_get_info_text());
        $allowed_info_tags = function_exists('qr_digital_pricelist_get_info_allowed_tags') ? qr_digital_pricelist_get_info_allowed_tags() : [];
        $info_text  = wp_kses($info_text, $allowed_info_tags);
        $wallpaper  = qr_digital_pricelist_get_background_url();
        $wallpaper  = $wallpaper !== '' ? $wallpaper : trailingslashit(QR_DIGITAL_PRICELIST_PLUGIN_URL) . 'assets/Images/background.png';
        $info_id    = uniqid('qr-info-');

        $shell_style = ' style="--qr-pricelist-wallpaper: url(' . esc_url($wallpaper) . ');"';
        echo '<div class="qr-digital-pricelist-shell"' . $shell_style . '>';
        echo '<div class="qr-digital-pricelist-wallpaper" aria-hidden="true"></div>';

        // Dynamically render all float assets (2 per file, max 20 total)
        $float_dir = trailingslashit(QR_DIGITAL_PRICELIST_PLUGIN_DIR) . 'assets/Float/';
        $float_url_base = trailingslashit(QR_DIGITAL_PRICELIST_PLUGIN_URL) . 'assets/Float/';
        $allowed_exts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
        $float_files = [];

        if (is_dir($float_dir)) {
            $files = scandir($float_dir);
            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed_exts, true)) {
                        continue;
                    }
                    $float_files[] = $file;
                }
            }
        }

        if (!empty($float_files)) {
            shuffle($float_files);
            $max_total = 40;
            $rendered = 0;
            foreach ($float_files as $file) {
                if ($rendered >= $max_total) {
                    break;
                }
                $remaining = $max_total - $rendered;
                $count_for_file = min(2, $remaining);
                $icon_src = $float_url_base . $file;
                for ($i = 0; $i < $count_for_file; $i++) {
                    echo '<div class="qr-digital-pricelist-floating-logo" aria-hidden="true">';
                    echo '<img src="' . esc_url($icon_src) . '" alt="" loading="lazy">';
                    echo '</div>';
                    $rendered++;
                    if ($rendered >= $max_total) {
                        break 2;
                    }
                }
            }
        }

        echo '<header class="qr-digital-pricelist-header">';
        echo '<p class="qr-digital-pricelist-label">' . esc_html__('Cjenik', 'qr-digital-pricelist') . '</p>';

        echo '<div class="qr-digital-pricelist-logo-stage">';
        if (!empty($logo_url)) {
            $logo_alt = !empty($venue_name) ? $venue_name : get_bloginfo('name');
            echo '<img class="qr-digital-pricelist-logo" src="' . esc_url($logo_url) . '" alt="' . esc_attr($logo_alt) . '">';
        } elseif (!empty($venue_name)) {
            echo '<h1 class="qr-digital-pricelist-title">' . esc_html($venue_name) . '</h1>';
        }

        if ($info_text !== '') {
            $info_body = nl2br($info_text);
            $icon_url = trailingslashit(QR_DIGITAL_PRICELIST_PLUGIN_URL) . 'assets/icons/icon-info.svg';
            echo '<button class="qr-digital-pricelist-info-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="' . esc_attr($info_id) . '">';
            echo '<span class="screen-reader-text">' . esc_html__('More information', 'qr-digital-pricelist') . '</span>';
            echo '<img src="' . esc_url($icon_url) . '" alt="" aria-hidden="true" class="qr-digital-pricelist-info-icon">';
            echo '</button>';
            echo '<div id="' . esc_attr($info_id) . '" class="qr-digital-pricelist-info-modal" role="dialog" aria-modal="true" hidden>';
            echo '<div class="qr-digital-pricelist-info-backdrop" data-dismiss="info"></div>';
            echo '<div class="qr-digital-pricelist-info-content">';
            echo '<button class="qr-digital-pricelist-info-close" type="button" aria-label="' . esc_attr__('Close information', 'qr-digital-pricelist') . '">&times;</button>';
            echo '<div class="qr-digital-pricelist-info-text">' . $info_body . '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>'; // logo-stage

        echo '</header>';
        
        echo '<div class="qr-digital-pricelist">';
        
        foreach ($categories as $category) {
            self::render_category($category, $show_disabled);
        }
        
        echo '</div>';

        echo '</div>'; // shell
        
        return ob_get_clean();
    }
    
    /**
     * Get categories
     */
    private static function get_categories($category_slug = '', $show_disabled = false) {
        if (!empty($category_slug)) {
            $term = get_term_by('slug', $category_slug, 'qr_menu_category');
            if (!$term || is_wp_error($term)) {
                return [];
            }
            $category = self::build_category_data($term, $show_disabled);
            return $category ? [$category] : [];
        }

        $args = [
            'taxonomy' => 'qr_menu_category',
            'hide_empty' => false,
            'parent' => 0,
            'orderby' => 'meta_value_num',
            'meta_key' => 'qr_sort_order',
            'order' => 'ASC',
        ];

        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            return [];
        }

        $categories = [];

        foreach ($terms as $term) {
            $category = self::build_category_data($term, $show_disabled);
            if ($category) {
                $categories[] = $category;
            }
        }

        usort($categories, function($a, $b) {
            if ($a['sort_order'] !== $b['sort_order']) {
                return $a['sort_order'] - $b['sort_order'];
            }
            return strcmp($a['term']->name, $b['term']->name);
        });

        return $categories;
    }

    /**
     * Build category data array
     */
    private static function build_category_data($term, $show_disabled = false, $include_children = true) {
        $enabled = get_term_meta($term->term_id, 'qr_enabled', true);
        $enabled = $enabled !== '' ? (bool) $enabled : true;

        if (!$show_disabled && !$enabled) {
            return null;
        }

        $category = [
            'term' => $term,
            'enabled' => $enabled,
            'sort_order' => (int) get_term_meta($term->term_id, 'qr_sort_order', true),
            'subsections_enabled' => (bool) get_term_meta($term->term_id, 'qr_enable_subsections', true),
            'children' => [],
        ];

        if ($include_children && $category['subsections_enabled']) {
            $category['children'] = self::get_child_categories($term->term_id, $show_disabled);
        }

        return $category;
    }

    /**
     * Fetch child categories for subsections
     */
    private static function get_child_categories($parent_id, $show_disabled = false) {
        $args = [
            'taxonomy' => 'qr_menu_category',
            'hide_empty' => true,
            'parent' => $parent_id,
            'orderby' => 'meta_value_num',
            'meta_key' => 'qr_sort_order',
            'order' => 'ASC',
        ];

        $terms = get_terms($args);
        if (is_wp_error($terms)) {
            return [];
        }

        $children = [];
        foreach ($terms as $term) {
            $child = self::build_category_data($term, $show_disabled, false);
            if ($child) {
                $children[] = $child;
            }
        }

        usort($children, function($a, $b) {
            if ($a['sort_order'] !== $b['sort_order']) {
                return $a['sort_order'] - $b['sort_order'];
            }
            return strcmp($a['term']->name, $b['term']->name);
        });

        return $children;
    }
    
    /**
     * Render category
     */
    private static function render_category($category, $show_disabled = false) {
        $items = self::get_items($category['term']->term_id, $show_disabled);
        $child_categories = $category['subsections_enabled'] ? array_filter($category['children']) : [];
        
        if (empty($items) && empty($child_categories)) {
            return;
        }
        
        $content_id = 'qr-pricelist-category-' . (int) $category['term']->term_id;
        $icon_url = esc_url(trailingslashit(QR_DIGITAL_PRICELIST_PLUGIN_URL) . 'assets/icons/' . sanitize_title($category['term']->slug) . '.svg');

        $indicator_down = trailingslashit(QR_DIGITAL_PRICELIST_PLUGIN_URL) . 'assets/icons/icon-arrow-down.svg';
        $indicator_up   = trailingslashit(QR_DIGITAL_PRICELIST_PLUGIN_URL) . 'assets/icons/icon-arrow-up.svg';

        $indicator_down_path = QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'assets/icons/icon-arrow-down.svg';
        $indicator_up_path   = QR_DIGITAL_PRICELIST_PLUGIN_DIR . 'assets/icons/icon-arrow-up.svg';

        if (file_exists($indicator_down_path)) {
            $indicator_down = add_query_arg('v', filemtime($indicator_down_path), $indicator_down);
        }
        if (file_exists($indicator_up_path)) {
            $indicator_up = add_query_arg('v', filemtime($indicator_up_path), $indicator_up);
        }

        $indicator_down = esc_url($indicator_down);
        $indicator_up   = esc_url($indicator_up);
        echo '<section class="qr-digital-pricelist-category is-collapsed" data-category="' . esc_attr($category['term']->slug) . '">';

        echo '<button class="qr-digital-pricelist-category-toggle" type="button" aria-expanded="false" aria-controls="' . esc_attr($content_id) . '">';
        echo '<div class="qr-digital-pricelist-category-head">';
        echo '<img class="qr-digital-pricelist-category-icon" src="' . $icon_url . '" alt="' . esc_attr($category['term']->name) . ' icon">';
        echo '<h2 class="qr-digital-pricelist-category-title">' . esc_html($category['term']->name) . '</h2>';
        echo '<span class="qr-digital-pricelist-category-indicator" aria-hidden="true">';
        echo '<img class="qr-digital-pricelist-category-indicator-icon" src="' . $indicator_down . '" data-icon-down="' . $indicator_down . '" data-icon-up="' . $indicator_up . '" alt="" role="presentation">';
        echo '</span>';
        echo '</div>';
        echo '</button>';
        
        echo '<div id="' . esc_attr($content_id) . '" class="qr-digital-pricelist-items collapsed">'; 
        
        if (!empty($category['term']->description)) {
            echo '<p class="qr-digital-pricelist-category-description">' . esc_html($category['term']->description) . '</p>';
        }

        if (!empty($child_categories)) {
            echo '<div class="qr-digital-pricelist-subsections">';
            foreach ($child_categories as $child_category) {
                self::render_subsection($child_category, $show_disabled);
            }
            echo '</div>';
        }
        
        foreach ($items as $item) {
            self::render_item($item);
        }
        
        echo '</div>';
        echo '</section>';
    }

    /**
     * Render subsection (child category)
     */
    private static function render_subsection($category, $show_disabled = false) {
        $items = self::get_items($category['term']->term_id, $show_disabled);
        if (empty($items)) {
            return;
        }

        echo '<div class="qr-digital-pricelist-subsection">';
        echo '<h3 class="qr-digital-pricelist-subsection-title">' . esc_html($category['term']->name) . '</h3>';

        foreach ($items as $item) {
            self::render_item($item);
        }

        echo '</div>';
    }
    
    /**
     * Get items for category
     */
    private static function get_items($category_id, $show_disabled = false) {
        $args = [
            'post_type' => 'qr_menu_item',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'qr_menu_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                    'include_children' => false,
                ],
            ],
            'meta_query' => [
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
                
                if (!$show_disabled && !$enabled) {
                    continue;
                }
                
                $variants = get_post_meta($post_id, 'qr_variants', true);
                $variants = is_array($variants) ? $variants : [];
                
                // Filter enabled variants
                if (!$show_disabled) {
                    $variants = array_filter($variants, function($variant) {
                        return isset($variant['enabled']) && $variant['enabled'];
                    });
                }
                
                // Sort variants by sort_order then volume
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
                    'post' => get_post(),
                    'enabled' => $enabled,
                    'sort_order' => (int) get_post_meta($post_id, 'qr_sort_order', true),
                    'variants' => $variants,
                ];
            }
        }
        
        wp_reset_postdata();
        
        // Sort items by sort_order then title
        usort($items, function($a, $b) {
            if ($a['sort_order'] !== $b['sort_order']) {
                return $a['sort_order'] - $b['sort_order'];
            }
            return strcmp($a['post']->post_title, $b['post']->post_title);
        });
        
        return $items;
    }
    
    /**
     * Render item
     */
    private static function render_item($item) {
        if (empty($item['variants'])) {
            return;
        }
        
        echo '<article class="qr-digital-pricelist-item" data-item-id="' . esc_attr($item['post']->ID) . '">';

        echo '<div class="qr-digital-pricelist-variants">';

        foreach ($item['variants'] as $index => $variant) {
            self::render_variant($variant, $item['post'], $index === 0);
        }

        echo '</div>';

        if (!empty($item['post']->post_excerpt)) {
            echo '<p class="qr-digital-pricelist-item-description">' . esc_html($item['post']->post_excerpt) . '</p>';
        }

        echo '</article>';
    }
    
    /**
     * Render variant
     */
    private static function render_variant($variant, $post, $show_name = true) {
        $volume_value = isset($variant['volume_value']) ? esc_html($variant['volume_value']) : '';
        $unit_slug = isset($variant['unit_slug']) ? esc_html($variant['unit_slug']) : '';
        $unit_label = qr_digital_pricelist_get_unit_label($unit_slug);
        $price = isset($variant['price']) ? floatval($variant['price']) : 0;
        $currency_symbol = qr_digital_pricelist_get_currency_symbol();
        $item_title = $post instanceof WP_Post ? $post->post_title : '';

        echo '<div class="qr-digital-pricelist-variant-row">';

        echo '<span class="qr-digital-pricelist-item-name">';
        if ($show_name && !empty($item_title)) {
            echo esc_html($item_title);
        } else {
            echo '&#160;';
        }
        echo '</span>';

        echo '<span class="qr-digital-pricelist-volume">';
        if (!empty($volume_value) && !empty($unit_label)) {
            echo esc_html(trim($volume_value . ' ' . $unit_label));
        }
        echo '</span>';

        echo '<span class="qr-digital-pricelist-price">' . esc_html($currency_symbol) . number_format($price, 2) . '</span>';

        echo '</div>';
    }
}
