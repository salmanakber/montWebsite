<?php
namespace DC_Product_Manager;

class Plugin {
    private static $instance = null;
    private $product_handler;
    private $supplier_manager;
    private $staff_role_manager;
    private $notification_system;
    private $title_generator;

    public static function get_instance() {
        if (null === self::$instance) {
            error_log('DC Product Manager: Creating new Plugin instance');
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    private function init_hooks() {
        error_log('DC Product Manager: Initializing hooks');
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    private function load_dependencies() {
        // Load core classes
        require_once DC_PM_PLUGIN_DIR . 'includes/class-product-handler.php';
        require_once DC_PM_PLUGIN_DIR . 'includes/class-supplier-manager.php';
        require_once DC_PM_PLUGIN_DIR . 'includes/class-staff-role-manager.php';
        require_once DC_PM_PLUGIN_DIR . 'includes/class-notification-system.php';
        require_once DC_PM_PLUGIN_DIR . 'includes/class-title-generator.php';

        // Initialize components
        $this->product_handler = new Product_Handler();
        $this->supplier_manager = new Supplier_Manager();
        $this->staff_role_manager = new Staff_Role_Manager();
        $this->notification_system = new Notification_System();
        $this->title_generator = new Title_Generator();
    }

    public function init() {
        // Initialize components
        $this->product_handler->init();
        $this->supplier_manager->init();
        $this->staff_role_manager->init();
        $this->notification_system->init();
        $this->title_generator->init();
    }

    public function enqueue_admin_assets($hook) {
        // Load admin styles on all admin pages
        wp_enqueue_style(
            'dc-product-manager-admin',
            DC_PM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            DC_PM_VERSION
        );

        // Only load admin scripts on product edit screen
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        $screen = get_current_screen();
        if ($screen->post_type !== 'product') {
            return;
        }

        // Enqueue admin scripts
        wp_enqueue_script(
            'dc-product-manager-admin',
            DC_PM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            DC_PM_VERSION,
            true
        );

        // Localize script
        wp_localize_script('dc-product-manager-admin', 'dcProductManager', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dc-product-manager-nonce'),
            'i18n' => array(
                'titlePreview' => __('Title Preview', 'dc-product-manager'),
                'saving' => __('Saving...', 'dc-product-manager'),
                'saved' => __('Saved!', 'dc-product-manager'),
                'error' => __('Error occurred', 'dc-product-manager'),
            )
        ));
    }

    public function add_admin_menu() {
        // Debug message
        error_log('DC Product Manager: Adding admin menu');
        
        // Add main menu item
    // add_menu_page(
    //     __('DC Product Manager', 'dc-product-manager'),
    //     __('DC Product Manager', 'dc-product-manager'),
    //     'manage_options',
    //     'dc-product-manager-external', // Slug
    //     'dc_product_manager_redirect', // Callback redirects to external URL
    //     'dashicons-cart',
    //     56
    // );

        // Add submenu items
        // add_submenu_page(
        //     'dc-product-manager',
        //     __('Dashboard', 'dc-product-manager'),
        //     __('Dashboard', 'dc-product-manager'),
        //     'manage_options',
        //     'dc-product-manager',
        //     array($this, 'render_admin_page')
        // );

        // add_submenu_page(
        //     'dc-product-manager',
        //     __('Products', 'dc-product-manager'),
        //     __('Products', 'dc-product-manager'),
        //     'edit_products',
        //     'edit.php?post_type=product'
        // );

        // add_submenu_page(
        //     'dc-product-manager',
        //     __('Suppliers', 'dc-product-manager'),
        //     __('Suppliers', 'dc-product-manager'),
        //     'edit_suppliers',
        //     'edit.php?post_type=supplier'
        // );

        // add_submenu_page(
        //     'dc-product-manager',
        //     __('Settings', 'dc-product-manager'),
        //     __('Settings', 'dc-product-manager'),
        //     'manage_options',
        //     'dc-product-manager-settings',
        //     array($this, 'render_settings_page')
        // );
    }
    
    function dc_product_manager_redirect() {
    // Change this to your external URL
    $external_url = home_url().'crm';

    // Optional: Add security check
    if (current_user_can('manage_options')) {
        wp_redirect($external_url);
        exit;
    } else {
        wp_die('Unauthorized');
    }
}

    public function render_admin_page() {
        // Include the admin dashboard template
        include DC_PM_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    public function render_settings_page() {
        // Include the settings page template
        include DC_PM_PLUGIN_DIR . 'admin/partials/settings.php';
    }
} 