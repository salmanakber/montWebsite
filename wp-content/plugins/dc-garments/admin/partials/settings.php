<?php
/**
 * Settings template for DC Product Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Save settings if form is submitted
if (isset($_POST['dc_product_manager_settings_nonce']) && wp_verify_nonce($_POST['dc_product_manager_settings_nonce'], 'dc_product_manager_settings')) {
    // Save stock threshold
    if (isset($_POST['dc_stock_threshold'])) {
        update_option('dc_stock_threshold', intval($_POST['dc_stock_threshold']));
    }
    
    // Save notification settings
    $notification_settings = array(
        'email_notifications' => isset($_POST['dc_email_notifications']) ? 1 : 0,
        'admin_notifications' => isset($_POST['dc_admin_notifications']) ? 1 : 0,
    );
    update_option('dc_notification_settings', $notification_settings);
    
    // Show success message
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'dc-product-manager') . '</p></div>';
}

// Get current settings
$stock_threshold = get_option('dc_stock_threshold', 10);
$notification_settings = get_option('dc_notification_settings', array(
    'email_notifications' => 1,
    'admin_notifications' => 1,
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('dc_product_manager_settings', 'dc_product_manager_settings_nonce'); ?>
        
        <div class="dc-settings-section">
            <h2><?php _e('Stock Management', 'dc-product-manager'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="dc_stock_threshold"><?php _e('Low Stock Threshold', 'dc-product-manager'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="dc_stock_threshold" name="dc_stock_threshold" value="<?php echo esc_attr($stock_threshold); ?>" min="1" class="regular-text">
                        <p class="description"><?php _e('The number of items that triggers a low stock notification.', 'dc-product-manager'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="dc-settings-section">
            <h2><?php _e('Notifications', 'dc-product-manager'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Notification Methods', 'dc-product-manager'); ?></th>
                    <td>
                        <fieldset>
                            <label for="dc_email_notifications">
                                <input type="checkbox" id="dc_email_notifications" name="dc_email_notifications" value="1" <?php checked($notification_settings['email_notifications'], 1); ?>>
                                <?php _e('Email Notifications', 'dc-product-manager'); ?>
                            </label>
                            <br>
                            <label for="dc_admin_notifications">
                                <input type="checkbox" id="dc_admin_notifications" name="dc_admin_notifications" value="1" <?php checked($notification_settings['admin_notifications'], 1); ?>>
                                <?php _e('Admin Dashboard Notifications', 'dc-product-manager'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings', 'dc-product-manager'); ?>">
        </p>
    </form>
</div>

<style>
    .dc-settings-section {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .dc-settings-section h2 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
</style> 