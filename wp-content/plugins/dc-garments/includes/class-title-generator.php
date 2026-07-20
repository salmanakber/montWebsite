<?php
namespace DC_Product_Manager;

class Title_Generator {
    public function init() {
        // Add title preview field
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_title_preview'));
        
        // Add title generation hooks
        add_action('woocommerce_process_product_meta', array($this, 'generate_title'), 10, 1);
        add_action('woocommerce_update_product_variation', array($this, 'generate_parent_title'), 10, 1);
        
        // Add AJAX handler for live preview
        add_action('wp_ajax_preview_product_title', array($this, 'ajax_preview_title'));
        
        // Add custom title override option
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_title_override_option'));
    }

    public function add_title_preview() {
        global $post;
        
        echo '<div class="options_group">';
        echo '<div class="title-preview-wrapper">';
        echo '<h4>' . __('Title Preview', 'dc-product-manager') . '</h4>';
        echo '<div id="title-preview" class="title-preview">';
        
        if ($post->ID) {
            $fabric_color = get_post_meta($post->ID, '_fabric_color', true);
            $monte_napoleone_no = get_post_meta($post->ID, '_monte_napoleone_no', true);
            $terms = get_the_terms($post->ID, 'product_cat');
            $category_name = '';
            
            if ($terms && !is_wp_error($terms)) {
                $category_name = $terms[0]->name;
            }
            
            if ($fabric_color && $monte_napoleone_no && $category_name) {
                echo esc_html($this->generate_title_string($fabric_color, $category_name, $monte_napoleone_no));
            }
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function add_title_override_option() {
        global $post;
        
        $use_custom_title = get_post_meta($post->ID, '_use_custom_title', true);
        
        woocommerce_wp_checkbox(array(
            'id' => '_use_custom_title',
            'label' => __('Use Custom Title', 'dc-product-manager'),
            'description' => __('Check this to use a custom title instead of the auto-generated one.', 'dc-product-manager'),
            'value' => $use_custom_title,
        ));
    }

    public function generate_title($post_id) {
        $use_custom_title = isset($_POST['_use_custom_title']) ? 'yes' : 'no';
        update_post_meta($post_id, '_use_custom_title', $use_custom_title);
        
        if ($use_custom_title === 'yes') {
            return;
        }
        
        $fabric_color = get_post_meta($post_id, '_fabric_color', true);
        $monte_napoleone_no = get_post_meta($post_id, '_monte_napoleone_no', true);
        $terms = get_the_terms($post_id, 'product_cat');
        $category_name = '';
        
        if ($terms && !is_wp_error($terms)) {
            $category_name = $terms[0]->name;
        }
        
        if ($fabric_color && $monte_napoleone_no && $category_name) {
            $title = $this->generate_title_string($fabric_color, $category_name, $monte_napoleone_no);
            
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $title
            ));
        }
    }

    public function generate_parent_title($variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation) {
            return;
        }
        
        $parent_id = $variation->get_parent_id();
        if (!$parent_id) {
            return;
        }
        
        $this->generate_title($parent_id);
    }

    public function ajax_preview_title() {
        check_ajax_referer('dc-product-manager-nonce', 'nonce');
        
        if (!current_user_can('edit_products')) {
            wp_send_json_error('Permission denied');
        }
        
        $fabric_color = isset($_POST['fabric_color']) ? sanitize_text_field($_POST['fabric_color']) : '';
        $category_name = isset($_POST['category_name']) ? sanitize_text_field($_POST['category_name']) : '';
        $monte_napoleone_no = isset($_POST['monte_napoleone_no']) ? sanitize_text_field($_POST['monte_napoleone_no']) : '';
        
        if (empty($fabric_color) || empty($category_name) || empty($monte_napoleone_no)) {
            wp_send_json_error('Missing required fields');
        }
        
        $title = $this->generate_title_string($fabric_color, $category_name, $monte_napoleone_no);
        wp_send_json_success(array('title' => $title));
    }

    private function generate_title_string($fabric_color, $category_name, $monte_napoleone_no) {
        return sprintf(
            '%s %s #%s',
            strtoupper($fabric_color),
            strtoupper($category_name),
            $monte_napoleone_no
        );
    }
} 