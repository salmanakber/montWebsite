<?php
/**
 * AI provider contract.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Interface Provider_Interface
 */
interface Provider_Interface {

	/**
	 * Provider slug (groq|gemini).
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Human label.
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Whether API key is configured.
	 *
	 * @return bool
	 */
	public function is_configured();

	/**
	 * Chat completion (optionally with tools).
	 *
	 * @param array $messages OpenAI-style messages.
	 * @param array $tools    Tool definitions (OpenAI format).
	 * @param array $args     Extra args (temperature, max_tokens, stream).
	 * @return array{content:string,tool_calls:array,raw:mixed,provider:string}
	 * @throws \Exception On hard failure.
	 */
	public function chat( array $messages, array $tools = array(), array $args = array() );
}
