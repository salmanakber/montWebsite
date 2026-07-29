<?php
/**
 * Uninstall Mont DeepL Translate.
 *
 * @package Mont_DeepL
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

delete_option('mont_deepl_settings');
delete_option('mont_deepl_usage');

$table = $wpdb->prefix . 'mont_deepl_cache';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query("DROP TABLE IF EXISTS {$table}");
