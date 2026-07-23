<?php
/**
 * Product Edit Template
 *
 * @package    DC_Product_Manager
 * @subpackage DC_Product_Manager/public/templates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the product data from the global scope
global $product_data;

// Add body class for CRM page
add_filter('body_class', function($classes) {
    $classes[] = 'dc-crm-page';
    $classes[] = 'dc-product-edit-page';
    return $classes;
});

// Enqueue CRM styles
wp_enqueue_style('dc-crm-styles', DC_PM_PLUGIN_URL . 'public/css/dc-crm.css', array(), DC_PM_VERSION);
wp_enqueue_style('dc-product-management', DC_PM_PLUGIN_URL . 'assets/css/product-management.css', array(), DC_PM_VERSION);
wp_enqueue_style('dc-region-switcher', DC_PM_PLUGIN_URL . 'assets/css/region-switcher.css', array(), DC_PM_VERSION);

// Enqueue scripts
wp_enqueue_script('dc-product-management', DC_PM_PLUGIN_URL . 'assets/js/product-management.js', array('jquery'), DC_PM_VERSION, true);

// Localize script
wp_localize_script('dc-product-management', 'dc_product_manager', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'adminUrl' => admin_url(),
    'siteUrl' => home_url('/'),
    'nonce' => wp_create_nonce('dc-product-management-nonce'),
    'i18n' => array(
        'loading' => __('Loading...', 'dc-product-manager'),
        'saving' => __('Saving...', 'dc-product-manager'),
        'saved' => __('Saved!', 'dc-product-manager'),
        'error' => __('Error occurred', 'dc-product-manager'),
        'success' => __('Success', 'dc-product-manager'),
        'update' => __('Update Product', 'dc-product-manager'),
        'noProducts' => __('No products found', 'dc-product-manager'),
        'noImage' => __('No image available', 'dc-product-manager'),
        'sku' => __('SKU', 'dc-product-manager'),
        'price' => __('Price', 'dc-product-manager'),
        'stock' => __('Stock', 'dc-product-manager'),
        'edit' => __('Edit', 'dc-product-manager'),
        'lowStock' => __('Low Stock Alert', 'dc-product-manager'),
        'lowStockMessage' => __('Some products have low stock', 'dc-product-manager'),
        'requiredFields' => __('Please fill in all required fields', 'dc-product-manager'),
        'productUpdated' => __('Product updated successfully', 'dc-product-manager'),
        'save' => __('Update Product', 'dc-product-manager'),
        'titlePreview' => __('Title will be generated automatically', 'dc-product-manager')
    )
));

// Add inline script to ensure dc_product_manager is available
wp_add_inline_script('dc-product-management', 'console.log("dc_product_manager loaded:", dc_product_manager);', 'before');

// Get header
get_header();
?>
<style>
    div#sticky-popup-btn {
        display: none !important;
    }
</style>
<div class="dc-crm-wrap">
    <div class="dc-crm-header">
        <h1><?php _e('Edit Product', 'dc-product-manager'); ?></h1>
        <div class="dc-crm-actions">
            <a href="javascript:history.back()" class="button"><?php _e('Back to Products', 'dc-product-manager'); ?></a>
        </div>
    </div>
    
    <div class="dc-crm-content">
        <div class="dc-product-edit-layout">
            <!-- Product Images Section (Left Side) -->
            <div class="dc-product-images-section">
                <h2><?php _e('Product Images', 'dc-product-manager'); ?></h2>
                <div class="dc-product-images">
					<input type="hidden" value="<?php echo get_post_meta($product_id, '_dc_product_image', true); ?>" id="dc-product-image"/>
					 <input type="hidden" value="" id="dc-product-image-id"/>
                    <?php
                    // Get product images
                    $product_id = $product_data['id'];
                    $product = wc_get_product($product_id);
                    
                    if ($product) {
                        // Get featured image
                        $featured_image_id = $product->get_image_id();
	$featured_image_url = get_post_meta($product_id, '_dc_product_image', true);

if ($featured_image_url) {
    echo '<div class="dc-product-featured-image">';
    echo '<h3>' . esc_html__('Featured Image', 'dc-product-manager') . '</h3>';
    echo '<img id="featured-image" src="' . esc_url($featured_image_url) . '" alt="' . esc_attr($product->get_name()) . '">';
    echo '</div>';
}
						else{
                        if ($featured_image_id) {
                            $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'large');
                            echo '<div class="dc-product-featured-image">';
                            echo '<h3>' . __('Featured Image', 'dc-product-manager') . '</h3>';
                            echo '<img id="featured-image" src="' . esc_url($featured_image_url) . '" alt="' . esc_attr($product->get_name()) . '">';
                            echo '</div>';
                        }
						}

                        
                        // Get gallery images
                        $gallery_image_ids = $product->get_gallery_image_ids();
                        if (!empty($gallery_image_ids)) {
                            echo '<div class="dc-product-gallery-images">';
                            echo '<h3>' . __('Gallery Images', 'dc-product-manager') . '</h3>';
                            echo '<div class="dc-product-gallery-grid">';

    // Include the featured image in gallery
                            if ($product->get_image_id()) {
                                echo '<div class="dc-product-gallery-image">';
                                echo '<img class="thumbnail-clickable" src="' . esc_url(wp_get_attachment_image_url($featured_image_id, 'medium')) . '" data-large="' . esc_url(wp_get_attachment_image_url($featured_image_id, 'large')) . '" data-imageId="'.($image_id).'" alt="' . esc_attr($product->get_name()) . '">';
                                echo '</div>';
                            }

    // Other gallery images
                            foreach ($gallery_image_ids as $image_id) {
                                echo '<div class="dc-product-gallery-image">';
                                echo '<img class="thumbnail-clickable" src="' . esc_url(wp_get_attachment_image_url($image_id, 'medium')) . '" data-large="' . esc_url(wp_get_attachment_image_url($image_id, 'large')) . '" data-imageId="'.($image_id).'" alt="' . esc_attr($product->get_name()) . '">';
                                echo '</div>';
                            }

                            echo '</div></div>';
                        }

                    } else {
                        echo '<div class="dc-no-images">' . __('No images available for this product', 'dc-product-manager') . '</div>';
                    }
                    ?>
                </div>
                <div class="dc-product-images-note">
                    <p><?php _e('Note: Images are for display only and cannot be updated from this page.', 'dc-product-manager'); ?></p>
                </div>
            </div>
            
            <!-- Product Edit Form (Right Side) -->
            <div class="dc-product-edit-form">
                <form id="dc-product-edit-form" class="dc-product-form">
                    <input type="hidden" id="dc-product-id" value="<?php echo esc_attr($product_data['id']); ?>">
                    
                    <div class="dc-form-section">
                        <h3>Basic Information</h3>
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-fabric-color" class="required"><?php _e('Fabric Color', 'dc-product-manager'); ?></label>
                                <input type="text" id="dc-product-fabric-color" value="<?php echo esc_attr($product_data['fabric_color']); ?>" required>
                            </div>
                        </div>
				 <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-fabric-color" class="required"><?php _e('Fabric Color (English)', 'dc-product-manager'); ?></label>
                                <input type="text" id="dc-product-fabric-color-english" value="<?php echo esc_attr($product_data['fabric_color_english']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-category" class="required"><?php _e('Category', 'dc-product-manager'); ?></label>
                                <select id="dc-product-category" required>
                                    <?php
                                    $categories = get_terms(array(
                                        'taxonomy' => 'product_cat',
                                        'hide_empty' => false,
                                    ));
                                    
                                    foreach ($categories as $category) {
                                        $selected = ($category->term_id == $product_data['category_id']) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-fabric-no" class="required"><?php _e('Fabric No', 'dc-product-manager'); ?></label>
                                <input type="text" id="dc-product-fabric-no" value="<?php echo esc_attr($product_data['fabric_no']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dc-form-section">
                        <h3>Product Details</h3>
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-title"><?php _e('Product Title', 'dc-product-manager'); ?></label>
                                <div class="dc-title-preview">
                                    <div id="dc-product-title-preview"><?php echo esc_html($product_data['generated_title']); ?></div>
                                    <label>
                                        <input type="checkbox" id="dc-product-custom-title">
                                        <?php _e('Use custom title', 'dc-product-manager'); ?>
                                    </label>
                                </div>
                                <input type="text" id="dc-product-custom-title-input" value="<?php echo esc_attr($product_data['title']); ?>" style="display: none;">
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group dc-form-group--full">
                                <label><?php _e('Prices by Region', 'dc-product-manager'); ?></label>
                                <p class="description"><?php _e('Set a price for each currency. If a currency is empty, the default NOK price is used on the storefront.', 'dc-product-manager'); ?></p>
                                <div class="dc-multicurrency-grid">
                                    <?php
                                    $mc_prices = isset($product_data['multicurrency_prices']) ? $product_data['multicurrency_prices'] : array();
                                    foreach (\DC_Product_Manager\DC_Region_Currency::get_regions() as $slug => $region) :
                                        $code = $region['currency'];
                                        $val = isset($mc_prices[$code]) ? $mc_prices[$code] : '';
                                    ?>
                                    <div class="dc-form-group">
                                        <label for="dc-price-<?php echo esc_attr(strtolower($code)); ?>">
                                            <?php echo esc_html($region['label'] . ' (' . $region['display'] . ')'); ?>
                                        </label>
                                        <input
                                            type="number"
                                            id="dc-price-<?php echo esc_attr(strtolower($code)); ?>"
                                            class="dc-multicurrency-price"
                                            data-currency="<?php echo esc_attr($code); ?>"
                                            value="<?php echo esc_attr($val); ?>"
                                            step="<?php echo $code === 'VND' ? '1' : '0.01'; ?>"
                                            min="0"
                                        >
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" id="dc-product-price" value="<?php echo esc_attr($product_data['price']); ?>">
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-stock"><?php _e('Stock', 'dc-product-manager'); ?></label>
                                <div style="position: relative;">
                                    <input type="number" id="dc-product-stock" value="<?php echo esc_attr($product_data['stock']); ?>">
                                    <?php 
                                    $stock = intval($product_data['stock']);
                                    $stock_class = 'good';
                                    if ($stock <= 0) {
                                        $stock_class = 'out';
                                    } elseif ($stock <= 10) {
                                        $stock_class = 'low';
                                    }
                                    ?>
                                    <span class="stock-status <?php echo esc_attr($stock_class); ?>" title="<?php echo esc_attr(sprintf(__('Current stock: %d', 'dc-product-manager'), $stock)); ?>"></span>
                                </div>
                            </div>
                        </div>
                        
                        
                    </div>

                    <?php
                    $is_b2b = in_array( (string) $product_data['b2b_product'], array( 'yes', '1' ), true );
                    ?>
                    <div class="dc-form-section dc-b2b-channel-section <?php echo $is_b2b ? 'is-b2b-active' : ''; ?>">
                        <div class="dc-b2b-channel-header">
                            <div>
                                <h3><?php _e( 'B2B / Wholesale Channel', 'dc-product-manager' ); ?></h3>
                                <p class="dc-b2b-channel-help">
                                    <?php _e( 'Turn this on if this fabric/product should appear in the Monte B2B wholesale portal (not only the regular shop).', 'dc-product-manager' ); ?>
                                </p>
                            </div>
                            <span class="dc-b2b-badge <?php echo $is_b2b ? 'dc-b2b-badge--on' : 'dc-b2b-badge--off'; ?>">
                                <?php echo $is_b2b ? esc_html__( 'B2B', 'dc-product-manager' ) : esc_html__( 'B2C only', 'dc-product-manager' ); ?>
                            </span>
                        </div>

                        <div class="dc-b2b-toggle-row">
                            <label class="dc-b2b-toggle" for="dc-product-b2b-toggle">
                                <input type="checkbox" id="dc-product-b2b-toggle" <?php checked( $is_b2b ); ?>>
                                <span class="dc-b2b-toggle-slider"></span>
                                <span class="dc-b2b-toggle-label">
                                    <?php _e( 'Mark as B2B product', 'dc-product-manager' ); ?>
                                </span>
                            </label>
                            <select id="dc-product-b2b-status" class="dc-b2b-status-select" aria-hidden="true" tabindex="-1">
                                <option value="no" <?php selected( $is_b2b, false ); ?>><?php _e( 'No', 'dc-product-manager' ); ?></option>
                                <option value="yes" <?php selected( $is_b2b, true ); ?>><?php _e( 'Yes', 'dc-product-manager' ); ?></option>
                            </select>
                        </div>

                        <div class="dc-form-row dc-b2b-moq-row">
                            <div class="dc-form-group">
                                <label for="dc-product-moq"><?php _e( 'Minimum Order Quantity (MOQ)', 'dc-product-manager' ); ?></label>
                                <input type="number" id="dc-product-moq" min="1" value="<?php echo esc_attr( $product_data['moq'] ); ?>" placeholder="e.g. 50">
                                <small class="dc-field-hint"><?php _e( 'Wholesale customers must order at least this many shirts for this product.', 'dc-product-manager' ); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dc-supplier-section">
                        <h3>Supplier Information</h3>
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-supplier"><?php _e('Supplier', 'dc-product-manager'); ?></label>
                                <select id="dc-product-supplier">
                                    <option value=""><?php _e('Select Supplier', 'dc-product-manager'); ?></option>
                                    <?php
                                    $suppliers = get_posts(array(
                                        'post_type' => 'dc_supplier',
                                        'posts_per_page' => -1,
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                    ));
                                    
                                    foreach ($suppliers as $supplier) {
                                        $selected = ($supplier->ID == $product_data['supplier_id']) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($supplier->ID) . '" ' . $selected . '>' . esc_html($supplier->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-supplier-sku"><?php _e('Supplier SKU', 'dc-product-manager'); ?></label>
                                <input type="text" id="dc-product-supplier-sku" value="<?php echo esc_attr($product_data['supplier_sku']); ?>">
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-quality"><?php _e('Quality', 'dc-product-manager'); ?></label>
                                <input type="text" id="dc-product-quality" value="<?php echo esc_attr($product_data['quality']); ?>">
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-fabric-width"><?php _e('Fabric Width', 'dc-product-manager'); ?></label>
                                <input type="text" id="dc-product-fabric-width" value="<?php echo esc_attr($product_data['fabric_width']); ?>">
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-weight"><?php _e('Weight', 'dc-product-manager'); ?></label>
                                <input type="text" id="dc-product-weight" value="<?php echo esc_attr($product_data['weight']); ?>">
                            </div>
                        </div>
                        
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-supplier-price"><?php _e('Supplier Price', 'dc-product-manager'); ?></label>
                                <input type="number" id="dc-product-supplier-price" value="<?php echo esc_attr($product_data['supplier_price']); ?>" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="dc-form-actions">
                        <button type="button" id="dc-save-product" class="button button-primary"><?php _e('Update Product', 'dc-product-manager'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const thumbnails = document.querySelectorAll('.thumbnail-clickable');
        const featuredImage = document.getElementById('featured-image');

        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function () {
                const largeImage = this.getAttribute('data-large');
				 const imageid = this.getAttribute('data-imageId');
                if (largeImage) {
                    featuredImage.src = largeImage;
					 document.getElementById('dc-product-image').value = largeImage ;
					 document.getElementById('dc-product-image-id').value = imageid ;
					
                }
            });
        });
    });
</script>


<?php
// Get footer
get_footer(); 