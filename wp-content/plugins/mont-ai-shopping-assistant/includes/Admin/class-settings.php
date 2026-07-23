<?php
/**
 * Admin settings page.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Admin;

use Mont_AI_Assistant\Plugin;
use Mont_AI_Assistant\Services\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 */
class Settings {

	const OPTION = 'mont_ai_settings';

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'save' ) );
	}

	/**
	 * Menu under WooCommerce.
	 */
	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'AI Shopping Assistant', 'mont-ai-assistant' ),
			__( 'AI Assistant', 'mont-ai-assistant' ),
			'manage_woocommerce',
			'mont-ai-assistant',
			array( $this, 'render' )
		);
	}

	/**
	 * Persist settings.
	 */
	public function save() {
		if ( ! isset( $_POST['mont_ai_settings_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mont_ai_settings_nonce'] ) ), 'mont_ai_save_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$raw = isset( $_POST['mont_ai'] ) ? wp_unslash( $_POST['mont_ai'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$cats = array();
		if ( ! empty( $raw['allowed_categories'] ) && is_array( $raw['allowed_categories'] ) ) {
			$cats = array_map( 'intval', $raw['allowed_categories'] );
		}

		$langs = array();
		if ( ! empty( $raw['languages'] ) && is_array( $raw['languages'] ) ) {
			$langs = array_map( 'sanitize_text_field', $raw['languages'] );
		}

		$settings = array(
			'groq_api_key'       => isset( $raw['groq_api_key'] ) ? sanitize_text_field( $raw['groq_api_key'] ) : '',
			'gemini_api_key'     => isset( $raw['gemini_api_key'] ) ? sanitize_text_field( $raw['gemini_api_key'] ) : '',
			'primary_provider'   => isset( $raw['primary_provider'] ) ? sanitize_text_field( $raw['primary_provider'] ) : 'groq',
			'fallback_provider'  => isset( $raw['fallback_provider'] ) ? sanitize_text_field( $raw['fallback_provider'] ) : 'gemini',
			'groq_model'         => isset( $raw['groq_model'] ) ? sanitize_text_field( $raw['groq_model'] ) : 'llama-3.3-70b-versatile',
			'gemini_model'       => isset( $raw['gemini_model'] ) ? sanitize_text_field( $raw['gemini_model'] ) : 'gemini-2.0-flash',
			'temperature'        => isset( $raw['temperature'] ) ? floatval( $raw['temperature'] ) : 0.4,
			'max_tokens'         => isset( $raw['max_tokens'] ) ? intval( $raw['max_tokens'] ) : 2048,
			'theme_color'        => isset( $raw['theme_color'] ) ? sanitize_hex_color( $raw['theme_color'] ) : '#1b3359',
			'welcome_message'    => isset( $raw['welcome_message'] ) ? sanitize_textarea_field( $raw['welcome_message'] ) : '',
			'system_prompt'      => isset( $raw['system_prompt'] ) ? wp_kses_post( $raw['system_prompt'] ) : '',
			'allowed_categories' => $cats,
			'enable_logging'     => ! empty( $raw['enable_logging'] ) ? 1 : 0,
			'enable_debug'       => ! empty( $raw['enable_debug'] ) ? 1 : 0,
			'languages'          => $langs ? $langs : array( 'en', 'it', 'nb', 'vi' ),
			'default_language'   => isset( $raw['default_language'] ) ? sanitize_text_field( $raw['default_language'] ) : 'en',
		);

		if ( ! $settings['theme_color'] ) {
			$settings['theme_color'] = '#1b3359';
		}

		update_option( self::OPTION, $settings );
		add_settings_error( 'mont_ai', 'saved', __( 'Settings saved.', 'mont-ai-assistant' ), 'updated' );
	}

	/**
	 * Render page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$settings = Plugin::settings();
		$logs     = Logger::recent( 30 );
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		include MONT_AI_PATH . 'includes/Admin/views/settings-page.php';
	}
}
