<?php
/**
 * Main plugin orchestrator.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant;

use Mont_AI_Assistant\Admin\Settings;
use Mont_AI_Assistant\API\Rest_Controller;
use Mont_AI_Assistant\Assets\Assets;
use Mont_AI_Assistant\Product\Product_Index;
use Mont_AI_Assistant\Services\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Boots admin, REST, front assets, and product index hooks.
 */
class Plugin {

	/**
	 * Singleton.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire hooks.
	 */
	public function run() {
		( new Settings() )->register();
		( new Rest_Controller() )->register();
		( new Assets() )->register();
		( new Product_Index() )->register();

		add_action( 'mont_ai_rebuild_index', array( Product_Index::class, 'rebuild_all' ) );

		if ( ! wp_next_scheduled( 'mont_ai_rebuild_index' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mont_ai_rebuild_index' );
		}
	}

	/**
	 * Get merged settings.
	 *
	 * @return array
	 */
	public static function settings() {
		$defaults = array(
			'groq_api_key'       => '',
			'gemini_api_key'     => '',
			'primary_provider'   => 'groq',
			'fallback_provider'  => 'gemini',
			'groq_model'         => 'llama-3.3-70b-versatile',
			'gemini_model'       => 'gemini-2.0-flash',
			'temperature'        => 0.4,
			'max_tokens'         => 2048,
			'theme_color'        => '#1b3359',
			'welcome_message'    => '',
			'system_prompt'      => '',
			'allowed_categories' => array(),
			'enable_logging'     => 1,
			'enable_debug'       => 0,
			'languages'          => array( 'en', 'it', 'nb', 'vi' ),
			'default_language'   => 'en',
		);
		$saved = get_option( 'mont_ai_settings', array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	/**
	 * Logger helper.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 */
	public static function log( $message, $context = array() ) {
		Logger::log( $message, $context );
	}
}
