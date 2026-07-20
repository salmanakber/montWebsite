<?php
/**
Plugin Name: Monte B2B
Plugin URI: http://montenapoleone.com
Description: Custom b2b plugin which connect two website database using REST APIs.
Version: 1.0
Author: Salman akber
Author URI: http://salman.sixerweb.com
Text Domain: b2b monte
Domain Path: /staff
License: GPL2

 * @package sixerWeb
 * @version 0.1
 * @author Sixerweb <suppoer@sixerweb.com>
 * @copyright Copyright (c) 2025, sixerweb
 * @link http://www.sixerweb.com
*/
session_start();
if (!function_exists('add_action')) {
    echo 'WordPress not found!';
    exit;
}

// Define plugin directory path
define('B2B_PATH', plugin_dir_path(__FILE__));
define('B2B_URL_PATH', plugin_dir_url(__FILE__));

// Include required files
require_once B2B_PATH . 'include/classes/getApi.php';
require_once B2B_PATH . 'include/classes/mainClasses.php';
require_once B2B_PATH . 'include/classes/ajax.php';

// Instantiate the b2b class
$b2b_plugin = new b2b(B2B_URL_PATH , B2B_PATH);
$b2b_plugin = new ajax(B2B_URL_PATH , B2B_PATH);

// Register activation hook
register_activation_hook(__FILE__, array($b2b_plugin, 'plugin_activation'));
?>
