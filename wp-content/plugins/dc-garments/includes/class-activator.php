<?php
namespace DC_Product_Manager;

class Activator {
    public static function activate() {
        // Create staff role
        Staff_Role_Manager::activate();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    private static function set_default_options() {
        // Set default stock threshold if not set
        if (!get_option('dc_stock_threshold')) {
            update_option('dc_stock_threshold', 10);
        }
        
        // Initialize notifications array if not set
        if (!get_option('dc_stock_notifications')) {
            update_option('dc_stock_notifications', array());
        }
    }
} 