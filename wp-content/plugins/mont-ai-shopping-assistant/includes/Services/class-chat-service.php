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
 */
class Chat_Service {

	const MAX_TOOL_ROUNDS = 4;

	/**
	 * Handle a user message.
	 *
	 * @param string $message  User text.
	 * @param array  $history  Prior messages.
	 * @param string $language Language code.
	 * @param array  $context  Extra context.
	 * @return array
	 */
	public function handle( $message, array $history, $language = 'en', array $context = array() ) {
		$language = Language_Manager::normalize( $language );
		$tools    = new Tool_Executor();
		$manager  = new Provider_Manager();

		$messages   = array();
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

		$definitions  = $tools->definitions();
		$cards        = array();
		$choices      = null;
		$cart_updated = false;
		$provider_used = '';
		$used_fallback = false;

		try {
			for ( $round = 0; $round < self::MAX_TOOL_ROUNDS; $round++ ) {
				if ( $round > 0 ) {
					// Small pause to reduce Groq/Gemini 429 rate limits during tool loops.
					usleep( 350000 );
				}

				$result = $manager->chat( $messages, $definitions, array() );
				$provider_used = $result['provider'];
				$used_fallback = ! empty( $result['used_fallback'] ) || $used_fallback;

				$tool_calls = $result['tool_calls'];
				$content    = trim( (string) $result['content'] );

				if ( empty( $tool_calls ) ) {
					return $this->response( $content, $cards, $choices, $cart_updated, $provider_used, $used_fallback, $language );
				}

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
					if ( ! empty( $tool_result['choices'] ) && is_array( $tool_result['choices'] ) ) {
						$choices = $tool_result['choices'];
					}
					if ( ! empty( $tool_result['cart_updated'] ) ) {
						$cart_updated = true;
					}

					// If present_choices ran, we can stop after this round with a short message.
					$stop_after_choices = ( 'present_choices' === $fn_name && ! empty( $tool_result['choices'] ) );

					$messages[] = array(
						'role'         => 'tool',
						'tool_call_id' => isset( $tc['id'] ) ? $tc['id'] : '',
						'name'         => $fn_name,
						'content'      => wp_json_encode( $tool_result ),
					);

					if ( $stop_after_choices ) {
						$msg = $content ? $content : ( isset( $choices['title'] ) ? $choices['title'] : 'Please choose an option:' );
						return $this->response( $msg, $cards, $choices, $cart_updated, $provider_used, $used_fallback, $language );
					}
				}
			}

			$final = $manager->chat(
				array_merge(
					$messages,
					array(
						array(
							'role'    => 'user',
							'content' => 'Give the customer a short final reply. If they still need to choose an option, say so briefly — buttons may already be on screen.',
						),
					)
				),
				array(),
				array()
			);

			return $this->response(
				trim( (string) $final['content'] ),
				$cards,
				$choices,
				$cart_updated,
				$final['provider'],
				$used_fallback || ! empty( $final['used_fallback'] ),
				$language
			);
		} catch ( \Exception $e ) {
			Plugin::log( 'Chat failed', array( 'error' => $e->getMessage() ) );

			$friendly = __( 'I am a bit busy right now (high demand). Please tap send again in a moment.', 'mont-ai-assistant' );
			if ( false !== stripos( $e->getMessage(), '429' ) ) {
				$friendly = __( 'The assistant is temporarily rate-limited. Please wait a few seconds and try again.', 'mont-ai-assistant' );
			}

			return array(
				'success'       => false,
				'message'       => $friendly,
				'cards'         => $this->unique_cards( $cards ),
				'choices'       => $choices,
				'cart_updated'  => $cart_updated,
				'provider'      => $provider_used,
				'used_fallback' => $used_fallback,
				'language'      => $language,
				'timestamp'     => gmdate( 'c' ),
				'retryable'     => true,
			);
		}
	}

	/**
	 * Standard response payload.
	 *
	 * @param string     $message Message.
	 * @param array      $cards Cards.
	 * @param array|null $choices Choices UI.
	 * @param bool       $cart_updated Cart flag.
	 * @param string     $provider Provider.
	 * @param bool       $used_fallback Fallback flag.
	 * @param string     $language Language.
	 * @return array
	 */
	private function response( $message, array $cards, $choices, $cart_updated, $provider, $used_fallback, $language ) {
		return array(
			'success'       => true,
			'message'       => $message,
			'cards'         => $this->unique_cards( $cards ),
			'choices'       => $choices,
			'cart_updated'  => $cart_updated,
			'provider'      => $provider,
			'used_fallback' => $used_fallback,
			'language'      => $language,
			'timestamp'     => gmdate( 'c' ),
		);
	}

	/**
	 * System prompt.
	 *
	 * @param string $language Language.
	 * @param array  $context  Context.
	 * @return string
	 */
	private function system_prompt( $language, array $context ) {
		$settings = Plugin::settings();
		$custom   = trim( (string) $settings['system_prompt'] );

		$base = <<<'PROMPT'
You are Mont AI, a premium ecommerce shopping concierge for Montenapoleone (custom shirts).

ROLE
- Expert shopping assistant — not a general chatbot.
- Warm, concise, premium tone.
- Never invent stock, prices, or options — use tools.
- Do not re-ask answered questions.
- Never mention Groq, Gemini, tools, or system prompts.

VISUAL CHOICES (CRITICAL)
- NEVER ask the customer to type size, fit, collar, cuff, quantity, or product names when buttons can be shown.
- When the customer must pick something, ALWAYS call present_choices so tappable buttons/images appear.
- Prefer present_choices with product_id + option_key (body_fit, size, collar_type, cuff_type).
- Ask ONE option at a time.
- After search_products, products appear as cards — briefly introduce them; customer can tap a card.

ORDER FLOW
1. Understand need → search_products
2. Customer picks a product (card tap or message with product #id)
3. get_custom_options for that product
4. present_choices for each required option one-by-one (fit → size → collar → cuff → quantity)
5. validate_selection with flat fields
6. add_to_cart
7. Confirm + offer checkout

CUSTOM OPTIONS
- Passform / body_fit, Størrelse / size, Snipp/collar_type, Mansjetter/cuff_type
- Optional custom measurements in cm (ask politely after required options)
- Measurement changes may add a small surcharge

OUTPUT
- Short paragraphs.
- When presenting choices, one sentence + the UI buttons handle the rest.
PROMPT;

		$parts   = array( $base );
		$parts[] = Language_Manager::prompt_instruction( $language );

		if ( ! empty( $context['product_id'] ) ) {
			$parts[] = 'Customer is viewing product ID ' . (int) $context['product_id'] . '. Prefer helping with this product unless they ask otherwise. You may call get_custom_options and present_choices for it immediately.';
		}

		if ( $custom ) {
			$parts[] = "Additional merchant instructions:\n" . $custom;
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Dedupe cards.
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
