<?php
/**
 * Dashboard template for DC Product Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="dc-product-manager-dashboard">
        <div class="dc-dashboard-welcome">
            <h2><?php _e('Welcome to DC Product Manager', 'dc-product-manager'); ?></h2>
            <p><?php _e('This plugin helps you manage your WooCommerce products, suppliers, and automate product titles.', 'dc-product-manager'); ?></p>
        </div>
        
        <div class="dc-dashboard-stats">
            <div class="dc-stat-box">
                <h3><?php _e('Products', 'dc-product-manager'); ?></h3>
                <p class="dc-stat-number"><?php echo wp_count_posts('product')->publish; ?></p>
                <a href="<?php echo admin_url('edit.php?post_type=product'); ?>" class="button button-primary"><?php _e('Manage Products', 'dc-product-manager'); ?></a>
            </div>
            
            <div class="dc-stat-box">
                <h3><?php _e('Suppliers', 'dc-product-manager'); ?></h3>
                <p class="dc-stat-number"><?php echo wp_count_posts('supplier')->publish; ?></p>
                <a href="<?php echo admin_url('edit.php?post_type=supplier'); ?>" class="button button-primary"><?php _e('Manage Suppliers', 'dc-product-manager'); ?></a>
            </div>
        </div>
        
        <div class="dc-dashboard-quick-actions">
            <h3><?php _e('Quick Actions', 'dc-product-manager'); ?></h3>
            <div class="dc-quick-actions-buttons">
                <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button"><?php _e('Add New Product', 'dc-product-manager'); ?></a>
                <a href="<?php echo admin_url('post-new.php?post_type=supplier'); ?>" class="button"><?php _e('Add New Supplier', 'dc-product-manager'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=dc-product-manager-settings'); ?>" class="button"><?php _e('Plugin Settings', 'dc-product-manager'); ?></a>
            </div>
        </div>
    </div>
</div>

<style>
    .dc-product-manager-dashboard {
        margin-top: 20px;
    }
    
    .dc-dashboard-welcome {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .dc-dashboard-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .dc-stat-box {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        flex: 1;
        text-align: center;
    }
    
    .dc-stat-number {
        font-size: 36px;
        font-weight: bold;
        margin: 10px 0;
        color: #2271b1;
    }
    
    .dc-dashboard-quick-actions {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .dc-quick-actions-buttons {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
</style> 