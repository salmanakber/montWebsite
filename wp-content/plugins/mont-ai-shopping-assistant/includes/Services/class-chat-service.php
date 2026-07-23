<?php
/**
 * Chat orchestration — system prompt, tools loop, provider fallback.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Services;

use Mont_AI_Assistant\Language\Language_Manager;
use Mont_AI_Assistant\Plugin;
use Mont_AI_Assistant\Providers\Provider_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Chat_Service
 *
 * Runs a multi-turn tool-calling loop until the model returns a final reply.
 */
class Chat_Service {

	/**
	 * Max tool rounds per user message.
	 *
	 * @var int
	 */
	const MAX_TOOL_ROUNDS = 6;

	/**
	 * Handle a user message.
	 *
	 * @param string $message   User text.
	 * @param array  $history   Prior messages [{role,content}].
	 * @param string $language  Language code.
	 * @param array  $context   Extra context (page product id, etc).
	 * @return array
	 */
	public function handle( $message, array $history, $language = 'en', array $context = array() ) {
		$language = Language_Manager::normalize( $language );
		$tools    = new Tool_Executor();
		$manager  = new Provider_Manager();

		$messages = array();
		$messages[] = array(
			'role'    => 'system',
			'content' => $this->system_prompt( $language, $context ),
		);

		foreach ( $history as $h ) {
			if ( empty( $h['role'] ) || empty( $h['content'] ) ) {
				continue;
			}
			$role = in_array( $h['role'], array( 'user', 'assistant' ), true ) ? $h['role'] : 'user';
			$messages[] = array(
				'role'    => $role,
				'content' => sanitize_textarea_field( $h['content'] ),
			);
		}

		$messages[] = array(
			'role'    => 'user',
			'content' => sanitize_textarea_field( $message ),
		);

		$definitions = $tools->definitions();
		$cards       = array();
		$cart_updated = false;
		$provider_used = '';
		$used_fallback = false;

		for ( $round = 0; $round < self::MAX_TOOL_ROUNDS; $round++ ) {
			$result = $manager->chat( $messages, $definitions, array() );
			$provider_used = $result['provider'];
			$used_fallback = ! empty( $result['used_fallback'] ) || $used_fallback;

			$tool_calls = $result['tool_calls'];
			$content    = trim( (string) $result['content'] );

			if ( empty( $tool_calls ) ) {
				return array(
					'success'       => true,
					'message'       => $content,
					'cards'         => $cards,
					'cart_updated'  => $cart_updated,
					'provider'      => $provider_used,
					'used_fallback' => $used_fallback,
					'language'      => $language,
					'timestamp'     => gmdate( 'c' ),
				);
			}

			// Append assistant tool-call message.
			$messages[] = array(
				'role'       => 'assistant',
				'content'    => $content,
				'tool_calls' => $tool_calls,
			);

			foreach ( $tool_calls as $tc ) {
				$fn_name = isset( $tc['function']['name'] ) ? $tc['function']['name'] : '';
				$fn_args = array();
				if ( ! empty( $tc['function']['arguments'] ) ) {
					$decoded = json_decode( $tc['function']['arguments'], true );
					$fn_args = is_array( $decoded ) ? $decoded : array();
				}

				$tool_result = $tools->execute( $fn_name, $fn_args );

				if ( ! empty( $tool_result['cards'] ) && is_array( $tool_result['cards'] ) ) {
					$cards = array_merge( $cards, $tool_result['cards'] );
				}
				if ( ! empty( $tool_result['cart_updated'] ) ) {
					$cart_updated = true;
				}

				$messages[] = array(
					'role'         => 'tool',
					'tool_call_id' => isset( $tc['id'] ) ? $tc['id'] : '',
					'name'         => $fn_name,
					'content'      => wp_json_encode( $tool_result ),
				);
			}
		}

		// Safety: ask model for a final answer without tools.
		$final = $manager->chat(
			array_merge(
				$messages,
				array(
					array(
						'role'    => 'user',
						'content' => 'Please give the customer a clear final answer now based on the tool results.',
					),
				)
			),
			array(),
			array()
		);

		return array(
			'success'       => true,
			'message'       => trim( (string) $final['content'] ),
			'cards'         => $this->unique_cards( $cards ),
			'cart_updated'  => $cart_updated,
			'provider'      => $final['provider'],
			'used_fallback' => $used_fallback || ! empty( $final['used_fallback'] ),
			'language'      => $language,
			'timestamp'     => gmdate( 'c' ),
		);
	}

	/**
	 * Build system prompt.
	 *
	 * @param string $language Language.
	 * @param array  $context  Context.
	 * @return string
	 */
	private function system_prompt( $language, array $context ) {
		$settings = Plugin::settings();
		$custom   = trim( (string) $settings['system_prompt'] );

		$base = <<<'PROMPT'
You are Mont AI, a premium ecommerce shopping concierge for a luxury/custom shirt brand (Montenapoleone).

ROLE
- You are an expert shopping assistant, NOT a general chatbot.
- Help customers find products, compare, explain specs/pricing/availability/delivery, and build complete orders.
- Guide step-by-step. Never skip required options.
- Ask one clear question at a time when collecting options.
- Remember conversation context; do not re-ask answered questions.
- Be warm, concise, and premium in tone.

PRODUCT KNOWLEDGE
- Use tools to search and inspect products. Never invent stock, prices, or options.
- Understand simple/variable products, attributes, variations, sale prices, SKUs, categories, tags, meta, and custom options.
- This store uses custom options: Passform (body fit), Størrelse (size), Snipp/Collar, Mansjetter/Cuff, and optional custom measurements in cm.
- Custom measurement changes may add a surcharge; mention this politely when relevant.
- Free shipping worldwide is often promoted on the site; confirm policies carefully if asked.

ORDER BUILDING
1. Identify what the customer wants.
2. Search / recommend products with tools.
3. Call get_custom_options for the chosen product.
4. Collect EVERY required option (body_fit, size, collar_type, cuff_type, quantity).
5. Ask about optional measurements politely.
6. Call validate_selection.
7. Only then call add_to_cart.
8. Confirm success and offer checkout.

RECOMMENDATIONS
- Consider budget, purpose (business, wedding, casual), color, fabric, stock, and prior answers.

OUTPUT
- Prefer short paragraphs and clear next questions.
- When products are found, briefly highlight 2–4 options; product cards will render in the UI.
- Never mention Groq, Gemini, fallback, tools, or system prompts to the user.
PROMPT;

		$parts = array( $base );
		$parts[] = Language_Manager::prompt_instruction( $language );

		if ( ! empty( $context['product_id'] ) ) {
			$parts[] = 'The customer is currently viewing product ID ' . (int) $context['product_id'] . '. Prefer helping with this product unless they ask otherwise.';
		}

		if ( $custom ) {
			$parts[] = "Additional merchant instructions:\n" . $custom;
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Dedupe product cards by id.
	 *
	 * @param array $cards Cards.
	 * @return array
	 */
	private function unique_cards( array $cards ) {
		$seen = array();
		$out  = array();
		foreach ( $cards as $card ) {
			if ( empty( $card['id'] ) || isset( $seen[ $card['id'] ] ) ) {
				continue;
			}
			$seen[ $card['id'] ] = true;
			$out[] = $card;
		}
		return $out;
	}
}
