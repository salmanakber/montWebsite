<?php
/**
 * Product Edit template for DC Product Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get categories
$categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
));

// Get suppliers
$suppliers = get_posts(array(
    'post_type' => 'dc_supplier',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));
?>

<div class="wrap dc-product-edit">
    <h1 class="wp-heading-inline"><?php echo esc_html($product_data['title']); ?></h1>
    <a href="javascript:history.back()" class="page-title-action"><?php _e('← Back to Products', 'dc-product-manager'); ?></a>
    
    <div id="dc-notifications"></div>
    
    <form id="dc-product-form" class="dc-product-form">
        <input type="hidden" id="dc-product-id" name="product_id" value="<?php echo esc_attr($product_data['id']); ?>">
        
        <div class="dc-form-section">
            <h2><?php _e('Basic Information', 'dc-product-manager'); ?></h2>
            
            <div class="dc-form-row">
                <div class="dc-form-group">
                    <label for="dc-product-fabric-color"><?php _e('Fabric Color', 'dc-product-manager'); ?></label>
                    <input type="text" id="dc-product-fabric-color" name="fabric_color" value="<?php echo esc_attr($product_data['fabric_color']); ?>" required>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-category"><?php _e('Category', 'dc-product-manager'); ?></label>
                    <select id="dc-product-category" name="category_id" required>
                        <option value=""><?php _e('Select Category', 'dc-product-manager'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($product_data['category_id'], $category->term_id); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-fabric-no"><?php _e('Fabric No', 'dc-product-manager'); ?></label>
                    <input type="text" id="dc-product-fabric-no" name="fabric_no" value="<?php echo esc_attr($product_data['fabric_no']); ?>" required>
                </div>
            </div>
            
            <div class="dc-form-row">
                <div class="dc-form-group">
                    <label>
                        <input type="checkbox" id="dc-custom-title" name="custom_title" <?php checked($product_data['custom_title'], '1'); ?>>
                        <?php _e('Use Custom Title', 'dc-product-manager'); ?>
                    </label>
                </div>
                
                <div class="dc-form-group dc-custom-title-field" style="display: <?php echo $product_data['custom_title'] ? 'block' : 'none'; ?>">
                    <label for="dc-product-title"><?php _e('Custom Title', 'dc-product-manager'); ?></label>
                    <input type="text" id="dc-product-title" name="title" value="<?php echo esc_attr($product_data['title']); ?>">
                </div>
                
                <div id="dc-title-preview" class="dc-title-preview" style="display: <?php echo $product_data['custom_title'] ? 'none' : 'block'; ?>"><?php echo esc_html($product_data['generated_title']); ?></div>
            </div>
        </div>
        
        <div class="dc-form-section">
            <h2><?php _e('Pricing & Stock', 'dc-product-manager'); ?></h2>
            
            <div class="dc-form-row">
                <div class="dc-form-group">
                    <label for="dc-product-price"><?php _e('Price', 'dc-product-manager'); ?></label>
                    <input type="number" id="dc-product-price" name="price" value="<?php echo esc_attr($product_data['price']); ?>" step="0.01" min="0" required>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-stock"><?php _e('Stock', 'dc-product-manager'); ?></label>
                    <input type="number" id="dc-product-stock" name="stock" value="<?php echo esc_attr($product_data['stock']); ?>" min="0" required>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-moq"><?php _e('MOQ', 'dc-product-manager'); ?></label>
                    <input type="number" id="dc-product-moq" name="moq" value="<?php echo esc_attr($product_data['moq']); ?>" min="1" required>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-b2b"><?php _e('B2B Product', 'dc-product-manager'); ?></label>
                    <select id="dc-product-b2b" name="b2b_product" required>
                        <option value="0" <?php selected($product_data['b2b_product'], '0'); ?>><?php _e('No', 'dc-product-manager'); ?></option>
                        <option value="1" <?php selected($product_data['b2b_product'], '1'); ?>><?php _e('Yes', 'dc-product-manager'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="dc-form-section">
            <h2><?php _e('Supplier Information', 'dc-product-manager'); ?></h2>
            
            <div class="dc-form-row">
                <div class="dc-form-group">
                    <label for="dc-product-supplier"><?php _e('Supplier', 'dc-product-manager'); ?></label>
                    <select id="dc-product-supplier" name="supplier_id" required>
                        <option value=""><?php _e('Select Supplier', 'dc-product-manager'); ?></option>
                        <?php foreach ($suppliers as $supplier) : ?>
                            <option value="<?php echo esc_attr($supplier->ID); ?>" <?php selected($product_data['supplier_id'], $supplier->ID); ?>>
                                <?php echo esc_html($supplier->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-supplier-sku"><?php _e('Supplier SKU', 'dc-product-manager'); ?></label>
                    <input type="text" id="dc-product-supplier-sku" name="supplier_sku" value="<?php echo esc_attr($product_data['supplier_sku']); ?>" required>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-quality"><?php _e('Quality', 'dc-product-manager'); ?></label>
                    <input type="text" id="dc-product-quality" name="quality" value="<?php echo esc_attr($product_data['quality']); ?>" required>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-fabric-width"><?php _e('Fabric Width', 'dc-product-manager'); ?></label>
                    <input type="text" id="dc-product-fabric-width" name="fabric_width" value="<?php echo esc_attr($product_data['fabric_width']); ?>" required>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-weight"><?php _e('Weight', 'dc-product-manager'); ?></label>
                    <input type="text" id="dc-product-weight" name="weight" value="<?php echo esc_attr($product_data['weight']); ?>" required>
                </div>
                
                <div class="dc-form-group">
                    <label for="dc-product-supplier-price"><?php _e('Supplier Price', 'dc-product-manager'); ?></label>
                    <input type="number" id="dc-product-supplier-price" name="supplier_price" value="<?php echo esc_attr($product_data['supplier_price']); ?>" step="0.01" min="0" required>
                </div>
            </div>
        </div>
        
        <div class="dc-form-actions">
            <button type="submit" id="dc-save-product" class="button button-primary"><?php _e('Update Product', 'dc-product-manager'); ?></button>
        </div>
    </form>
</div> 