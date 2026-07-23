<?php
/**
 * Google Gemini API provider.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Providers;

use Mont_AI_Assistant\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Gemini_Provider
 *
 * Fallback provider via Gemini generateContent + function calling.
 * Normalizes responses to the same shape as Groq (OpenAI-style tool_calls).
 */
class Gemini_Provider implements Provider_Interface {

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'gemini';
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return 'Google Gemini';
	}

	/**
	 * @inheritdoc
	 */
	public function is_configured() {
		$s = Plugin::settings();
		return ! empty( $s['gemini_api_key'] );
	}

	/**
	 * Build endpoint URL.
	 *
	 * @param string $model Model id.
	 * @return string
	 */
	private function endpoint( $model ) {
		$s = Plugin::settings();
		return sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
			rawurlencode( $model ),
			rawurlencode( $s['gemini_api_key'] )
		);
	}

	/**
	 * Convert OpenAI messages → Gemini contents + systemInstruction.
	 *
	 * @param array $messages Messages.
	 * @return array{system:string,contents:array}
	 */
	private function convert_messages( array $messages ) {
		$system   = '';
		$contents = array();

		foreach ( $messages as $msg ) {
			$role = isset( $msg['role'] ) ? $msg['role'] : 'user';
			$text = isset( $msg['content'] ) ? (string) $msg['content'] : '';

			if ( 'system' === $role ) {
				$system .= ( $system ? "\n\n" : '' ) . $text;
				continue;
			}

			if ( 'tool' === $role ) {
				$contents[] = array(
					'role'  => 'user',
					'parts' => array(
						array(
							'functionResponse' => array(
								'name'     => isset( $msg['name'] ) ? $msg['name'] : 'tool',
								'response' => array(
									'result' => $text,
								),
							),
						),
					),
				);
				continue;
			}

			if ( 'assistant' === $role && ! empty( $msg['tool_calls'] ) ) {
				$parts = array();
				foreach ( $msg['tool_calls'] as $tc ) {
					$fn   = isset( $tc['function']['name'] ) ? $tc['function']['name'] : '';
					$args = array();
					if ( ! empty( $tc['function']['arguments'] ) ) {
						$decoded = json_decode( $tc['function']['arguments'], true );
						$args    = is_array( $decoded ) ? $decoded : array();
					}
					$parts[] = array(
						'functionCall' => array(
							'name' => $fn,
							'args' => $args ? $args : new \stdClass(),
						),
					);
				}
				if ( $text ) {
					array_unshift( $parts, array( 'text' => $text ) );
				}
				$contents[] = array(
					'role'  => 'model',
					'parts' => $parts,
				);
				continue;
			}

			$contents[] = array(
				'role'  => ( 'assistant' === $role ) ? 'model' : 'user',
				'parts' => array( array( 'text' => $text ) ),
			);
		}

		return array(
			'system'   => $system,
			'contents' => $contents,
		);
	}

	/**
	 * Convert OpenAI tools → Gemini functionDeclarations.
	 *
	 * @param array $tools Tools.
	 * @return array
	 */
	private function convert_tools( array $tools ) {
		$decls = array();
		foreach ( $tools as $tool ) {
			if ( empty( $tool['function']['name'] ) ) {
				continue;
			}
			$fn = $tool['function'];
			$decls[] = array(
				'name'        => $fn['name'],
				'description' => isset( $fn['description'] ) ? $fn['description'] : '',
				'parameters'  => isset( $fn['parameters'] ) ? $fn['parameters'] : array( 'type' => 'object', 'properties' => new \stdClass() ),
			);
		}
		return $decls;
	}

	/**
	 * @inheritdoc
	 */
	public function chat( array $messages, array $tools = array(), array $args = array() ) {
		$s     = Plugin::settings();
		$model = ! empty( $args['model'] ) ? $args['model'] : $s['gemini_model'];
		$conv  = $this->convert_messages( $messages );

		$body = array(
			'contents'         => $conv['contents'],
			'generationConfig' => array(
				'temperature'     => isset( $args['temperature'] ) ? (float) $args['temperature'] : (float) $s['temperature'],
				'maxOutputTokens' => isset( $args['max_tokens'] ) ? (int) $args['max_tokens'] : (int) $s['max_tokens'],
			),
		);

		if ( $conv['system'] ) {
			$body['systemInstruction'] = array(
				'parts' => array( array( 'text' => $conv['system'] ) ),
			);
		}

		if ( ! empty( $tools ) ) {
			$body['tools'] = array(
				array(
					'functionDeclarations' => $this->convert_tools( $tools ),
				),
			);
		}

		$response = wp_remote_post(
			$this->endpoint( $model ),
			array(
				'timeout' => 45,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( 'Gemini request failed: ' . $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 429 || $code >= 500 ) {
			throw new \Exception( 'Gemini unavailable (HTTP ' . $code . ')' );
		}

		if ( $code < 200 || $code >= 300 || empty( $data['candidates'][0]['content']['parts'] ) ) {
			$err = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown Gemini error';
			throw new \Exception( 'Gemini error: ' . $err );
		}

		$parts      = $data['candidates'][0]['content']['parts'];
		$content    = '';
		$tool_calls = array();

		foreach ( $parts as $i => $part ) {
			if ( isset( $part['text'] ) ) {
				$content .= $part['text'];
			}
			if ( isset( $part['functionCall']['name'] ) ) {
				$args_obj = isset( $part['functionCall']['args'] ) ? $part['functionCall']['args'] : array();
				$tool_calls[] = array(
					'id'       => 'call_gemini_' . $i . '_' . wp_generate_password( 6, false ),
					'type'     => 'function',
					'function' => array(
						'name'      => $part['functionCall']['name'],
						'arguments' => wp_json_encode( $args_obj ),
					),
				);
			}
		}

		return array(
			'content'    => $content,
			'tool_calls' => $tool_calls,
			'raw'        => $data,
			'provider'   => $this->get_id(),
		);
	}
}
