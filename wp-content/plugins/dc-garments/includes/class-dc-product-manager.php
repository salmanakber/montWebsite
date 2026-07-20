<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    DC_Product_Manager
 * @subpackage DC_Product_Manager/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    DC_Product_Manager
 * @subpackage DC_Product_Manager/includes
 * @author     Your Name <email@example.com>
 */
class DC_Product_Manager {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      DC_Product_Manager_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The supplier manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      DC_Supplier_Manager    $supplier_manager    Handles supplier-related functionality.
     */
    protected $supplier_manager;

    /**
     * The CRM manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      CRM_Manager    $crm_manager    Handles CRM-related functionality.
     */
    protected $crm_manager;

    /**
     * The endpoint handler instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Endpoint_Handler    $endpoint_handler    Handles custom endpoints and redirects.
     */
    protected $endpoint_handler;

    /**
     * The staff role manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Staff_Role_Manager    $staff_role_manager    Handles staff role management.
     */
    protected $staff_role_manager;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('DC_PRODUCT_MANAGER_VERSION')) {
            $this->version = DC_PRODUCT_MANAGER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'dc-product-manager';

        $this->load_dependencies();
        $this->set_locale();
        
        // Initialize staff role manager first
        $this->staff_role_manager = new \DC_Product_Manager\Staff_Role_Manager();
        $this->staff_role_manager->init();
        
        // Initialize other components
        $this->supplier_manager = new \DC_Product_Manager\DC_Supplier_Manager();
        $this->crm_manager = new \DC_Product_Manager\CRM_Manager();
        $this->endpoint_handler = new \DC_Product_Manager\Endpoint_Handler();
        
        // Define hooks after components are initialized
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
        // Register activation hook
        register_activation_hook(DC_PRODUCT_MANAGER_FILE, array($this, 'activate'));
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - DC_Product_Manager_Loader. Orchestrates the hooks of the plugin.
     * - DC_Product_Manager_i18n. Defines internationalization functionality.
     * - DC_Product_Manager_Admin. Defines all hooks for the admin area.
     * - DC_Product_Manager_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dc-product-manager-loader.php';

        /**
         * The class responsible for supplier management.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dc-supplier-manager.php';

        /**
         * The class responsible for staff role management.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-staff-role-manager.php';

        /**
         * The class responsible for product handling.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-product-handler.php';

        /**
         * The class responsible for title generation.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-title-generator.php';

        /**
         * The class responsible for notifications.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-notification-system.php';

        /**
         * The class responsible for handling custom endpoints and redirects.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-endpoint-handler.php';

        /**
         * The class responsible for CRM management.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-crm-manager.php';

        $this->loader = new \DC_Product_Manager\DC_Product_Manager_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        // Internationalization functionality will be added later
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        // Initialize supplier manager
        $this->supplier_manager->init();
        
        // Initialize endpoint handler
        $this->endpoint_handler->init();
        
        // Initialize product handler
        $product_handler = new \DC_Product_Manager\Product_Handler();
        $product_handler->init();
        
        // Initialize title generator
        $title_generator = new \DC_Product_Manager\Title_Generator();
        $title_generator->init();
        
        // Initialize notification system
        $notification_system = new \DC_Product_Manager\Notification_System();
        $notification_system->init();
        
        // Add menu items
        $this->loader->add_action('admin_menu', $this, 'add_admin_menu');
        
        // Add admin scripts
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_scripts');
		        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_admin_scripts');
        
        // Add admin styles
        $this->loader->add_action('admin_enqueue_styles', $this, 'enqueue_admin_styles');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_dc_get_products', $product_handler, 'ajax_get_products');
        $this->loader->add_action('wp_ajax_dc_get_product', $product_handler, 'ajax_get_product');
        $this->loader->add_action('wp_ajax_dc_update_product', $product_handler, 'ajax_update_product');
        $this->loader->add_action('wp_ajax_dc_get_suppliers', $this->supplier_manager, 'ajax_get_suppliers');
        $this->loader->add_action('wp_ajax_dc_get_supplier', $this->supplier_manager, 'ajax_get_supplier');
        $this->loader->add_action('wp_ajax_dc_save_supplier', $this->supplier_manager, 'ajax_save_supplier');
        
        // Check for low stock products periodically
        $this->loader->add_action('admin_init', $this, 'check_low_stock_products');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        // Public hooks will be added later if needed
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    DC_Product_Manager_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Check for low stock products and store the result in a transient
     * to avoid checking too frequently.
     *
     * @since     1.0.0
     */
    public function check_low_stock_products() {
        // Only check once per hour
        if (get_transient('dc_low_stock_check')) {
            return;
        }
        
        // Set transient to prevent frequent checks
        set_transient('dc_low_stock_check', true, HOUR_IN_SECONDS);
        
        // Get all products with low stock
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_stock',
                    'value'   => 10,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_stock',
                    'value'   => 0,
                    'compare' => '>',
                    'type'    => 'NUMERIC',
                ),
            ),
        );
        
        $low_stock_products = get_posts($args);
        
        // Store the count in a transient for the frontend to use
        set_transient('dc_low_stock_count', count($low_stock_products), HOUR_IN_SECONDS);
    }

    /**
     * Add the admin menu for the plugin.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Add main menu
        // add_menu_page(
        //     __('DC Product Manager', 'dc-product-manager'),
        //     __('DC Product Manager', 'dc-product-manager'),
        //     'dc_access_crm',
        //     'dc-product-manager',
        //     array($this, 'display_admin_page'),
        //     'dashicons-products',
        //     56
        // );
        
        // // Add submenu items
        // add_submenu_page(
        //     'dc-product-manager',
        //     __('Dashboard', 'dc-product-manager'),
        //     __('Dashboard', 'dc-product-manager'),
        //     'dc_access_crm',
        //     'dc-product-manager',
        //     array($this, 'display_admin_page')
        // );
        
        // add_submenu_page(
        //     'dc-product-manager',
        //     __('Products', 'dc-product-manager'),
        //     __('Products', 'dc-product-manager'),
        //     'dc_access_crm',
        //     'dc-product-manager&tab=products',
        //     array($this, 'display_admin_page')
        // );
        
        // add_submenu_page(
        //     'dc-product-manager',
        //     __('Suppliers', 'dc-product-manager'),
        //     __('Suppliers', 'dc-product-manager'),
        //     'dc_access_crm',
        //     'dc-product-manager&tab=suppliers',
        //     array($this, 'display_admin_page')
        // );
        
        // add_submenu_page(
        //     'dc-product-manager',
        //     __('Settings', 'dc-product-manager'),
        //     __('Settings', 'dc-product-manager'),
        //     'dc_access_crm',
        //     'dc-product-manager&tab=settings',
        //     array($this, 'display_admin_page')
        // );
        
        // Hide the submenu items from the admin menu
    }

    /**
     * Display the admin page for the plugin.
     *
     * @since    1.0.0
     */
    public function display_admin_page() {
        // Check user capabilities
        if (!current_user_can('dc_access_crm')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dc-product-manager'));
        }
        
        // Get the current tab
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        // Check if we're on the product edit page
        if (isset($_GET['dc_product_edit'])) {
            $product_id = intval($_GET['dc_product_edit']);
            
            // Verify product exists
            $product = get_post($product_id);
            if (!$product || $product->post_type !== 'product') {
                wp_die(__('Product not found.', 'dc-product-manager'));
            }
            
            // Get WooCommerce product
            $wc_product = wc_get_product($product_id);
            if (!$wc_product) {
                wp_die(__('WooCommerce product not found.', 'dc-product-manager'));
            }
            
            // Get product data
            $product_data = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'fabric_color' => get_post_meta($product->ID, '_fabric_color', true),
				'fabric_color_english' => get_post_meta($product->ID, '_fabric_color_english', true),
                'category_id' => get_post_meta($product->ID, '_category_id', true),
                'fabric_no' => get_post_meta($product->ID, '_fabric_no', true),
                'price' => $wc_product->get_price(),
                'stock' => $wc_product->get_stock_quantity(),
                'moq' => get_post_meta($product->ID, '_moq', true),
                'b2b_product' => get_post_meta($product->ID, '_b2b_product', true),
                'supplier_id' => get_post_meta($product->ID, '_supplier_id', true),
                'supplier_sku' => get_post_meta($product->ID, '_supplier_sku', true),
                'quality' => get_post_meta($product->ID, '_quality', true),
                'fabric_width' => get_post_meta($product->ID, '_fabric_width', true),
                'weight' => get_post_meta($product->ID, '_weight', true),
                'supplier_price' => get_post_meta($product->ID, '_supplier_price', true),
                'custom_title' => get_post_meta($product->ID, '_custom_title', true)
            );
            
            // Get category name
            $category = get_term($product_data['category_id'], 'product_cat');
            $product_data['category_name'] = $category ? $category->name : '';
            
            // Generate title string
            $product_data['generated_title'] = $this->generate_title_string(
                $product_data['fabric_color'],
                $product_data['category_name'],
                $product_data['fabric_no']
            );
            
            // Start the admin wrap
            echo '<div class="dc-admin-wrap">';
            
            // Include the sidebar
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sidebar.php';
            
            // Start the main content area
            echo '<div class="dc-main-content">';
            
            // Include the product edit template
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/product-edit.php';
            
            // Close the main content area and admin wrap
            echo '</div></div>';
            return;
        }
        
        // Start the admin wrap
        echo '<div class="dc-admin-wrap">';
        
        // Include the sidebar
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/sidebar.php';
        
        // Start the main content area
        echo '<div class="dc-main-content">';
        
        // Include the appropriate template
        switch ($tab) {
            case 'products':
                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/product-management.php';
                break;
            case 'suppliers':
                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/supplier-management.php';
                break;
            case 'settings':
                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings.php';
                break;
            default:
                require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/dashboard.php';
                break;
        }
        
        // Close the main content area and admin wrap
        echo '</div></div>';
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'dc-product-management') === false) {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'dc-product-manager-admin',
            DC_PM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            DC_PM_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'dc-product-manager-admin',
            DC_PM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            DC_PM_VERSION,
            true
        );
        
        // Enqueue product management scripts
        wp_enqueue_script(
            'dc-product-management',
            DC_PM_PLUGIN_URL . 'assets/js/product-management.js',
            array('jquery'),
            DC_PM_VERSION,
            true
        );
        
                wp_enqueue_script(
            'dc-supplier-management',
            DC_PM_PLUGIN_URL . 'assets/js/supplier-management.js',
            array('jquery'),
            DC_PM_VERSION,
            true
        );
        
        $localized_data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'siteUrl' => home_url('/'),
            'nonce' => wp_create_nonce('dc_product_manager_ajax_nonce'),
            'i18n' => array(
                'loading' => __('Loading products...', 'dc-product-manager'),
                'error' => __('Error', 'dc-product-manager'),
                'noProducts' => __('No products found.', 'dc-product-manager'),
                'noImage' => __('No image available', 'dc-product-manager'),
                'sku' => __('SKU', 'dc-product-manager'),
                'price' => __('Price', 'dc-product-manager'),
                'stock' => __('Stock', 'dc-product-manager'),
                'edit' => __('Edit', 'dc-product-manager'),
                'saving' => __('Saving...', 'dc-product-manager'),
                'update' => __('Update Product', 'dc-product-manager'),
                'success' => __('Success', 'dc-product-manager'),
                'saved' => __('Product saved successfully.', 'dc-product-manager'),
                'lowStock' => __('Low Stock Alert', 'dc-product-manager'),
                'lowStockMessage' => __('Some products are running low on stock.', 'dc-product-manager')
            )
        );
        
        wp_localize_script('dc-product-management', 'dc_product_manager', $localized_data);
        
        // Check if we're on the suppliers page
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        if ($current_tab === 'suppliers') {
            // Enqueue supplier management scripts
            wp_enqueue_script(
                'dc-supplier-management',
                DC_PM_PLUGIN_URL . 'assets/js/supplier-management.js',
                array('jquery'),
                DC_PM_VERSION,
                true
            );
            
            // Localize supplier management script
            $supplier_localized_data = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dc_supplier_manager_ajax_nonce'),
                'i18n' => array(
                    'error' => __('Error', 'dc-product-manager'),
                    'success' => __('Success', 'dc-product-manager'),
                    'confirm_delete' => __('Are you sure you want to delete this supplier?', 'dc-product-manager'),
                    'supplier_deleted' => __('Supplier deleted successfully', 'dc-product-manager'),
                    'supplier_saved' => __('Supplier saved successfully', 'dc-product-manager')
                )
            );
            
            wp_localize_script('dc-supplier-management', 'dc_supplier_manager', $supplier_localized_data);
            
            // Add inline script to ensure dc_supplier_manager is available
            wp_add_inline_script('dc-supplier-management', 'dc_supplier_manager = ' . json_encode($supplier_localized_data) . ';', 'before');
        }
        
        // Add body class to hide WordPress admin menu
        add_filter('admin_body_class', function($classes) {
            return $classes . ' dc-hide-admin-menu';
        });
        
        // Hide admin bar
        add_action('admin_head', function() {
            echo '<style>
                body.dc-hide-admin-menu #wpcontent, 
                body.dc-hide-admin-menu #wpfooter {
                    margin-left: 0;
                }
                body.dc-hide-admin-menu #adminmenumain {
                    display: none;
                }
            </style>';
        });
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_admin_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/dc-product-manager-admin.css',
            array(),
            $this->version,
            'all'
        );

        // Load CRM styles in admin
        wp_enqueue_style(
            'dc-crm-styles',
            plugin_dir_url(dirname(__FILE__)) . 'public/css/dc-crm.css',
            array('wp-admin'),
            $this->version,
            'all'
        );
    }

    /**
     * Plugin activation handler
     */
    public function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Add custom capabilities
        $this->staff_role_manager->add_custom_capabilities();
    }
} 