<?php
/**
 * Product Management template for DC Product Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap dc-product-crm">
    <!--<h1><?php //_e('Product CRM', 'dc-product-manager'); ?></h1>-->
    
    
    <div class="dc-product-management">
        <div class="dc-product-list-container">
            <div class="dc-product-list-header">
                <h2><?php _e('Manage Products', 'dc-product-manager'); ?></h2>
                <div class="dc-bulk-actions">
                    <!--<button id="dc-bulk-edit-toggle" class="button"><?php //_e('Bulk Edit', 'dc-product-manager'); ?></button>-->
                    <div class="dc-bulk-filters">
                        <button class="button dc-select-all"><?php _e('Select All', 'dc-product-manager'); ?></button>
                        <button class="button dc-select-out-of-stock"><?php _e('Select Out of Stock', 'dc-product-manager'); ?></button>
                        <button class="button dc-select-low-stock"><?php _e('Select Low Stock', 'dc-product-manager'); ?></button>
                        <button class="button dc-select-in-stock"><?php _e('Select In Stock', 'dc-product-manager'); ?></button>
                    </div>
                </div>
            </div>
            
<div class="dc-category-filter">
    <h3><?php _e('Categories', 'dc-product-manager'); ?></h3>
    <div class="dc-category-list">
        <a href="#" class="dc-category-item active" data-category="all"><?php _e('All Products', 'dc-product-manager'); ?></a>
        <?php
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        // Separate parents and children
        $parents = [];
        $children = [];

        foreach ($categories as $category) {
            if ($category->name === 'Uncategorized') continue;

            if ($category->parent == 0) {
                $parents[$category->term_id] = $category;
            } else {
                $children[$category->parent][] = $category;
            }
        }

        // Output parent categories
        foreach ($parents as $parent_id => $parent) {
            echo '<a href="#" class="dc-category-item parent-cat" data-category="' . esc_attr($parent_id) . '"  data-parent-id="' . esc_attr($parent_id) . '">' . esc_html($parent->name) . '</a>';

            // Prepare child container hidden by default
            if (isset($children[$parent_id])) {
                echo '<div class="dc-child-categories" data-child-of="' . esc_attr($parent_id) . '" style="display: none; margin-left: 10px;">';
                foreach ($children[$parent_id] as $child) {
                    echo '<a href="#" class="dc-category-item child-cat" data-category="' . esc_attr($child->term_id) . '">' . esc_html($child->name) . '</a>';
                }
                echo '</div>';
            }
        }
        ?>
    </div>
</div>

            
            <div class="dc-product-search">
                <input type="text" id="dc-product-search" placeholder="<?php _e('Search products...', 'dc-product-manager'); ?>">
            </div>
            
            <div class="dc-bulk-actions" style="display: none;">
                <button class="button button-primary" id="dc-bulk-edit-button">
                    <?php _e('Bulk Edit Selected', 'dc-product-manager'); ?>
                </button>
				         <button class="button button-primary" id="dc-bulk-delete-button">
                    <?php _e('Bulk Delete Selected', 'dc-product-manager'); ?>
                </button>
            </div>

            <!-- Bulk Edit Popup Modal -->
            <div id="dc-bulk-edit-modal" class="dc-modal">
                <div class="dc-modal-content">
                    <div class="dc-modal-header">
                        <h3><?php _e('Bulk Edit Products', 'dc-product-manager'); ?></h3>
                        <button class="dc-modal-close">&times;</button>
                    </div>
                    <div class="dc-modal-body">
                        <div class="dc-bulk-edit-fields">
                            <div class="dc-form-row">
                                <div class="dc-form-group">
                                    <label for="dc-bulk-price"><?php _e('Price', 'dc-product-manager'); ?></label>
                                    <input type="number" id="dc-bulk-price" step="0.01" placeholder="<?php _e('Leave empty to keep current value', 'dc-product-manager'); ?>">
                                </div>
                                <div class="dc-form-group">
                                    <label for="dc-bulk-stock"><?php _e('Stock', 'dc-product-manager'); ?></label>
                                    <input type="number" id="dc-bulk-stock" placeholder="<?php _e('Leave empty to keep current value', 'dc-product-manager'); ?>">
                                </div>
                            </div>
                            <div class="dc-form-row">
                                <div class="dc-form-group">
                                    <label for="dc-bulk-supplier-price"><?php _e('Supplier Price', 'dc-product-manager'); ?></label>
                                    <input type="number" id="dc-bulk-supplier-price" step="0.01" placeholder="<?php _e('Leave empty to keep current value', 'dc-product-manager'); ?>">
                                </div>
                                <div class="dc-form-group">
                                    <label for="dc-bulk-quality"><?php _e('Quality', 'dc-product-manager'); ?></label>
                                    <input type="text" id="dc-bulk-quality" placeholder="<?php _e('Leave empty to keep current value', 'dc-product-manager'); ?>">
                                </div>
                            </div>
                            <div class="dc-form-row">
                                <div class="dc-form-group">
                                    <label for="dc-bulk-fabric-width"><?php _e('Fabric Width', 'dc-product-manager'); ?></label>
                                    <input type="text" id="dc-bulk-fabric-width" placeholder="<?php _e('Leave empty to keep current value', 'dc-product-manager'); ?>">
                                </div>
                                <div class="dc-form-group">
                                    <label for="dc-bulk-weight"><?php _e('Weight', 'dc-product-manager'); ?></label>
                                    <input type="text" id="dc-bulk-weight" placeholder="<?php _e('Leave empty to keep current value', 'dc-product-manager'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dc-modal-footer">
                        <button id="dc-bulk-edit-cancel" class="button"><?php _e('Cancel', 'dc-product-manager'); ?></button>
                        <button id="dc-bulk-edit-apply" class="button button-primary"><?php _e('Apply to Selected Products', 'dc-product-manager'); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="dc-product-grid" id="dc-product-list-body">
                <div class="dc-loading"><?php _e('Loading products...', 'dc-product-manager'); ?></div>
            </div>
        </div>
    </div>
</div>
<script>
jQuery(document).ready(function ($) {
    $('.parent-cat').on('click', function (e) {
        e.preventDefault();
        var parentId = $(this).data('parent-id');
		$('.dc-category-list').addClass('make-space')

        // Hide all other child groups
        $('.dc-child-categories').slideUp();

        // Toggle the one that was clicked
        $('[data-child-of="' + parentId + '"]').slideToggle();
		
    });
});
</script>
<style>
.make-space {
    position: relative;
    margin-bottom: 55px;
}
	.dc-child-categories {
    position: absolute;
    top: 44px;
    display: flex;
    gap: 7px;
    border-top: 1px solid #ddd;
    width: 100%;
    padding-top: 7px;
}
</style>
<!-- Notification Area -->
<div id="dc-notifications" class="dc-notifications"></div> 