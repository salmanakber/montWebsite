<?php
/**
 * Plugin activation / deactivation.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant;

defined( 'ABSPATH' ) || exit;

/**
 * Class Activator
 */
class Activator {

	/**
	 * Run on plugin activate.
	 */
	public static function activate() {
		self::create_tables();
		self::seed_defaults();
		flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivate.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'mont_ai_rebuild_index' );
		flush_rewrite_rules();
	}

	/**
	 * Create product index table.
	 */
	private static function create_tables() {
		global $wpdb;
		$table   = $wpdb->prefix . 'mont_ai_product_index';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			product_id bigint(20) unsigned NOT NULL,
			sku varchar(100) NOT NULL DEFAULT '',
			title text NOT NULL,
			search_blob longtext NOT NULL,
			payload longtext NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY product_id (product_id),
			KEY sku (sku),
			FULLTEXT KEY ft_search (title, search_blob)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Default option values.
	 */
	private static function seed_defaults() {
		$defaults = array(
			'groq_api_key'          => '',
			'gemini_api_key'        => '',
			'primary_provider'      => 'groq',
			'fallback_provider'     => 'gemini',
			'groq_model'            => 'llama-3.3-70b-versatile',
			'gemini_model'          => 'gemini-2.0-flash',
			'temperature'           => 0.4,
			'max_tokens'            => 2048,
			'theme_color'           => '#1b3359',
			'welcome_message'       => 'Hi! I\'m your personal shopping assistant. Tell me what you\'re looking for and I\'ll help you find the perfect shirt.',
			'system_prompt'         => '',
			'allowed_categories'    => array(),
			'enable_logging'        => 1,
			'enable_debug'          => 0,
			'languages'             => array( 'en', 'it', 'nb', 'vi' ),
			'default_language'      => 'en',
		);

		if ( false === get_option( 'mont_ai_settings' ) ) {
			add_option( 'mont_ai_settings', $defaults );
		}
	}
}
