<?php
/**
 * Groq API provider (OpenAI-compatible).
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Providers;

use Mont_AI_Assistant\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Groq_Provider
 *
 * Primary AI provider via https://api.groq.com/openai/v1
 */
class Groq_Provider implements Provider_Interface {

	const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'groq';
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return 'Groq';
	}

	/**
	 * @inheritdoc
	 */
	public function is_configured() {
		$s = Plugin::settings();
		return ! empty( $s['groq_api_key'] );
	}

	/**
	 * @inheritdoc
	 */
	public function chat( array $messages, array $tools = array(), array $args = array() ) {
		$s     = Plugin::settings();
		$model = ! empty( $args['model'] ) ? $args['model'] : $s['groq_model'];

		$body = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => isset( $args['temperature'] ) ? (float) $args['temperature'] : (float) $s['temperature'],
			'max_tokens'  => isset( $args['max_tokens'] ) ? (int) $args['max_tokens'] : (int) $s['max_tokens'],
		);

		if ( ! empty( $tools ) ) {
			$body['tools']       = $tools;
			$body['tool_choice'] = 'auto';
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $s['groq_api_key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( 'Groq request failed: ' . $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 429 || $code >= 500 ) {
			throw new \Exception( 'Groq unavailable (HTTP ' . $code . ')' );
		}

		if ( $code < 200 || $code >= 300 || empty( $data['choices'][0]['message'] ) ) {
			$err = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown Groq error';
			throw new \Exception( 'Groq error: ' . $err );
		}

		$msg = $data['choices'][0]['message'];

		return array(
			'content'    => isset( $msg['content'] ) ? (string) $msg['content'] : '',
			'tool_calls' => isset( $msg['tool_calls'] ) && is_array( $msg['tool_calls'] ) ? $msg['tool_calls'] : array(),
			'raw'        => $data,
			'provider'   => $this->get_id(),
		);
	}
}
