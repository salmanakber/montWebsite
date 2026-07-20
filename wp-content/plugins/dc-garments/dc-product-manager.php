<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://example.com/dc-product-manager
 * @since             1.0.0
 * @package           DC_Product_Manager
 *
 * @wordpress-plugin
 * Plugin Name:       DC Product Manager
 * Plugin URI:        sixerwb.com
 * Description:       A CRM-like interface for managing WooCommerce products.
 * Version:           1.0.0
 * Author:            Salman akber
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dc-product-manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('DC_PRODUCT_MANAGER_VERSION', '1.0.0');

/**
 * Plugin URL.
 */
define('DC_PM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin version.
 */
define('DC_PM_VERSION', DC_PRODUCT_MANAGER_VERSION);

/**
 * Plugin directory path.
 */
define('DC_PM_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin file path.
 */
define('DC_PRODUCT_MANAGER_FILE', __FILE__);

/**
 * The code that runs during plugin activation.
 */
function activate_dc_product_manager() {
    // Check if WooCommerce is active
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        
        // Display error message
        wp_die(
            __('DC Product Manager requires WooCommerce to be installed and activated.', 'dc-product-manager'),
            __('Plugin Activation Error', 'dc-product-manager'),
            array('back_link' => true)
        );
    }
    
    require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
    DC_Product_Manager\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_dc_product_manager() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-deactivator.php';
    DC_Product_Manager\Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_dc_product_manager');
register_deactivation_hook(__FILE__, 'deactivate_dc_product_manager');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-dc-product-manager.php';

/**
 * Begins execution of the plugin.
 */
function run_dc_product_manager() {
    // Check if WooCommerce is active
    if (!dc_product_manager_check_woocommerce()) {
        return;
    }
    
    $plugin = new DC_Product_Manager();
    $plugin->run();
}
run_dc_product_manager();

/**
 * Check if WooCommerce is active
 */
function dc_product_manager_check_woocommerce() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'dc_product_manager_woocommerce_notice');
        return false;
    }
    return true;
}

/**
 * Display WooCommerce notice
 */
function dc_product_manager_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('DC Product Manager requires WooCommerce to be installed and activated.', 'dc-product-manager'); ?></p>
    </div>
    <?php
}

/**
 * Remove admin sidebar when viewing our plugin
 */
function dc_product_manager_remove_admin_sidebar() {
    $screen = get_current_screen();
    
    // Check if we're on our plugin page
    if ($screen && $screen->id === 'toplevel_page_dc-product-manager') {
        echo '<style>
            #wpcontent, #wpfooter {
                margin-left: 0 !important;
            }
            #adminmenumain {
                display: none !important;
            }
            #wpcontent {
                padding-left: 0 !important;
            }
            .wrap {
                margin: 10px 20px 0 2px !important;
            }
        </style>';
    }
}
add_action('admin_head', 'dc_product_manager_remove_admin_sidebar'); 

