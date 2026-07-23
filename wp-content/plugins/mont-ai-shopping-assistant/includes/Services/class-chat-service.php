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
		$message  = sanitize_textarea_field( $message );

		// Fast path: greetings / small talk — no tools, no API burn.
		if ( $this->is_simple_greeting( $message ) ) {
			return $this->response(
				$this->greeting_reply( $language, $context ),
				array(),
				null,
				false,
				'local',
				false,
				$language
			);
		}

		$tools   = new Tool_Executor();
		$manager = new Provider_Manager();

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
			'content' => $message,
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

					// Only surface choice buttons from explicit present_choices,
					// product search, or failed validate/add_to_cart — never from get_custom_options alone.
					$allow_choices = in_array(
						$fn_name,
						array( 'present_choices', 'search_products', 'validate_selection', 'add_to_cart' ),
						true
					);
					if ( $allow_choices && ! empty( $tool_result['choices'] ) && is_array( $tool_result['choices'] ) ) {
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
You are Mont AI — a warm, expert sales concierge for Montenapoleone (premium / custom shirts).

PERSONALITY
- Sound like a helpful in-store salesperson: friendly, calm, never pushy.
- Short natural sentences. One question at a time.
- Never invent stock, prices, or options — use tools when needed.
- Never mention Groq, Gemini, tools, or system prompts.

GREETINGS & SMALL TALK (CRITICAL)
- If the customer says hi/hello/hey/thanks or anything without a product need:
  greet them warmly and ask what they are looking for (e.g. colour, occasion, linen shirt, gift…).
- Do NOT call any tools on a bare greeting.
- Do NOT show size, quantity, collar, cuff, or fit buttons until they have a clear need AND a product is chosen.
- Do NOT jump into order building on "hi".

NATURAL SALES FLOW
1. Understand the need (chat first).
2. When they describe a product need → search_products.
3. Let them pick a product from cards.
4. Then get_custom_options.
5. Then present_choices ONE option at a time (fit → size → collar → cuff → quantity).
6. validate_selection → add_to_cart → confirm.

VISUAL CHOICES
- Use present_choices only while configuring a chosen product.
- Prefer product_id + option_key so images/labels load correctly.
- Never ask them to type options when buttons can be shown.

OUTPUT
- Prefer conversation over forms.
- When showing choices, one short line of copy is enough — buttons do the rest.
PROMPT;

		$parts   = array( $base );
		$parts[] = Language_Manager::prompt_instruction( $language );

		if ( ! empty( $context['product_id'] ) ) {
			$parts[] = 'Context only (do not force it): the browser is on product ID ' . (int) $context['product_id'] . '. If they greet you, still greet and ask what they need. Only focus on this product if they ask about "this shirt/product", "add this", or clearly continue configuring it.';
		} else {
			$parts[] = 'The customer is not on a product page. Start with discovery — never assume a product or quantity.';
		}

		if ( $custom ) {
			$parts[] = "Additional merchant instructions:\n" . $custom;
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Detect bare greetings / small talk with no shopping intent.
	 *
	 * @param string $message Message.
	 * @return bool
	 */
	private function is_simple_greeting( $message ) {
		$text = strtolower( trim( $message ) );
		$text = preg_replace( '/[!?.…]+$/u', '', $text );
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return false;
		}
		// Keep short — longer messages likely have intent.
		if ( str_word_count( $text ) > 6 ) {
			return false;
		}
		$greetings = array(
			'hi', 'hello', 'hey', 'hiya', 'yo', 'hola', 'ciao', 'salut',
			'hei', 'hallo', 'god dag', 'goddag', 'xin chao', 'chào', 'chao',
			'good morning', 'good afternoon', 'good evening', 'good day',
			'thanks', 'thank you', 'takk', 'grazie', 'cảm ơn', 'cam on',
			'how are you', 'whats up', "what's up", 'help', 'start',
		);
		foreach ( $greetings as $g ) {
			if ( $text === $g || 0 === strpos( $text, $g . ' ' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Local greeting copy by language.
	 *
	 * @param string $language Language.
	 * @param array  $context  Context.
	 * @return string
	 */
	private function greeting_reply( $language, array $context ) {
		$on_product = ! empty( $context['product_id'] );
		$map = array(
			'en' => $on_product
				? "Hi! Welcome. I can help with this shirt, or find something else — what are you looking for today?"
				: "Hi! Welcome to Montenapoleone. What are you looking for today — a colour, an occasion, or a particular style of shirt?",
			'it' => $on_product
				? "Ciao! Posso aiutarti con questa camicia oppure trovare altro — cosa cerchi oggi?"
				: "Ciao! Benvenuto/a da Montenapoleone. Cosa stai cercando oggi — un colore, un'occasione o uno stile particolare?",
			'nb' => $on_product
				? "Hei! Jeg kan hjelpe deg med denne skjorten, eller finne noe annet — hva ser du etter i dag?"
				: "Hei! Velkommen til Montenapoleone. Hva ser du etter i dag — en farge, en anledning eller en bestemt stil?",
			'vi' => $on_product
				? "Xin chào! Tôi có thể hỗ trợ về chiếc áo này, hoặc tìm mẫu khác — bạn đang tìm gì hôm nay?"
				: "Xin chào! Chào mừng bạn đến Montenapoleone. Bạn đang tìm gì hôm nay — màu sắc, dịp mặc, hay một kiểu áo cụ thể?",
		);
		return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
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
