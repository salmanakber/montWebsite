<?php
/**
 * Plugin Name: DC Garment Stock Sync
 * Description: Sync stock between WooCommerce and dc-garment.com/staff/
 * Version: 1.0
 * Author: Suleman Khan
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin path
define('DC_GARMENT_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include required files
require_once DC_GARMENT_PLUGIN_PATH . 'includes/class-stock-sync.php';
require_once DC_GARMENT_PLUGIN_PATH . 'includes/api-handler.php';
require_once DC_GARMENT_PLUGIN_PATH . 'admin/settings-page.php';

// Initialize plugin
new DC_Garment_Stock_Sync();


