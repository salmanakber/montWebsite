<?php
namespace DC_Product_Manager;

class Deactivator {
    public static function deactivate() {
        // Remove staff role
        Staff_Role_Manager::deactivate();
        
        // Clear scheduled events
        wp_clear_scheduled_hook('dc_check_stock_levels');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
} 