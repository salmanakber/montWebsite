<?php
/**
 * AI Provider Manager — Groq primary, Gemini automatic fallback.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Providers;

use Mont_AI_Assistant\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Provider_Manager
 *
 * Every chat request tries the primary provider first. On timeout,
 * rate-limit, or error it transparently retries with the fallback.
 * Users never see the switch; the answering provider is logged.
 */
class Provider_Manager {

	/**
	 * Registered providers keyed by id.
	 *
	 * @var Provider_Interface[]
	 */
	private $providers = array();

	/**
	 * Constructor — registers built-in providers.
	 */
	public function __construct() {
		$this->register( new Groq_Provider() );
		$this->register( new Gemini_Provider() );

		/**
		 * Allow third parties to register additional providers.
		 *
		 * @param Provider_Manager $manager This manager.
		 */
		do_action( 'mont_ai_register_providers', $this );
	}

	/**
	 * Register a provider.
	 *
	 * @param Provider_Interface $provider Provider.
	 */
	public function register( Provider_Interface $provider ) {
		$this->providers[ $provider->get_id() ] = $provider;
	}

	/**
	 * Get provider by id.
	 *
	 * @param string $id Provider id.
	 * @return Provider_Interface|null
	 */
	public function get( $id ) {
		return isset( $this->providers[ $id ] ) ? $this->providers[ $id ] : null;
	}

	/**
	 * All providers.
	 *
	 * @return Provider_Interface[]
	 */
	public function all() {
		return $this->providers;
	}

	/**
	 * Chat with automatic fallback.
	 *
	 * @param array $messages Messages.
	 * @param array $tools    Tools.
	 * @param array $args     Args.
	 * @return array Response with provider key.
	 * @throws \Exception When both providers fail.
	 */
	public function chat( array $messages, array $tools = array(), array $args = array() ) {
		$settings = Plugin::settings();
		$primary  = isset( $settings['primary_provider'] ) ? $settings['primary_provider'] : 'groq';
		$fallback = isset( $settings['fallback_provider'] ) ? $settings['fallback_provider'] : 'gemini';

		$order = array_unique( array( $primary, $fallback ) );
		$errors = array();

		foreach ( $order as $id ) {
			$provider = $this->get( $id );
			if ( ! $provider || ! $provider->is_configured() ) {
				$errors[ $id ] = 'Not configured';
				continue;
			}

			try {
				$result = $provider->chat( $messages, $tools, $args );
				Plugin::log(
					'Provider answered',
					array(
						'provider' => $id,
						'primary'  => $primary,
						'fallback' => ( $id !== $primary ),
					)
				);
				$result['provider']         = $id;
				$result['used_fallback']    = ( $id !== $primary );
				return $result;
			} catch ( \Exception $e ) {
				$errors[ $id ] = $e->getMessage();
				Plugin::log(
					'Provider failed — trying next',
					array(
						'provider' => $id,
						'error'    => $e->getMessage(),
					)
				);
			}
		}

		throw new \Exception(
			'All AI providers failed: ' . wp_json_encode( $errors )
		);
	}
}
