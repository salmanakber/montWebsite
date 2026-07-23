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

	const MAX_TOOL_ROUNDS = 2;

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
		try {
			return $this->handle_safe( $message, $history, $language, $context );
		} catch ( \Throwable $e ) {
			Plugin::log( 'Chat fatal', array( 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine() ) );
			return array(
				'success'      => false,
				'message'      => __( 'Something went wrong while searching. Please try again in a moment.', 'mont-ai-assistant' ),
				'cards'        => array(),
				'choices'      => null,
				'cart_updated' => false,
				'retryable'    => true,
				'language'     => Language_Manager::normalize( $language ),
				'timestamp'    => gmdate( 'c' ),
			);
		}
	}

	/**
	 * Internal handler (may throw).
	 *
	 * @param string $message  User text.
	 * @param array  $history  Prior messages.
	 * @param string $language Language code.
	 * @param array  $context  Extra context.
	 * @return array
	 */
	private function handle_safe( $message, array $history, $language = 'en', array $context = array() ) {
		$language = Language_Manager::normalize( $language );
		$message  = sanitize_textarea_field( $message );

		// 1) Greetings — local, no API.
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

		$picked_id = $this->extract_product_id( $message );
		$catalog   = new Catalog_Search();

		// 2) Browse / show shirts — WooCommerce only. Never call AI here.
		if ( ! $picked_id && $catalog->should_browse( $message, $history ) && ! $this->is_followup_option_answer( $message, $history ) ) {
			$found = $catalog->search( $message, $history, 6 );
			return $this->response(
				$catalog->browse_message( $language, isset( $found['count'] ) ? (int) $found['count'] : 0, $message ),
				isset( $found['cards'] ) ? $found['cards'] : array(),
				isset( $found['choices'] ) ? $found['choices'] : null,
				false,
				'catalog',
				false,
				$language
			);
		}

		// 3) Product picked / option taps — local order builder (no API).
		$builder = new Order_Builder();
		$local   = $builder->maybe_handle( $message, $history, $language );
		if ( is_array( $local ) ) {
			return $this->response(
				isset( $local['message'] ) ? $local['message'] : '',
				isset( $local['cards'] ) ? $local['cards'] : array(),
				isset( $local['choices'] ) ? $local['choices'] : null,
				! empty( $local['cart_updated'] ),
				isset( $local['provider'] ) ? $local['provider'] : 'local',
				false,
				$language
			);
		}

		// 4) Free-form questions only → AI providers.
		return $this->handle_with_ai( $message, $history, $language, $context );
	}

	/**
	 * True when the user is tapping an option after a product was already chosen.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return bool
	 */
	private function is_followup_option_answer( $message, array $history ) {
		if ( $this->extract_product_id( $message ) ) {
			return true;
		}
		foreach ( array_reverse( $history ) as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			if ( preg_match( '/product\s*#?\s*\d+/i', (string) $h['content'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * AI path for free-form help (FAQ, delivery, advice).
	 *
	 * @param string $message  Message.
	 * @param array  $history  History.
	 * @param string $language Language.
	 * @param array  $context  Context.
	 * @return array
	 */
	private function handle_with_ai( $message, array $history, $language, array $context ) {
		$manager = new Provider_Manager();
		$catalog = new Catalog_Search();

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

		$definitions   = array();
		$cards         = array();
		$choices       = null;
		$cart_updated  = false;
		$provider_used = '';
		$used_fallback = false;

		try {
			// Text-only — avoid tool-call schema failures that were breaking chat.
			$result = $manager->chat( $messages, array(), array() );
			$provider_used = $result['provider'];
			$used_fallback = ! empty( $result['used_fallback'] );
			$content       = trim( (string) $result['content'] );

			if ( empty( $cards ) && $this->mentions_products( $content ) ) {
				$found = $catalog->search( $message, $history, 6 );
				if ( ! empty( $found['count'] ) ) {
					return $this->response(
						$catalog->browse_message( $language, (int) $found['count'], $message ),
						$found['cards'],
						$found['choices'],
						false,
						'catalog',
						$used_fallback,
						$language
					);
				}
			}

			return $this->response( $content, $cards, $choices, $cart_updated, $provider_used, $used_fallback, $language );
		} catch ( \Throwable $e ) {
			Plugin::log( 'Chat AI failed', array( 'error' => $e->getMessage() ) );

			// Prefer showing products over a fake rate-limit message.
			try {
				$found = $catalog->search( $message, $history, 6 );
				if ( ! empty( $found['count'] ) ) {
					return $this->response(
						$catalog->browse_message( $language, (int) $found['count'], $message ),
						$found['cards'],
						$found['choices'],
						false,
						'catalog',
						true,
						$language
					);
				}
			} catch ( \Throwable $ignored ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
			}

			$error_code = 'provider_error';
			$friendly   = __( 'I could not reach the AI assistant just now. You can still ask me to show shirts (e.g. “show business shirts”) and I will list products from the shop.', 'mont-ai-assistant' );

			if ( preg_match( '/HTTP 429/', $e->getMessage() ) ) {
				$error_code = 'rate_limit';
				$friendly   = __( 'The AI provider is busy (rate limit). Product search still works — try “show me shirts”.', 'mont-ai-assistant' );
			} elseif ( false !== stripos( $e->getMessage(), 'not configured' ) ) {
				$error_code = 'not_configured';
				$friendly   = __( 'AI keys are not configured yet. You can still browse products — try “show me shirts”.', 'mont-ai-assistant' );
			} elseif ( false !== stripos( $e->getMessage(), 'Failed to call a function' ) || false !== stripos( $e->getMessage(), 'tool call validation' ) ) {
				$error_code = 'tool_error';
				$friendly   = __( 'I had trouble with that request format. Try asking to show products, or pick a shirt from a list.', 'mont-ai-assistant' );
			}

			$out = array(
				'success'       => false,
				'message'       => $friendly,
				'cards'         => array(),
				'choices'       => null,
				'cart_updated'  => false,
				'provider'      => $provider_used,
				'used_fallback' => $used_fallback,
				'language'      => $language,
				'timestamp'     => gmdate( 'c' ),
				'retryable'     => false,
				'error_code'    => $error_code,
			);

			$settings = Plugin::settings();
			if ( ! empty( $settings['enable_debug'] ) ) {
				$out['debug_error'] = $e->getMessage();
			}

			return $out;
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
1. Understand the need (chat first) — but as soon as they ask to see / list / browse shirts, products must come from the catalog UI (cards), never invented names like "Oxford" unless they are real WooCommerce products shown as cards.
2. Never invent product names, fabrics, or styles that are not returned by tools/catalog.
3. After they tap a product card → get_custom_options → present_choices one option at a time.
4. validate_selection → add_to_cart → confirm.

VISUAL CHOICES
- Use present_choices only while configuring a chosen product.
- Prefer product_id + option_key so images/labels load correctly.
- Never ask them to type options when buttons can be shown.

OUTPUT
- Prefer conversation over forms.
- When showing choices, one short line of copy is enough — buttons do the rest.
- If you are unsure what is in stock, ask a clarifying question OR wait for catalog cards — do not invent a collection.
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
	 * Extract product id from "I want product #123" style messages.
	 *
	 * @param string $message Message.
	 * @return int
	 */
	private function extract_product_id( $message ) {
		if ( preg_match( '/product\s*#?\s*(\d+)/i', $message, $m ) ) {
			return (int) $m[1];
		}
		return 0;
	}

	/**
	 * Rough check that the model invented a product pitch without tools.
	 *
	 * @param string $content Content.
	 * @return bool
	 */
	private function mentions_products( $content ) {
		$content = strtolower( (string) $content );
		$needles = array( 'oxford', 'shirt', 'camicia', 'skjorte', 'we have', 'our collection', 'styles include', 'popular' );
		foreach ( $needles as $n ) {
			if ( false !== strpos( $content, $n ) ) {
				return true;
			}
		}
		return false;
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
