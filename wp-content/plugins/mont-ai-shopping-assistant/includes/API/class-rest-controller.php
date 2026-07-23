<?php
/**
 * REST API registration.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rest_Controller
 *
 * Routes under /wp-json/mont-ai/v1/
 */
class Rest_Controller {

	const NS = 'mont-ai/v1';

	/**
	 * Register routes.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Route map.
	 */
	public function routes() {
		register_rest_route(
			self::NS,
			'/chat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'chat' ),
				'permission_callback' => array( $this, 'public_permission' ),
			)
		);

		register_rest_route(
			self::NS,
			'/config',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'config' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/cart',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'cart' ),
				'permission_callback' => array( $this, 'public_permission' ),
			)
		);
	}

	/**
	 * Verify nonce for public endpoints.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public function public_permission( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			$nonce = $request->get_param( '_wpnonce' );
		}
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error( 'mont_ai_forbidden', 'Invalid nonce', array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Public widget config (no secrets).
	 *
	 * @return \WP_REST_Response
	 */
	public function config() {
		$s = \Mont_AI_Assistant\Plugin::settings();
		$langs = \Mont_AI_Assistant\Language\Language_Manager::all();
		$allowed = isset( $s['languages'] ) ? (array) $s['languages'] : array_keys( $langs );
		$list = array();
		foreach ( $allowed as $code ) {
			if ( isset( $langs[ $code ] ) ) {
				$list[] = $langs[ $code ];
			}
		}

		return rest_ensure_response(
			array(
				'welcome'          => $s['welcome_message'],
				'theme_color'      => $s['theme_color'],
				'default_language' => $s['default_language'],
				'languages'        => $list,
				'debug'            => ! empty( $s['enable_debug'] ),
			)
		);
	}

	/**
	 * Chat endpoint.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function chat( $request ) {
		$message  = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
		$language = sanitize_text_field( (string) $request->get_param( 'language' ) );
		$history  = $request->get_param( 'history' );
		$context  = $request->get_param( 'context' );

		if ( '' === trim( $message ) ) {
			return new \WP_Error( 'mont_ai_empty', 'Message required', array( 'status' => 400 ) );
		}

		if ( ! is_array( $history ) ) {
			$history = array();
		}
		// Cap history length.
		$history = array_slice( $history, -20 );
		if ( ! is_array( $context ) ) {
			$context = array();
		}
		if ( isset( $context['product_id'] ) ) {
			$context['product_id'] = (int) $context['product_id'];
		}
		if ( isset( $context['channel'] ) && 'b2b' === $context['channel'] ) {
			$context['channel'] = 'b2b';
		} else {
			$context['channel'] = 'b2c';
		}

		try {
			$service = new \Mont_AI_Assistant\Services\Chat_Service();
			$result  = $service->handle( $message, $history, $language, $context );

			$settings = \Mont_AI_Assistant\Plugin::settings();
			if ( empty( $settings['enable_debug'] ) ) {
				unset( $result['provider'], $result['used_fallback'] );
			}

			return rest_ensure_response( $result );
		} catch ( \Throwable $e ) {
			\Mont_AI_Assistant\Plugin::log( 'Chat failed', array( 'error' => $e->getMessage() ) );
			return rest_ensure_response(
				array(
					'success'   => false,
					'message'   => __( 'Sorry, I could not process that right now. Please try again in a few seconds.', 'mont-ai-assistant' ),
					'retryable' => true,
					'cards'     => array(),
					'choices'   => null,
				)
			);
		}
	}

	/**
	 * Cart snapshot.
	 *
	 * @return \WP_REST_Response
	 */
	public function cart() {
		$cart = new \Mont_AI_Assistant\Cart\Cart_Service();
		return rest_ensure_response( $cart->get_cart() );
	}
}
