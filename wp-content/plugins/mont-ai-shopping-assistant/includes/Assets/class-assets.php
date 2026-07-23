<?php
/**
 * Front-end asset loader (lazy).
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Assets;

use Mont_AI_Assistant\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Assets
 *
 * Loads chat widget CSS/JS only on the storefront (not admin).
 */
class Assets {

	/**
	 * Hooks.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		// Print markup BEFORE footer scripts (priority 20) so JS can bind clicks.
		add_action( 'wp_footer', array( $this, 'render_widget' ), 5 );
	}

	/**
	 * Whether widget should load.
	 *
	 * @return bool
	 */
	private function should_load() {
		if ( is_admin() ) {
			return false;
		}
		/**
		 * Filter whether to show the AI chat widget.
		 *
		 * @param bool $show Show.
		 */
		return (bool) apply_filters( 'mont_ai_show_widget', true );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue() {
		if ( ! $this->should_load() ) {
			return;
		}

		$settings = Plugin::settings();
		$css = MONT_AI_PATH . 'assets/css/chat-widget.css';
		$js  = MONT_AI_PATH . 'assets/js/chat-widget.js';

		wp_enqueue_style(
			'mont-ai-chat',
			MONT_AI_URL . 'assets/css/chat-widget.css',
			array(),
			file_exists( $css ) ? (string) filemtime( $css ) : MONT_AI_VERSION
		);

		wp_enqueue_script(
			'mont-ai-chat',
			MONT_AI_URL . 'assets/js/chat-widget.js',
			array(),
			file_exists( $js ) ? (string) filemtime( $js ) : MONT_AI_VERSION,
			true
		);

		$product_id = 0;
		if ( function_exists( 'is_product' ) && is_product() ) {
			$product_id = get_the_ID();
		}

		$channel = 'b2c';
		if ( $this->is_b2b_context() ) {
			$channel = 'b2b';
		}

		wp_localize_script(
			'mont-ai-chat',
			'MontAIChat',
			array(
				'restUrl'     => esc_url_raw( rest_url( 'mont-ai/v1' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'themeColor'  => $settings['theme_color'],
				'welcome'     => $settings['welcome_message'],
				'defaultLang' => $settings['default_language'],
				'languages'   => $settings['languages'],
				'productId'   => $product_id,
				'channel'     => $channel,
				'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
				'i18n'        => array(
					'title'       => ( 'b2b' === $channel )
						? __( 'B2B Assistant', 'mont-ai-assistant' )
						: __( 'Shopping Assistant', 'mont-ai-assistant' ),
					'placeholder' => ( 'b2b' === $channel )
						? __( 'Ask about wholesale fabrics, MOQ…', 'mont-ai-assistant' )
						: __( 'Ask me anything…', 'mont-ai-assistant' ),
					'send'        => __( 'Send', 'mont-ai-assistant' ),
					'thinking'    => __( 'Thinking…', 'mont-ai-assistant' ),
					'error'       => __( 'Something went wrong. Please try again.', 'mont-ai-assistant' ),
					'viewProduct' => __( 'View', 'mont-ai-assistant' ),
					'addedCart'   => __( 'Cart updated', 'mont-ai-assistant' ),
				),
			)
		);

		$color = sanitize_hex_color( $settings['theme_color'] );
		if ( $color ) {
			wp_add_inline_style( 'mont-ai-chat', ':root{--mont-ai-accent:' . $color . ';}' );
		}
	}

	/**
	 * Print widget markup.
	 */
	public function render_widget() {
		if ( ! $this->should_load() ) {
			return;
		}
		include MONT_AI_PATH . 'templates/chat-widget.php';
	}

	/**
	 * Detect Monte B2B storefront context.
	 *
	 * @return bool
	 */
	private function is_b2b_context() {
		if ( isset( $_GET['productb2b'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}
		if ( ! is_singular( 'page' ) ) {
			return false;
		}
		$post = get_post();
		if ( ! $post ) {
			return false;
		}
		if ( has_shortcode( (string) $post->post_content, 'monte_b2b_shortcode' ) ) {
			return true;
		}
		$slug = $post->post_name;
		if ( false !== strpos( $slug, 'b2b' ) || false !== strpos( $slug, 'monte-connected' ) ) {
			return true;
		}
		return (bool) apply_filters( 'mont_ai_is_b2b_context', false );
	}
}
