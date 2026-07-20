<?php
namespace DC_Product_Manager;

/**
 * Supplier Manager Class
 *
 * @package    DC_Product_Manager
 * @subpackage DC_Product_Manager/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Supplier Manager Class
 *
 * Handles supplier-related functionality
 */
class DC_Supplier_Manager {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Register supplier post type
        add_action('init', array($this, 'register_supplier_post_type'));
        
        // Add AJAX handlers
        add_action('wp_ajax_dc_add_supplier', array($this, 'ajax_add_supplier'));
        add_action('wp_ajax_dc_get_suppliers', array($this, 'ajax_get_suppliers'));
    }

    /**
     * Initialize the supplier manager
     */
    public function init() {
        // Register supplier post type
        add_action('init', array($this, 'register_supplier_post_type'));
        
        // Add AJAX handlers
        add_action('wp_ajax_dc_add_supplier', array($this, 'ajax_add_supplier'));
        add_action('wp_ajax_dc_get_suppliers', array($this, 'ajax_get_suppliers'));
        add_action('wp_ajax_dc_get_supplier', array($this, 'ajax_get_supplier'));
        add_action('wp_ajax_dc_save_supplier', array($this, 'ajax_save_supplier'));
        add_action('wp_ajax_dc_delete_supplier', array($this, 'ajax_delete_supplier'));

        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Register supplier post type
     */
    public function register_supplier_post_type() {
        $labels = array(
            'name'               => _x('Suppliers', 'post type general name', 'dc-product-manager'),
            'singular_name'      => _x('Supplier', 'post type singular name', 'dc-product-manager'),
            'menu_name'          => _x('Suppliers', 'admin menu', 'dc-product-manager'),
            'name_admin_bar'     => _x('Supplier', 'add new on admin bar', 'dc-product-manager'),
            'add_new'            => _x('Add New', 'supplier', 'dc-product-manager'),
            'add_new_item'       => __('Add New Supplier', 'dc-product-manager'),
            'new_item'           => __('New Supplier', 'dc-product-manager'),
            'edit_item'          => __('Edit Supplier', 'dc-product-manager'),
            'view_item'          => __('View Supplier', 'dc-product-manager'),
            'all_items'          => __('All Suppliers', 'dc-product-manager'),
            'search_items'       => __('Search Suppliers', 'dc-product-manager'),
            'parent_item_colon'  => __('Parent Suppliers:', 'dc-product-manager'),
            'not_found'          => __('No suppliers found.', 'dc-product-manager'),
            'not_found_in_trash' => __('No suppliers found in Trash.', 'dc-product-manager')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Supplier information.', 'dc-product-manager'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'supplier'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail')
        );

        register_post_type('supplier', $args);
    }

    /**
     * AJAX handler for adding a new supplier
     */
    public function ajax_add_supplier() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc_supplier_manager_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to perform this action');
        }

        // Validate required fields
        if (empty($_POST['supplier_name'])) {
            wp_send_json_error('Supplier name is required');
        }

        // Create supplier post
        $supplier_data = array(
            'post_title'  => sanitize_text_field($_POST['supplier_name']),
            'post_content' => isset($_POST['supplier_address']) ? sanitize_textarea_field($_POST['supplier_address']) : '',
            'post_status' => 'publish',
            'post_type'   => 'supplier'
        );

        $supplier_id = wp_insert_post($supplier_data);

        if (is_wp_error($supplier_id)) {
            wp_send_json_error($supplier_id->get_error_message());
        }

        // Save supplier meta
        if (isset($_POST['supplier_email'])) {
            update_post_meta($supplier_id, '_supplier_email', sanitize_email($_POST['supplier_email']));
        }

        if (isset($_POST['supplier_phone'])) {
            update_post_meta($supplier_id, '_supplier_phone', sanitize_text_field($_POST['supplier_phone']));
        }

        // Return supplier data
        $supplier = array(
            'id' => $supplier_id,
            'name' => $supplier_data['post_title'],
            'email' => isset($_POST['supplier_email']) ? sanitize_email($_POST['supplier_email']) : '',
            'phone' => isset($_POST['supplier_phone']) ? sanitize_text_field($_POST['supplier_phone']) : '',
            'address' => isset($_POST['supplier_address']) ? sanitize_textarea_field($_POST['supplier_address']) : ''
        );

        wp_send_json_success($supplier);
    }

    /**
     * AJAX handler for getting all suppliers
     */
    public function ajax_get_suppliers() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc_supplier_manager_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Get suppliers
        $suppliers = get_posts(array(
            'post_type' => 'supplier',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $supplier_data = array();

        foreach ($suppliers as $supplier) {
            $supplier_data[] = array(
                'id' => $supplier->ID,
                'name' => $supplier->post_title,
                'email' => get_post_meta($supplier->ID, '_supplier_email', true),
                'phone' => get_post_meta($supplier->ID, '_supplier_phone', true),
                'address' => $supplier->post_content
            );
        }

        wp_send_json_success($supplier_data);
    }

    /**
     * Get all suppliers
     *
     * @return array Array of suppliers
     */
    public function get_suppliers() {
        $suppliers = get_posts(array(
            'post_type' => 'supplier',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $supplier_data = array();

        foreach ($suppliers as $supplier) {
            $supplier_data[] = array(
                'id' => $supplier->ID,
                'name' => $supplier->post_title,
                'email' => get_post_meta($supplier->ID, '_supplier_email', true),
                'phone' => get_post_meta($supplier->ID, '_supplier_phone', true),
                'address' => $supplier->post_content
            );
        }

        return $supplier_data;
    }

    /**
     * Get all suppliers
     *
     * @return array Array of supplier data
     */
    public function get_all_suppliers() {
        $suppliers = array();
        
        $args = array(
            'post_type' => 'dc_supplier',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $suppliers[] = array(
                    'id' => $post_id,
                    'name' => get_the_title(),
                    'contact_name' => get_post_meta($post_id, '_supplier_contact_name', true),
                    'email' => get_post_meta($post_id, '_supplier_email', true),
                    'phone' => get_post_meta($post_id, '_supplier_phone', true),
                    'address' => get_post_meta($post_id, '_supplier_address', true)
                );
            }
            wp_reset_postdata();
        }
        
        return $suppliers;
    }

    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_dc_get_supplier', array($this, 'ajax_get_supplier'));
        add_action('wp_ajax_dc_save_supplier', array($this, 'ajax_save_supplier'));
        add_action('wp_ajax_dc_delete_supplier', array($this, 'ajax_delete_supplier'));
    }

    /**
     * AJAX handler to get a supplier
     */
    public function ajax_get_supplier() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc_supplier_manager_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('dc_access_crm')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get supplier ID
        $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
        if (!$supplier_id) {
            wp_send_json_error('Invalid supplier ID');
        }
        
        // Get supplier data
        $supplier = $this->get_supplier($supplier_id);
        if (!$supplier) {
            wp_send_json_error('Supplier not found');
        }
        
        wp_send_json_success($supplier);
    }

    /**
     * AJAX handler to save a supplier
     */
    public function ajax_save_supplier() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc_supplier_manager_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('dc_access_crm')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get supplier data
        $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $contact_name = isset($_POST['contact_name']) ? sanitize_text_field($_POST['contact_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        
        // Validate data
        if (empty($name)) {
            wp_send_json_error('Supplier name is required');
        }
        
//         if (empty($contact_name)) {
//             wp_send_json_error('Contact name is required');
//         }
        
//         if (empty($email)) {
//             wp_send_json_error('Email is required');
//         }
        
//         if (empty($phone)) {
//             wp_send_json_error('Phone is required');
//         }
        
//         if (empty($address)) {
//             wp_send_json_error('Address is required');
//         }
        
        // Save supplier
        $result = $this->save_supplier($supplier_id, $name, $contact_name, $email, $phone, $address);
        
        if ($result) {
            wp_send_json_success('Supplier saved successfully');
        } else {
            wp_send_json_error('Error saving supplier');
        }
    }

    /**
     * AJAX handler to delete a supplier
     */
    public function ajax_delete_supplier() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc_supplier_manager_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('dc_access_crm')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get supplier ID
        $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
        if (!$supplier_id) {
            wp_send_json_error('Invalid supplier ID');
        }
        
        // Delete supplier
        $result = $this->delete_supplier($supplier_id);
        
        if ($result) {
            wp_send_json_success('Supplier deleted successfully');
        } else {
            wp_send_json_error('Error deleting supplier');
        }
    }

    /**
     * Save a supplier
     *
     * @param int    $supplier_id   Supplier ID (0 for new supplier)
     * @param string $name          Supplier name
     * @param string $contact_name  Contact name
     * @param string $email         Email
     * @param string $phone         Phone
     * @param string $address       Address
     * @return int|bool             Supplier ID on success, false on failure
     */
    public function save_supplier($supplier_id, $name, $contact_name, $email, $phone, $address) {
        // Create or update post
        $post_data = array(
            'post_title'  => $name,
            'post_status' => 'publish',
            'post_type'   => 'dc_supplier'
        );
        
        if ($supplier_id) {
            $post_data['ID'] = $supplier_id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Save meta data
        update_post_meta($post_id, '_supplier_contact_name', $contact_name);
        update_post_meta($post_id, '_supplier_email', $email);
        update_post_meta($post_id, '_supplier_phone', $phone);
        update_post_meta($post_id, '_supplier_address', $address);
        
        return $post_id;
    }

    /**
     * Delete a supplier
     *
     * @param int $supplier_id Supplier ID
     * @return bool True on success, false on failure
     */
    public function delete_supplier($supplier_id) {
        // Check if supplier exists
        $supplier = get_post($supplier_id);
        if (!$supplier || $supplier->post_type !== 'dc_supplier') {
            return false;
        }
        
        // Delete supplier
        $result = wp_delete_post($supplier_id, true);
        
        return $result !== false;
    }

    /**
     * Enqueue supplier manager scripts
     */
    public function enqueue_scripts($hook) {
        // Only load on supplier management page
        if (strpos($hook, 'dc-product-management') === false) {
            return;
        }

        // First, ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // Prepare the data for JavaScript
        $supplier_manager_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dc_supplier_manager_nonce'),
            'i18n' => array(
                'error' => __('Error', 'dc-product-management'),
                'success' => __('Success', 'dc-product-management'),
                'confirm_delete' => __('Are you sure you want to delete this supplier?', 'dc-product-management'),
                'supplier_deleted' => __('Supplier deleted successfully', 'dc-product-management'),
                'supplier_saved' => __('Supplier saved successfully', 'dc-product-management'),
                'server_error' => __('Error connecting to the server', 'dc-product-management')
            )
        );

        // Add the dc_supplier_manager object directly in the head
        wp_add_inline_script('jquery', 'var dc_supplier_manager = ' . wp_json_encode($supplier_manager_data) . ';', 'before');

        // Register and enqueue the main script with jQuery dependency
        wp_enqueue_script(
            'dc-supplier-management',
            DC_PRODUCT_MANAGEMENT_URL . 'assets/js/supplier-management.js',
            array('jquery'),
            DC_PRODUCT_MANAGEMENT_VERSION,
            true
        );

        // Add a small inline script to verify the object is available
        wp_add_inline_script('dc-supplier-management', 'console.log("dc_supplier_manager object:", dc_supplier_manager);', 'after');
    }
} 