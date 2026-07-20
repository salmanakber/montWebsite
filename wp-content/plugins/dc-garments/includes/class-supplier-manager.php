<?php
namespace DC_Product_Manager;

class Supplier_Manager {
    public function init() {
        // Register supplier post type
        add_action('init', array($this, 'register_supplier_post_type'));
        
        // Add supplier fields to product
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_supplier_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_supplier_fields'));
        
        // AJAX handlers
        add_action('wp_ajax_create_supplier', array($this, 'ajax_create_supplier'));
        add_action('wp_ajax_get_supplier_details', array($this, 'ajax_get_supplier_details'));
    }

    public function register_supplier_post_type() {
        $labels = array(
            'name'               => __('Suppliers', 'dc-product-manager'),
            'singular_name'      => __('Supplier', 'dc-product-manager'),
            'menu_name'          => __('Suppliers', 'dc-product-manager'),
            'add_new'            => __('Add New', 'dc-product-manager'),
            'add_new_item'       => __('Add New Supplier', 'dc-product-manager'),
            'edit_item'          => __('Edit Supplier', 'dc-product-manager'),
            'new_item'           => __('New Supplier', 'dc-product-manager'),
            'view_item'          => __('View Supplier', 'dc-product-manager'),
            'search_items'       => __('Search Suppliers', 'dc-product-manager'),
            'not_found'          => __('No suppliers found', 'dc-product-manager'),
            'not_found_in_trash' => __('No suppliers found in trash', 'dc-product-manager'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 57,
            'menu_icon'           => 'dashicons-businessman',
            'supports'            => array('title'),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'register_meta_box_cb' => array($this, 'add_supplier_meta_boxes'),
        );

        register_post_type('supplier', $args);
    }

    public function add_supplier_meta_boxes() {
        add_meta_box(
            'supplier_details',
            __('Supplier Details', 'dc-product-manager'),
            array($this, 'render_supplier_meta_box'),
            'supplier',
            'normal',
            'high'
        );
    }

    public function render_supplier_meta_box($post) {
        wp_nonce_field('supplier_meta_box', 'supplier_meta_box_nonce');

        $sku = get_post_meta($post->ID, '_supplier_sku', true);
        $quality = get_post_meta($post->ID, '_supplier_quality', true);
        $fabric_width = get_post_meta($post->ID, '_supplier_fabric_width', true);
        $weight = get_post_meta($post->ID, '_supplier_weight', true);
        $price = get_post_meta($post->ID, '_supplier_price', true);

        ?>
        <div class="supplier-fields">
            <p>
                <label for="supplier_sku"><?php _e('SKU', 'dc-product-manager'); ?></label>
                <input type="text" id="supplier_sku" name="supplier_sku" value="<?php echo esc_attr($sku); ?>" />
            </p>
            <p>
                <label for="supplier_quality"><?php _e('Quality', 'dc-product-manager'); ?></label>
                <select id="supplier_quality" name="supplier_quality">
                    <option value="premium" <?php selected($quality, 'premium'); ?>><?php _e('Premium', 'dc-product-manager'); ?></option>
                    <option value="standard" <?php selected($quality, 'standard'); ?>><?php _e('Standard', 'dc-product-manager'); ?></option>
                </select>
            </p>
            <p>
                <label for="supplier_fabric_width"><?php _e('Fabric Width (cm)', 'dc-product-manager'); ?></label>
                <input type="number" id="supplier_fabric_width" name="supplier_fabric_width" value="<?php echo esc_attr($fabric_width); ?>" step="0.1" min="0" />
            </p>
            <p>
                <label for="supplier_weight"><?php _e('Weight (GSM)', 'dc-product-manager'); ?></label>
                <input type="number" id="supplier_weight" name="supplier_weight" value="<?php echo esc_attr($weight); ?>" step="1" min="0" />
            </p>
            <p>
                <label for="supplier_price"><?php _e('Price ($ per meter)', 'dc-product-manager'); ?></label>
                <input type="number" id="supplier_price" name="supplier_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" />
            </p>
        </div>
        <?php
    }

    public function add_supplier_fields() {
        global $post;

        echo '<div class="options_group">';
        
        // Supplier Selection
        $suppliers = get_posts(array(
            'post_type' => 'supplier',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $supplier_options = array('' => __('Select a supplier', 'dc-product-manager'));
        foreach ($suppliers as $supplier) {
            $supplier_options[$supplier->ID] = $supplier->post_title;
        }

        woocommerce_wp_select(array(
            'id' => '_supplier_id',
            'label' => __('Supplier', 'dc-product-manager'),
            'options' => $supplier_options,
            'desc_tip' => true,
            'description' => __('Select the supplier for this product', 'dc-product-manager'),
        ));

        // Add New Supplier Button
        echo '<p class="form-field">';
        echo '<button type="button" class="button" id="add_new_supplier">' . __('Add New Supplier', 'dc-product-manager') . '</button>';
        echo '</p>';

        echo '</div>';
    }

    public function save_supplier_fields($post_id) {
        // Save supplier ID
        $supplier_id = isset($_POST['_supplier_id']) ? absint($_POST['_supplier_id']) : '';
        update_post_meta($post_id, '_supplier_id', $supplier_id);
    }

    public function ajax_create_supplier() {
        check_ajax_referer('dc-product-manager-nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error('Permission denied');
        }

        $supplier_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $sku = isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : '';
        $quality = isset($_POST['quality']) ? sanitize_text_field($_POST['quality']) : '';
        $fabric_width = isset($_POST['fabric_width']) ? floatval($_POST['fabric_width']) : 0;
        $weight = isset($_POST['weight']) ? absint($_POST['weight']) : 0;
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

        if (empty($supplier_name)) {
            wp_send_json_error('Supplier name is required');
        }

        $supplier_id = wp_insert_post(array(
            'post_title' => $supplier_name,
            'post_type' => 'supplier',
            'post_status' => 'publish'
        ));

        if (is_wp_error($supplier_id)) {
            wp_send_json_error($supplier_id->get_error_message());
        }

        update_post_meta($supplier_id, '_supplier_sku', $sku);
        update_post_meta($supplier_id, '_supplier_quality', $quality);
        update_post_meta($supplier_id, '_supplier_fabric_width', $fabric_width);
        update_post_meta($supplier_id, '_supplier_weight', $weight);
        update_post_meta($supplier_id, '_supplier_price', $price);

        wp_send_json_success(array(
            'id' => $supplier_id,
            'name' => $supplier_name
        ));
    }

    public function ajax_get_supplier_details() {
        check_ajax_referer('dc-product-manager-nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error('Permission denied');
        }

        $supplier_id = isset($_POST['supplier_id']) ? absint($_POST['supplier_id']) : 0;

        if (!$supplier_id) {
            wp_send_json_error('Invalid supplier ID');
        }

        $supplier = get_post($supplier_id);
        if (!$supplier || $supplier->post_type !== 'supplier') {
            wp_send_json_error('Supplier not found');
        }

        $details = array(
            'id' => $supplier_id,
            'name' => $supplier->post_title,
            'sku' => get_post_meta($supplier_id, '_supplier_sku', true),
            'quality' => get_post_meta($supplier_id, '_supplier_quality', true),
            'fabric_width' => get_post_meta($supplier_id, '_supplier_fabric_width', true),
            'weight' => get_post_meta($supplier_id, '_supplier_weight', true),
            'price' => get_post_meta($supplier_id, '_supplier_price', true),
        );

        wp_send_json_success($details);
    }
} 