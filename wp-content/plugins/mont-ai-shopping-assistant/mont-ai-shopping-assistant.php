<?php
/**
 * Plugin Name: Mont AI Shopping Assistant
 * Plugin URI:  https://montenapoleone.no
 * Description: Premium AI shopping concierge for WooCommerce — Groq primary, Gemini fallback. Guides customers from discovery to cart with full product & custom-option knowledge.
 * Version:     1.0.0
 * Author:      Montenapoleone
 * Text Domain: mont-ai-assistant
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 *
 * @package Mont_AI_Assistant
 */

defined( 'ABSPATH' ) || exit;

define( 'MONT_AI_VERSION', '1.0.0' );
define( 'MONT_AI_FILE', __FILE__ );
define( 'MONT_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'MONT_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'MONT_AI_BASENAME', plugin_basename( __FILE__ ) );

require_once MONT_AI_PATH . 'includes/class-autoloader.php';
Mont_AI_Assistant\Autoloader::register();

/**
 * Activation: create index table + default options.
 */
function mont_ai_activate() {
	Mont_AI_Assistant\Activator::activate();
}
register_activation_hook( __FILE__, 'mont_ai_activate' );

/**
 * Deactivation cleanup (keeps settings & index).
 */
function mont_ai_deactivate() {
	Mont_AI_Assistant\Activator::deactivate();
}
register_deactivation_hook( __FILE__, 'mont_ai_deactivate' );

/**
 * Bootstrap after plugins loaded (needs WooCommerce).
 */
function mont_ai_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'Mont AI Shopping Assistant requires WooCommerce.', 'mont-ai-assistant' ) .
				'</p></div>';
		} );
		return;
	}

	Mont_AI_Assistant\Plugin::instance()->run();
}
add_action( 'plugins_loaded', 'mont_ai_bootstrap', 20 );
