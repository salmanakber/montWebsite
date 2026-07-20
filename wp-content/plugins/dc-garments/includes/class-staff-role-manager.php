<?php
namespace DC_Product_Manager;

class Staff_Role_Manager {
    
        public function __construct() {
        add_filter( 'woocommerce_login_redirect', [ $this, 'redirect_dc_staff_after_login' ], 10, 2 );
    }
    
    public function init() {
        // Add staff role on plugin activation
        add_action('init', array($this, 'add_staff_role'));
        
        // Modify admin menu for staff role
        add_action('admin_menu', array($this, 'modify_admin_menu'), 999);
        add_shortcode('dc_staff_dashboard_button', array($this,'dc_staff_dashboard_button_shortcode'));
        
        // Restrict access to certain admin pages
        add_action('admin_init', array($this, 'restrict_admin_access'));
        
        
        // Add custom capabilities
        add_action('admin_init', array($this, 'add_custom_capabilities'));
    }
    
    public function dc_staff_dashboard_button_shortcode() {
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        if ( in_array( 'dc_staff', (array) $user->roles ) ) {
            $dashboard_url = site_url('/crm'); // Change to your desired dashboard URL
            return '<a href="' . esc_url($dashboard_url) . '" class="dc-dashboard-button" style="padding: 10px 10px;background: #000000;color: white;text-decoration: none;font-size: 11px;FONT-WEIGHT: 300;">Stock Management </a>';
        }
    }
    return ''; // Return nothing if not dc_staff or not logged in
}

public function redirect_dc_staff_after_login( $redirect, $user ) {
    if ( ! is_wp_error( $user ) && in_array( 'dc_staff', (array) $user->roles ) ) {
        return site_url( '/crm' ); // Change to your CRM URL
    }

    return $redirect;
}


    public function add_staff_role() {
        // Check if the role already exists
        if (!get_role('dc_staff')) {
            add_role(
                'dc_staff',
                __('DC Staff', 'dc-product-manager'),
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'edit_pages' => false,
                    'edit_others_posts' => false,
                    'create_posts' => false,
                    'upload_files' => true,
                    'edit_products' => true,
                    'edit_published_products' => true,
                    'publish_products' => true,
                    'read_private_products' => true,
                    'edit_private_products' => true,
                    'edit_supplier' => true,
                    'edit_suppliers' => true,
                    'edit_others_suppliers' => true,
                    'publish_suppliers' => true,
                    'read_private_suppliers' => true,
                    'edit_private_suppliers' => true,
                    'dc_access_crm' => true,
                )
            );
        } else {
            // Update existing role to add the new capability
            $role = get_role('dc_staff');
            if ($role) {
                $role->add_cap('dc_access_crm');
            }
        }
    }

    public function modify_admin_menu() {
        if (!current_user_can('administrator')) {
            // Remove unnecessary menu items
            remove_menu_page('index.php'); // Dashboard
            remove_menu_page('edit.php'); // Posts
            remove_menu_page('upload.php'); // Media
            remove_menu_page('edit.php?post_type=page'); // Pages
            remove_menu_page('edit-comments.php'); // Comments
            remove_menu_page('themes.php'); // Appearance
            remove_menu_page('plugins.php'); // Plugins
            remove_menu_page('users.php'); // Users
            remove_menu_page('tools.php'); // Tools
            remove_menu_page('options-general.php'); // Settings

            // Add custom menu items
        //     add_menu_page(
        //         __('Product Management', 'dc-product-manager'),
        //         __('Product Management', 'dc-product-manager'),
        //         'edit_products',
        //         'edit.php?post_type=product',
        //         '',
        //         'dashicons-cart',
        //         56
        //     );

        //     add_menu_page(
        //         __('Suppliers', 'dc-product-manager'),
        //         __('Suppliers', 'dc-product-manager'),
        //         'edit_suppliers',
        //         'edit.php?post_type=supplier',
        //         '',
        //         'dashicons-businessman',
        //         57
        //     );
            
        //     // Add DC Product Manager menu for staff
        //     add_menu_page(
        //         __('DC Product Manager', 'dc-product-manager'),
        //         __('DC Product Manager', 'dc-product-manager'),
        //         'dc_access_crm',
        //         'dc-product-manager',
        //         '',
        //         'dashicons-products',
        //         55
        //     );
        }
    }

    public function restrict_admin_access() {
        if (!current_user_can('administrator')) {
            global $pagenow;

            // List of allowed admin pages
            $allowed_pages = array(
                'edit.php',
                'post.php',
                'post-new.php',
                'admin-ajax.php',
                'upload.php',
                'media-upload.php',
                'admin.php',
            );

            // Check if current page is not in allowed pages
            if (!in_array($pagenow, $allowed_pages)) {
                wp_redirect(admin_url('edit.php?post_type=product'));
                exit;
            }

            // Additional checks for specific pages
            if ($pagenow === 'edit.php') {
                $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'post';
                if (!in_array($post_type, array('product', 'supplier'))) {
                    wp_redirect(admin_url('edit.php?post_type=product'));
                    exit;
                }
            }

            if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
                $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : get_post_type($_GET['post']);
                if (!in_array($post_type, array('product', 'supplier'))) {
                    wp_redirect(admin_url('edit.php?post_type=product'));
                    exit;
                }
            }
            
            // Allow access to the product management page
            if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'dc-product-management') {
                return;
            }
        }
    }

    public function add_custom_capabilities() {
        // Add custom capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('edit_supplier');
            $admin_role->add_cap('edit_suppliers');
            $admin_role->add_cap('edit_others_suppliers');
            $admin_role->add_cap('publish_suppliers');
            $admin_role->add_cap('read_private_suppliers');
            $admin_role->add_cap('edit_private_suppliers');
            $admin_role->add_cap('dc_access_crm');
        }
    }

    public static function activate() {
        try {
            // Ensure WooCommerce is fully loaded
            if (!function_exists('WC')) {
                throw new Exception('WooCommerce is not fully initialized during role creation.');
            }

            // Create staff role
            $staff_role = new self();
            $staff_role->add_staff_role();
            $staff_role->add_custom_capabilities();
        } catch (Exception $e) {
            error_log('DC Product Manager role creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function deactivate() {
        // Remove staff role
        remove_role('dc_staff');
    }
} 