<?php
/**
 * WooCommerce Load More Products
 * Replaces default pagination with AJAX load more functionality
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mont_WooCommerce_Load_More {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Remove default pagination
        remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
        
        // Add our load more button
        add_action('woocommerce_after_shop_loop', array($this, 'load_more_button'), 10);
        
        // Register AJAX handlers
        add_action('wp_ajax_mont_load_more_products', array($this, 'ajax_load_products'));
        add_action('wp_ajax_nopriv_mont_load_more_products', array($this, 'ajax_load_products'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue required scripts and styles
     */
    public function enqueue_scripts() {
        if (is_shop() || is_product_category() || is_product_tag()) {
            wp_enqueue_script(
                'mont-load-more',
                get_template_directory_uri() . '/assets/load-more.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('mont-load-more', 'mont_load_more_params', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mont_load_more_nonce'),
                'current_page' => max(1, get_query_var('paged')),
                'max_page' => $GLOBALS['wp_query']->max_num_pages,
                'loading_text' => __('Loading products...', 'mont'),
                'no_more_text' => __('No more products to load', 'mont')
            ));
            
            // Add inline CSS
            wp_add_inline_style('woocommerce-inline', $this->get_styles());
        }
    }
    
    /**
     * Get CSS styles for load more button and loading animation
     */
    private function get_styles() {
        return '
            .mont-load-more-container {
                text-align: center;
                margin: 30px 0;
            }
            
            .mont-load-more-button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #f5f5f5;
                color: #333;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .mont-load-more-button:hover {
                background-color: #e9e9e9;
            }
            
            .mont-load-more-button.loading {
                position: relative;
                color: transparent;
                pointer-events: none;
            }
            
            .mont-load-more-button.loading:after {
                content: "";
                position: absolute;
                top: 50%;
                left: 50%;
                width: 20px;
                height: 20px;
                margin: -10px 0 0 -10px;
                border: 2px solid rgba(0,0,0,0.1);
                border-top-color: #333;
                border-radius: 50%;
                animation: mont-spinner 0.6s linear infinite;
            }
            
            @keyframes mont-spinner {
                to {transform: rotate(360deg);}
            }
            
            .mont-no-more-products {
                display: block;
                text-align: center;
                padding: 15px;
                color: #777;
                font-style: italic;
            }
            
            .mont-products-loading {
                opacity: 0.6;
                pointer-events: none;
            }
        ';
    }
    
    /**
     * Display the load more button
     */
    public function load_more_button() {
        global $wp_query;
        
        // Only show button if there are more pages
        if ($wp_query->max_num_pages > 1) {
            echo '<div class="mont-load-more-container" style="background:transparent;">';
            echo '<button class="mont-load-more-button" data-page="1" style="background: transparent; border: none;">' . __('Load More Products', 'mont') . '</button>';
            echo '</div>';
        }
    }
    
    /**
     * AJAX handler to load more products
     */
    public function ajax_load_products() {
        // Security check
        check_ajax_referer('mont_load_more_nonce', 'nonce');
        
        // Get parameters
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $cat_id = isset($_POST['cat_id']) ? absint($_POST['cat_id']) : 0;
        
        // Build query
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page'),
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Add category if specified
        if ($cat_id > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $cat_id
                )
            );
        }
        
        // Run query
        $products = new WP_Query($args);
        
        ob_start();
        
        if ($products->have_posts()) {
            while ($products->have_posts()) {
                $products->the_post();
                wc_get_template_part('content', 'product');
            }
        }
        
        $html = ob_get_clean();
        
        wp_reset_postdata();
        
        // Send response
        wp_send_json(array(
            'html' => $html,
            'max_page' => $products->max_num_pages,
            'found_posts' => $products->found_posts
        ));
    }
}

// Initialize the class
new Mont_WooCommerce_Load_More();