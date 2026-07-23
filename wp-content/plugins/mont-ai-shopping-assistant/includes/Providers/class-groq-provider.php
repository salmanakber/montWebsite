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
		$attempts = 0;
		$max      = 3;
		$last_error = null;

		while ( $attempts < $max ) {
			++$attempts;
			try {
				return $this->request( $messages, $tools, $args );
			} catch ( \Exception $e ) {
				$last_error = $e;
				$msg = $e->getMessage();

				// Rate limit / transient — longer backoff (helps free-tier 429s).
				if ( false !== strpos( $msg, 'HTTP 429' ) || false !== strpos( $msg, 'HTTP 503' ) ) {
					usleep( (int) ( 1200000 * $attempts ) ); // ~1.2s, 2.4s — avoid long sleep() timeouts
					continue;
				}

				// Malformed / schema-invalid tool call — retry once without tools.
				if (
					false !== stripos( $msg, 'Failed to call a function' )
					|| false !== stripos( $msg, 'tool call validation failed' )
					|| false !== stripos( $msg, 'did not match schema' )
				) {
					if ( ! empty( $tools ) && empty( $args['no_tool_retry'] ) ) {
						Plugin::log( 'Groq tool-call invalid — retrying text-only' );
						$repair = $messages;
						$repair[] = array(
							'role'    => 'user',
							'content' => 'Continue helping the customer in plain text. Do not call tools in this reply. If they only said hi, greet them and ask what they are looking for.',
						);
						return $this->request( $repair, array(), array_merge( $args, array( 'no_tool_retry' => true ) ) );
					}
				}

				throw $e;
			}
		}

		throw $last_error ? $last_error : new \Exception( 'Groq unavailable' );
	}

	/**
	 * Perform one Groq API request.
	 *
	 * @param array $messages Messages.
	 * @param array $tools    Tools.
	 * @param array $args     Args.
	 * @return array
	 * @throws \Exception On failure.
	 */
	private function request( array $messages, array $tools, array $args ) {
		$s     = Plugin::settings();
		$model = ! empty( $args['model'] ) ? $args['model'] : $s['groq_model'];

		$body = array(
			'model'       => $model,
			'messages'    => $this->sanitize_messages( $messages ),
			'temperature' => isset( $args['temperature'] ) ? (float) $args['temperature'] : (float) $s['temperature'],
			'max_tokens'  => isset( $args['max_tokens'] ) ? (int) $args['max_tokens'] : (int) $s['max_tokens'],
		);

		if ( ! empty( $tools ) ) {
			$body['tools']               = $tools;
			$body['tool_choice']         = 'auto';
			$body['parallel_tool_calls'] = false;
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 60,
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
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code === 429 || $code >= 500 ) {
			throw new \Exception( 'Groq unavailable (HTTP ' . $code . ')' );
		}

		if ( $code < 200 || $code >= 300 || empty( $data['choices'][0]['message'] ) ) {
			$err = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown Groq error';
			throw new \Exception( 'Groq error: ' . $err );
		}

		$msg = $data['choices'][0]['message'];
		$tool_calls = array();
		if ( ! empty( $msg['tool_calls'] ) && is_array( $msg['tool_calls'] ) ) {
			foreach ( $msg['tool_calls'] as $tc ) {
				// Drop malformed tool calls instead of failing the whole turn.
				if ( empty( $tc['function']['name'] ) ) {
					continue;
				}
				$args_raw = isset( $tc['function']['arguments'] ) ? $tc['function']['arguments'] : '{}';
				if ( is_array( $args_raw ) ) {
					$args_raw = wp_json_encode( $args_raw );
				}
				$decoded = json_decode( (string) $args_raw, true );
				if ( ! is_array( $decoded ) ) {
					// Try to salvage truncated JSON.
					$decoded = array();
				}
				$tc['function']['arguments'] = wp_json_encode( $decoded );
				$tool_calls[] = $tc;
			}
		}

		return array(
			'content'    => isset( $msg['content'] ) ? (string) $msg['content'] : '',
			'tool_calls' => $tool_calls,
			'raw'        => $data,
			'provider'   => $this->get_id(),
		);
	}

	/**
	 * Normalize message payloads for Groq (null content, tool ids).
	 *
	 * @param array $messages Messages.
	 * @return array
	 */
	private function sanitize_messages( array $messages ) {
		$out = array();
		foreach ( $messages as $msg ) {
			$role = isset( $msg['role'] ) ? $msg['role'] : 'user';
			$row  = array( 'role' => $role );

			if ( 'assistant' === $role && ! empty( $msg['tool_calls'] ) ) {
				$row['content']    = isset( $msg['content'] ) && $msg['content'] !== '' ? (string) $msg['content'] : null;
				$row['tool_calls'] = $msg['tool_calls'];
			} elseif ( 'tool' === $role ) {
				$row['tool_call_id'] = isset( $msg['tool_call_id'] ) ? (string) $msg['tool_call_id'] : '';
				$row['name']         = isset( $msg['name'] ) ? (string) $msg['name'] : '';
				$row['content']      = isset( $msg['content'] ) ? (string) $msg['content'] : '';
			} else {
				$row['content'] = isset( $msg['content'] ) ? (string) $msg['content'] : '';
			}

			$out[] = $row;
		}
		return $out;
	}
}
