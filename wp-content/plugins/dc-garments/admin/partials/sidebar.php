<?php
/**
 * Custom sidebar template for DC Product Manager CRM
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current page/tab
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
?>

<div class="dc-sidebar">
    <div class="dc-sidebar-header">
        <h2><?php _e('DC Product Manager', 'dc-product-manager'); ?></h2>
    </div>
    
    <div class="dc-sidebar-menu">
        <ul>
            <li class="<?php echo ($current_tab === 'dashboard') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=dc-product-manager')); ?>">
                    <span class="dashicons dashicons-dashboard"></span>
                    <?php _e('Dashboard', 'dc-product-manager'); ?>
                </a>
            </li>
            
            <li class="<?php echo ($current_tab === 'products') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=dc-product-manager&tab=products')); ?>">
                    <span class="dashicons dashicons-products"></span>
                    <?php _e('Products', 'dc-product-manager'); ?>
                </a>
            </li>
            
            <li class="<?php echo ($current_tab === 'suppliers') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=dc-product-manager&tab=suppliers')); ?>">
                    <span class="dashicons dashicons-businessman"></span>
                    <?php _e('Suppliers', 'dc-product-manager'); ?>
                </a>
            </li>
            
            <li class="<?php echo ($current_tab === 'settings') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=dc-product-manager&tab=settings')); ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Settings', 'dc-product-manager'); ?>
                </a>
            </li>
        </ul>
    </div>
</div> 