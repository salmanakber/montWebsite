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

$product_id   = isset( $product_data['id'] ) ? (int) $product_data['id'] : 0;
$is_b2b       = in_array( (string) ( $product_data['b2b_product'] ?? '' ), array( 'yes', '1' ), true );
$stock        = intval( $product_data['stock'] ?? 0 );
$stock_class  = 'good';
$stock_label  = __( 'In stock', 'dc-product-manager' );
if ( $stock <= 0 ) {
	$stock_class = 'out';
	$stock_label = __( 'Out of stock', 'dc-product-manager' );
} elseif ( $stock <= 10 ) {
	$stock_class = 'low';
	$stock_label = __( 'Low stock', 'dc-product-manager' );
}
$display_title = ! empty( $product_data['title'] ) ? $product_data['title'] : ( $product_data['generated_title'] ?? '' );
$supplier_sku  = $product_data['supplier_sku'] ?? '';
?>
<style>
    div#sticky-popup-btn {
        display: none !important;
    }
</style>
<div class="dc-crm-wrap dc-product-edit-shell">
    <div class="dc-crm-header dc-product-edit-header">
        <div class="dc-product-edit-header__left">
            <a href="<?php echo esc_url( add_query_arg( 'tab', 'products', home_url( '/crm/' ) ) ); ?>" class="dc-edit-back">
                <?php _e( '← Products', 'dc-product-manager' ); ?>
            </a>
            <div class="dc-product-edit-heading">
                <p class="dc-product-edit-kicker"><?php _e( 'Stock Management', 'dc-product-manager' ); ?></p>
                <h1><?php echo esc_html( $display_title ? $display_title : __( 'Edit Product', 'dc-product-manager' ) ); ?></h1>
                <div class="dc-product-edit-meta">
                    <?php if ( $supplier_sku ) : ?>
                        <span class="dc-meta-chip">SKU <?php echo esc_html( $supplier_sku ); ?></span>
                    <?php endif; ?>
                    <span class="dc-meta-chip dc-meta-chip--stock dc-meta-chip--<?php echo esc_attr( $stock_class ); ?>">
                        <?php echo esc_html( $stock_label ); ?> · <?php echo esc_html( (string) $stock ); ?>
                    </span>
                    <span class="dc-meta-chip <?php echo $is_b2b ? 'dc-meta-chip--b2b' : 'dc-meta-chip--b2c'; ?>">
                        <?php echo $is_b2b ? esc_html__( 'B2B enabled', 'dc-product-manager' ) : esc_html__( 'B2C only', 'dc-product-manager' ); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="dc-crm-actions">
            <button type="button" class="button button-primary dc-header-save" id="dc-save-product-top"><?php _e( 'Update Product', 'dc-product-manager' ); ?></button>
        </div>
    </div>
    
    <div class="dc-crm-content dc-product-edit-content">
        <div class="dc-product-edit-layout">
            <!-- Product Images Section (Left Side) -->
            <aside class="dc-product-images-section">
                <div class="dc-media-panel-head">
                    <h2><?php _e( 'Media', 'dc-product-manager' ); ?></h2>
                    <span class="dc-media-panel-hint"><?php _e( 'Preview only', 'dc-product-manager' ); ?></span>
                </div>
                <div class="dc-product-images">
					<input type="hidden" value="<?php echo esc_attr( get_post_meta( $product_id, '_dc_product_image', true ) ); ?>" id="dc-product-image"/>
					 <input type="hidden" value="" id="dc-product-image-id"/>
                    <?php
                    $product = wc_get_product( $product_id );
                    
                    if ( $product ) {
                        $featured_image_id  = $product->get_image_id();
                        $featured_image_url = get_post_meta( $product_id, '_dc_product_image', true );

                        if ( $featured_image_url ) {
                            echo '<div class="dc-product-featured-image">';
                            echo '<img id="featured-image" src="' . esc_url( $featured_image_url ) . '" alt="' . esc_attr( $product->get_name() ) . '">';
                            echo '</div>';
                        } elseif ( $featured_image_id ) {
                            $featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'large' );
                            echo '<div class="dc-product-featured-image">';
                            echo '<img id="featured-image" src="' . esc_url( $featured_image_url ) . '" alt="' . esc_attr( $product->get_name() ) . '">';
                            echo '</div>';
                        }

                        $gallery_image_ids = $product->get_gallery_image_ids();
                        if ( ! empty( $gallery_image_ids ) || $featured_image_id ) {
                            echo '<div class="dc-product-gallery-images">';
                            echo '<div class="dc-product-gallery-grid">';

                            if ( $featured_image_id ) {
                                echo '<div class="dc-product-gallery-image is-active">';
                                echo '<img class="thumbnail-clickable" src="' . esc_url( wp_get_attachment_image_url( $featured_image_id, 'medium' ) ) . '" data-large="' . esc_url( wp_get_attachment_image_url( $featured_image_id, 'large' ) ) . '" data-imageId="' . esc_attr( (string) $featured_image_id ) . '" alt="' . esc_attr( $product->get_name() ) . '">';
                                echo '</div>';
                            }

                            foreach ( $gallery_image_ids as $image_id ) {
                                echo '<div class="dc-product-gallery-image">';
                                echo '<img class="thumbnail-clickable" src="' . esc_url( wp_get_attachment_image_url( $image_id, 'medium' ) ) . '" data-large="' . esc_url( wp_get_attachment_image_url( $image_id, 'large' ) ) . '" data-imageId="' . esc_attr( (string) $image_id ) . '" alt="' . esc_attr( $product->get_name() ) . '">';
                                echo '</div>';
                            }

                            echo '</div></div>';
                        }

                    } else {
                        echo '<div class="dc-no-images">' . esc_html__( 'No images available for this product', 'dc-product-manager' ) . '</div>';
                    }
                    ?>
                </div>
                <div class="dc-product-images-note">
                    <p><?php _e( 'Images are display-only and cannot be updated from this page.', 'dc-product-manager' ); ?></p>
                </div>
            </aside>
            
            <!-- Product Edit Form (Right Side) -->
            <div class="dc-product-edit-form">
                <form id="dc-product-edit-form" class="dc-product-form">
                    <input type="hidden" id="dc-product-id" value="<?php echo esc_attr( (string) $product_data['id'] ); ?>">

                    <div class="dc-form-columns dc-form-columns--top">
                        <section class="dc-form-section">
                            <div class="dc-section-head">
                                <span class="dc-section-index">01</span>
                                <div>
                                    <h3><?php _e( 'Basic Information', 'dc-product-manager' ); ?></h3>
                                    <p><?php _e( 'Colour, category and stock basics.', 'dc-product-manager' ); ?></p>
                                </div>
                            </div>
                            <div class="dc-form-row">
                                <div class="dc-form-group">
                                    <label for="dc-product-fabric-color" class="required"><?php _e('Fabric Color', 'dc-product-manager'); ?></label>
                                    <input type="text" id="dc-product-fabric-color" value="<?php echo esc_attr($product_data['fabric_color']); ?>" required>
                                </div>
                            </div>
                            <div class="dc-form-row">
                                <div class="dc-form-group">
                                    <label for="dc-product-fabric-color-english" class="required"><?php _e('Fabric Color (English)', 'dc-product-manager'); ?></label>
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
                            <div class="dc-form-row dc-form-row--split">
                                <div class="dc-form-group">
                                    <label for="dc-product-fabric-no" class="required"><?php _e('Fabric No', 'dc-product-manager'); ?></label>
                                    <input type="text" id="dc-product-fabric-no" value="<?php echo esc_attr($product_data['fabric_no']); ?>" required>
                                </div>
                                <div class="dc-form-group">
                                    <label for="dc-product-stock"><?php _e('Stock', 'dc-product-manager'); ?></label>
                                    <div class="dc-stock-field">
                                        <input type="number" id="dc-product-stock" value="<?php echo esc_attr($product_data['stock']); ?>">
                                        <span class="stock-status <?php echo esc_attr($stock_class); ?>" title="<?php echo esc_attr(sprintf(__('Current stock: %d', 'dc-product-manager'), $stock)); ?>"></span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="dc-form-section dc-supplier-section">
                            <div class="dc-section-head">
                                <span class="dc-section-index">02</span>
                                <div>
                                    <h3><?php _e( 'Supplier Information', 'dc-product-manager' ); ?></h3>
                                    <p><?php _e( 'Vendor, SKU and fabric specs.', 'dc-product-manager' ); ?></p>
                                </div>
                            </div>
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
                            <div class="dc-form-row dc-form-row--split">
                                <div class="dc-form-group">
                                    <label for="dc-product-fabric-width"><?php _e('Fabric Width', 'dc-product-manager'); ?></label>
                                    <input type="text" id="dc-product-fabric-width" value="<?php echo esc_attr($product_data['fabric_width']); ?>">
                                </div>
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
                        </section>
                    </div>
                    
                    <section class="dc-form-section">
                        <div class="dc-section-head">
                            <span class="dc-section-index">03</span>
                            <div>
                                <h3><?php _e( 'Product Details', 'dc-product-manager' ); ?></h3>
                                <p><?php _e( 'Storefront title used on the website.', 'dc-product-manager' ); ?></p>
                            </div>
                        </div>
                        <div class="dc-form-row">
                            <div class="dc-form-group">
                                <label for="dc-product-title"><?php _e('Product Title', 'dc-product-manager'); ?></label>
                                <div class="dc-title-preview">
                                    <div id="dc-product-title-preview"><?php echo esc_html($product_data['generated_title']); ?></div>
                                    <label class="dc-custom-title-toggle">
                                        <input type="checkbox" id="dc-product-custom-title">
                                        <?php _e('Use custom title', 'dc-product-manager'); ?>
                                    </label>
                                </div>
                                <input type="text" id="dc-product-custom-title-input" value="<?php echo esc_attr($product_data['title']); ?>" style="display: none;">
                            </div>
                        </div>
                        <?php
                        $desc_map = isset( $product_data['descriptions'] ) && is_array( $product_data['descriptions'] )
                            ? $product_data['descriptions']
                            : \DC_Product_Manager\DC_Product_Descriptions::get_map( $product_data['id'] );
                        $desc_langs = \DC_Product_Manager\DC_Product_Descriptions::get_languages();
                        $first_lang = 'nb';
                        ?>
                        <div class="dc-form-row dc-descriptions-block">
                            <div class="dc-form-group" style="width:100%;">
                                <label><?php _e( 'Long description (by language)', 'dc-product-manager' ); ?></label>
                                <p class="dc-field-hint" style="margin-top:0;">
                                    <?php _e( 'Storefront shows the text for the customer’s region language. Norwegian is also saved as the WooCommerce product description.', 'dc-product-manager' ); ?>
                                </p>
                                <div class="dc-desc-lang-tabs" role="tablist">
                                    <?php foreach ( $desc_langs as $code => $meta ) : ?>
                                        <button
                                            type="button"
                                            class="dc-desc-lang-tab<?php echo $code === $first_lang ? ' is-active' : ''; ?>"
                                            data-lang="<?php echo esc_attr( $code ); ?>"
                                            role="tab"
                                            aria-selected="<?php echo $code === $first_lang ? 'true' : 'false'; ?>"
                                        >
                                            <?php echo esc_html( $meta['native'] ); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <?php foreach ( $desc_langs as $code => $meta ) : ?>
                                    <div
                                        class="dc-desc-lang-panel<?php echo $code === $first_lang ? ' is-active' : ''; ?>"
                                        data-lang-panel="<?php echo esc_attr( $code ); ?>"
                                        role="tabpanel"
                                        <?php echo $code === $first_lang ? '' : 'hidden'; ?>
                                    >
                                        <textarea
                                            class="dc-product-description"
                                            id="dc-product-description-<?php echo esc_attr( $code ); ?>"
                                            data-lang="<?php echo esc_attr( $code ); ?>"
                                            rows="8"
                                            placeholder="<?php echo esc_attr( sprintf( __( 'Product description in %s…', 'dc-product-manager' ), $meta['label'] ) ); ?>"
                                        ><?php echo esc_textarea( isset( $desc_map[ $code ] ) ? $desc_map[ $code ] : '' ); ?></textarea>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <section class="dc-form-section dc-prices-section">
                        <div class="dc-section-head">
                            <span class="dc-section-index">04</span>
                            <div>
                                <h3><?php _e( 'Prices by Region', 'dc-product-manager' ); ?></h3>
                                <p><?php _e('If a currency is empty, the default NOK price is used on the storefront.', 'dc-product-manager'); ?></p>
                            </div>
                        </div>
                        <div class="dc-multicurrency-grid">
                            <?php
                            $mc_prices = isset($product_data['multicurrency_prices']) ? $product_data['multicurrency_prices'] : array();
                            foreach (\DC_Product_Manager\DC_Region_Currency::get_regions() as $slug => $region) :
                                $code = $region['currency'];
                                $val = isset($mc_prices[$code]) ? $mc_prices[$code] : '';
                            ?>
                            <div class="dc-form-group dc-price-card">
                                <label for="dc-price-<?php echo esc_attr(strtolower($code)); ?>">
                                    <span class="dc-price-card__region"><?php echo esc_html($region['label']); ?></span>
                                    <span class="dc-price-card__code"><?php echo esc_html($region['display']); ?></span>
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
                    </section>

                    <section class="dc-form-section dc-b2b-channel-section <?php echo $is_b2b ? 'is-b2b-active' : ''; ?>">
                        <div class="dc-b2b-channel-header">
                            <div class="dc-section-head">
                                <span class="dc-section-index">05</span>
                                <div>
                                    <h3><?php _e( 'B2B / Wholesale Channel', 'dc-product-manager' ); ?></h3>
                                    <p class="dc-b2b-channel-help">
                                        <?php _e( 'Show this product in the Monte B2B wholesale portal.', 'dc-product-manager' ); ?>
                                    </p>
                                </div>
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
                    </section>
                    
                    <div class="dc-form-actions dc-form-actions--sticky">
                        <div class="dc-form-actions__note"><?php _e( 'Changes apply to storefront and B2B immediately after update.', 'dc-product-manager' ); ?></div>
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
        const topSave = document.getElementById('dc-save-product-top');
        const mainSave = document.getElementById('dc-save-product');

        if (topSave && mainSave) {
            topSave.addEventListener('click', function () {
                mainSave.click();
            });
        }

        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function () {
                const largeImage = this.getAttribute('data-large');
				 const imageid = this.getAttribute('data-imageId');
                if (largeImage && featuredImage) {
                    featuredImage.src = largeImage;
					 document.getElementById('dc-product-image').value = largeImage ;
					 document.getElementById('dc-product-image-id').value = imageid ;
                    document.querySelectorAll('.dc-product-gallery-image').forEach(function (el) {
                        el.classList.remove('is-active');
                    });
                    if (this.parentElement) {
                        this.parentElement.classList.add('is-active');
                    }
                }
            });
        });
    });
</script>


<?php
// Get footer
get_footer(); 