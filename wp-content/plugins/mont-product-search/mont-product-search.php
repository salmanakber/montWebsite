<?php
/**
 * Plugin Name: Mont Product Search
 * Description: Custom WooCommerce product search with autosuggestions
 * Version: 1.0.0
 * Author: v0
 * Text Domain: mont-product-search
 * Requires WooCommerce: 3.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MONT_SEARCH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MONT_SEARCH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MONT_SEARCH_VERSION', '1.0.0');

/**
 * Check if WooCommerce is active
 */
function mont_search_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 __('Mont Product Search requires WooCommerce to be installed and active.', 'mont-product-search') . 
                 '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Register scripts and styles
 */
function mont_search_enqueue_scripts() {
    wp_enqueue_style(
        'mont-product-search-styles',
        MONT_SEARCH_PLUGIN_URL . 'assets/css/mont-search.css',
        array(),
        MONT_SEARCH_VERSION
    );

    wp_enqueue_script(
        'mont-product-search-script',
        MONT_SEARCH_PLUGIN_URL . 'assets/js/mont-search.js',
        array('jquery'),
        MONT_SEARCH_VERSION,
        true
    );

    wp_localize_script(
        'mont-product-search-script',
        'montSearch',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mont_search_nonce'),
            'no_results' => __('No products found', 'mont-product-search')
        )
    );
}

/**
 * AJAX handler for product search
 */
function mont_search_products_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mont_search_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    
    if (strlen($query) < 2) {
        wp_send_json_success(array('products' => array()));
        return;
    }
    
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        's'              => $query,
    );
    
    $products_query = new WP_Query($args);
    $products = array();
    
    if ($products_query->have_posts()) {
        while ($products_query->have_posts()) {
            $products_query->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            
            if (!$product) {
                continue;
            }
            
            $image = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'thumbnail');
            $image_url = $image ? $image[0] : wc_placeholder_img_src('thumbnail');
            
            $products[] = array(
                'id'        => $product_id,
                'title'     => get_the_title(),
                'permalink' => get_permalink(),
                'price'     => $product->get_price_html(),
                'image'     => $image_url,
            );
        }
    }
    
    wp_reset_postdata();
    
    wp_send_json_success(array('products' => $products));
}

/**
 * Register shortcode
 */
function mont_search_shortcode($atts) {
    // Check if WooCommerce is active
    if (!mont_search_check_woocommerce()) {
        return '<p>' . __('WooCommerce is required for this feature.', 'mont-product-search') . '</p>';
    }
    
    $atts = shortcode_atts(array(
        'placeholder' => __('Search products...', 'mont-product-search'),
        'button_text' => __('Search', 'mont-product-search'),
        'show_button' => 'yes',
    ), $atts, 'mont_search');
    
    $show_button = $atts['show_button'] === 'yes';
    
    ob_start();
    ?>
    <div class="mont-search-container">
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="hidden" name="post_type" value="product" />
            <input 
                type="text" 
                class="mont-search-input" 
                placeholder="<?php echo esc_attr($atts['placeholder']); ?>" 
                value="<?php echo get_search_query(); ?>" 
                name="s" 
                autocomplete="off"
            />
            <?php if ($show_button): ?>
                <button type="submit" class="mont-search-button">
                    <?php echo esc_html($atts['button_text']); ?>
                </button>
            <?php endif; ?>
        </form>
        <div class="mont-search-results"></div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Create plugin directories and files on activation
 */
function mont_search_activate() {
    // Create necessary directories
    $dirs = array(
        MONT_SEARCH_PLUGIN_DIR . 'assets',
        MONT_SEARCH_PLUGIN_DIR . 'assets/css',
        MONT_SEARCH_PLUGIN_DIR . 'assets/js',
    );

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // Create CSS file
    $css_content = <<<CSS
.mont-search-container {
    position: relative;
    margin: 0 auto;
}

.mont-search-input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    box-sizing: border-box;
}

.mont-search-input:focus {
    outline: none;
    border-color: #aaa;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}

.mont-search-button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    padding: 0 15px;
    background: #f7f7f7;
    border: 1px solid #ddd;
    border-left: none;
    border-radius: 0 4px 4px 0;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
}

.mont-search-button:hover {
    background: #eee;
}

.mont-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 400px;
    overflow-y: auto;
    display: none;
}

.mont-search-results.mont-search-active {
    display: block;
}

.mont-search-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
    text-decoration: none;
    color: #333;
    transition: background-color 0.2s;
}

.mont-search-item:last-child {
    border-bottom: none;
}

.mont-search-item:hover {
    background-color: #f9f9f9;
}

.mont-search-item-image {
    width: 50px;
    height: 50px;
    margin-right: 15px;
    object-fit: cover;
}

.mont-search-item-content {
    flex: 1;
}

.mont-search-item-title {
    font-weight: bold;
    margin-bottom: 5px;
}

.mont-search-item-price {
    color: #777;
}

.mont-search-no-results {
    padding: 15px;
    text-align: center;
    color: #777;
}

.mont-search-loading {
    text-align: center;
    padding: 15px;
    color: #777;
}

@media (max-width: 480px) {
    .mont-search-item-image {
        width: 40px;
        height: 40px;
        margin-right: 10px;
    }
    
    .mont-search-item {
        padding: 8px;
    }
    
    .mont-search-button {
        padding: 0 10px;
    }
}
CSS;

    file_put_contents(MONT_SEARCH_PLUGIN_DIR . 'assets/css/mont-search.css', $css_content);

    // Create JS file
    $js_content = <<<JS
(function($) {
    'use strict';
    
    $(document).ready(function() {
        const searchInput = $('.mont-search-input');
        const resultsContainer = $('.mont-search-results');
        let searchTimeout = null;
        
        // Handle input changes
        searchInput.on('input', function() {
            const query = $(this).val();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Hide results if query is empty
            if (query.length < 2) {
                resultsContainer.removeClass('mont-search-active');
                return;
            }
            
            // Show loading indicator
            resultsContainer.html('<div class="mont-search-loading">Searching...</div>');
            resultsContainer.addClass('mont-search-active');
            
            // Set timeout to prevent too many requests
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: montSearch.ajax_url,
                    type: 'post',
                    data: {
                        action: 'mont_search_products',
                        nonce: montSearch.nonce,
                        query: query
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.products.length > 0) {
                                let html = '';
                                
                                response.data.products.forEach(function(product) {
                                    html += '<a href="' + product.permalink + '" class="mont-search-item">';
                                    html += '<img src="' + product.image + '" class="mont-search-item-image" alt="' + product.title + '">';
                                    html += '<div class="mont-search-item-content">';
                                    html += '<div class="mont-search-item-title">' + product.title + '</div>';
                                    html += '<div class="mont-search-item-price">' + product.price + '</div>';
                                    html += '</div>';
                                    html += '</a>';
                                });
                                
                                resultsContainer.html(html);
                            } else {
                                resultsContainer.html('<div class="mont-search-no-results">' + montSearch.no_results + '</div>');
                            }
                        } else {
                            resultsContainer.html('<div class="mont-search-no-results">Error loading results</div>');
                        }
                    },
                    error: function() {
                        resultsContainer.html('<div class="mont-search-no-results">Error loading results</div>');
                    }
                });
            }, 300);
        });
        
        // Close results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mont-search-container').length) {
                resultsContainer.removeClass('mont-search-active');
            }
        });
        
        // Prevent form submission on enter
        searchInput.closest('form').on('submit', function(e) {
            const query = searchInput.val();
            if (query.length < 2) {
                e.preventDefault();
            }
        });
    });
})(jQuery);
JS;

    file_put_contents(MONT_SEARCH_PLUGIN_DIR . 'assets/js/mont-search.js', $js_content);
}

/**
 * Plugin deactivation
 */
function mont_search_deactivate() {
    // Clean up if needed
}

/**
 * Initialize the plugin
 */
function mont_search_init() {
    // Check if WooCommerce is active
    if (!mont_search_check_woocommerce()) {
        return;
    }
    
    // Register scripts and styles
    add_action('wp_enqueue_scripts', 'mont_search_enqueue_scripts');
    
    // Register AJAX handlers
    add_action('wp_ajax_mont_search_products', 'mont_search_products_ajax');
    add_action('wp_ajax_nopriv_mont_search_products', 'mont_search_products_ajax');
    
    // Register shortcode
    add_shortcode('mont_search', 'mont_search_shortcode');
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'mont_search_activate');
register_deactivation_hook(__FILE__, 'mont_search_deactivate');

// Initialize the plugin
add_action('plugins_loaded', 'mont_search_init');

