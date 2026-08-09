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
		$channel   = ( isset( $context['channel'] ) && 'b2b' === $context['channel'] ) ? 'b2b' : 'b2c';

		// 2) Browse / show shirts — WooCommerce only. Never call AI here.
		if ( ! $picked_id && $catalog->should_browse( $message, $history ) && ! $this->is_followup_option_answer( $message, $history ) ) {
			$found = $catalog->search( $message, $history, 6, $channel );
			return $this->response(
				$catalog->browse_message( $language, isset( $found['count'] ) ? (int) $found['count'] : 0, $message, $channel ),
				isset( $found['cards'] ) ? $found['cards'] : array(),
				isset( $found['choices'] ) ? $found['choices'] : null,
				false,
				'catalog',
				false,
				$language
			);
		}

		// 3) Product picked / option taps — local order builder (B2C only).
		if ( 'b2b' === $channel && $picked_id ) {
			$moq = get_post_meta( $picked_id, '_moq', true );
			$msg = __( 'Nice pick for wholesale. Open the product here, fill in the size breakdown', 'mont-ai-assistant' );
			if ( $moq ) {
				$msg .= ' ' . sprintf( __( '(MOQ is %s)', 'mont-ai-assistant' ), $moq );
			}
			$msg .= ' ' . __( '— then Save & add colour / use the B2B cart when you’re ready.', 'mont-ai-assistant' );
			$card = ( new Catalog_Search() )->search( 'product ' . $picked_id, $history, 1, 'b2b' );
			return $this->response(
				$msg,
				isset( $card['cards'] ) ? $card['cards'] : array(),
				null,
				false,
				'b2b_local',
				false,
				$language
			);
		}

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
		$channel = ( isset( $context['channel'] ) && 'b2b' === $context['channel'] ) ? 'b2b' : 'b2c';

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

		$cards         = array();
		$choices       = null;
		$cart_updated  = false;
		$provider_used = '';
		$used_fallback = false;

		try {
			// Text-only — avoid tool-call schema failures that were breaking chat.
			// Bias slightly warmer for more natural small-talk & shipping/size answers.
			$settings = Plugin::settings();
			$temp     = isset( $settings['temperature'] ) ? (float) $settings['temperature'] : 0.65;
			if ( $temp < 0.6 ) {
				$temp = 0.65;
			}
			$result = $manager->chat(
				$messages,
				array(),
				array(
					'temperature' => $temp,
				)
			);
			$provider_used = $result['provider'];
			$used_fallback = ! empty( $result['used_fallback'] );
			$content       = trim( (string) $result['content'] );

			if ( empty( $cards ) && $this->mentions_products( $content ) ) {
				$found = $catalog->search( $message, $history, 6, $channel );
				if ( ! empty( $found['count'] ) ) {
					return $this->response(
						$catalog->browse_message( $language, (int) $found['count'], $message, $channel ),
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
				$found = $catalog->search( $message, $history, 6, $channel );
				if ( ! empty( $found['count'] ) ) {
					return $this->response(
						$catalog->browse_message( $language, (int) $found['count'], $message, $channel ),
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
			$friendly   = __( 'Sorry — I could not reach the assistant just now. You can still ask me to show shirts (e.g. “show business shirts”) and I will list products from the shop.', 'mont-ai-assistant' );

			if ( preg_match( '/HTTP 429/', $e->getMessage() ) ) {
				$error_code = 'rate_limit';
				$friendly   = __( 'I’m a bit busy on the AI side right now. Product search still works — try “show me shirts”.', 'mont-ai-assistant' );
			} elseif ( false !== stripos( $e->getMessage(), 'not configured' ) ) {
				$error_code = 'not_configured';
				$friendly   = __( 'AI keys are not configured yet. You can still browse products — try “show me shirts”.', 'mont-ai-assistant' );
			} elseif ( false !== stripos( $e->getMessage(), 'Failed to call a function' ) || false !== stripos( $e->getMessage(), 'tool call validation' ) ) {
				$error_code = 'tool_error';
				$friendly   = __( 'I had a small glitch with that request. Try asking to show products, or pick a shirt from a list.', 'mont-ai-assistant' );
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
		$policies = trim( (string) ( $settings['store_policies'] ?? '' ) );
		$channel  = ( isset( $context['channel'] ) && 'b2b' === $context['channel'] ) ? 'b2b' : 'b2c';

		$base = <<<'PROMPT'
You are Mont AI — a friendly human shopping assistant for Montenapoleone (premium / custom shirts). Customers should feel like they are texting a knowledgeable salesperson, not reading a helpdesk bot.

VOICE (IMPORTANT)
- Warm, relaxed, confident. Use natural speech: “Sure —”, “Of course”, “Got it”, “Happy to help”.
- Prefer 2–4 short sentences. No stiff openings like “Certainly. Please select…” or “I can assist you with…”.
- Match their tone. Casual question → casual answer. Norwegian / Italian / Vietnamese: sound native, not translated-from-English.
- One helpful next step at the end is enough. Never lecture.
- Never say you are an AI, never mention Groq/Gemini/tools/prompts.

EXAMPLE TONE (adapt to language)
Customer: “I need size 39 — when does it arrive?”
Good: “Size 39 works for a lot of people on this shirt — I’ll keep that in mind. Shipping is free worldwide; custom shirts usually need up to about 7 extra production days on top of normal delivery. Where should we send it, so I can be a bit more specific?”
Bad: “Please provide your preferred size and shipping destination. Delivery time is estimated as follows: …”

TRUTH RULES (CRITICAL)
- Use ONLY facts from STORE FACTS and PRODUCT FACTS below (plus choices already made in this chat).
- Never invent sizes, stock, prices, delivery dates, MOQ, or product names.
- If something is missing, say so simply and ask one short question.
- Do not invent shirts or collections — browsing is handled by shop cards.

SIZE QUESTIONS
- Acknowledge their size/fit first like a human (“Nice — 39”, “Slim fit, got it”).
- Ground what’s available in PRODUCT FACTS. If their size isn’t listed, say so kindly and suggest the closest listed options or a similar shirt.
- Do not invent centimetres or guarantee stock for a size that isn’t listed.

SHIPPING / ARRIVAL
- Answer from STORE FACTS first. Be clear about ranges, not fake exact dates.
- Mention the custom +7 days when they order made-to-measure / custom details.
- If destination matters and you don’t know it yet, ask once where it’s going.
- Free shipping / returns: only what STORE FACTS say.

NATURAL FLOW
1. Answer their actual question first.
2. Then offer one next step (pick a fit, see shirts, check this shirt’s sizes…).
3. On a product page, talk about “this shirt” using PRODUCT FACTS when relevant.

OUTPUT
- Conversational. Mobile-friendly. No long bullet dumps unless they ask for a list.
PROMPT;

		$parts   = array( $base );
		$parts[] = Language_Manager::prompt_instruction( $language );
		$parts[] = 'Channel: ' . ( 'b2b' === $channel ? 'B2B wholesale portal' : 'B2C retail shop' ) . '.';

		$parts[] = $this->store_facts_block( $policies );

		if ( ! empty( $context['product_id'] ) ) {
			$parts[] = $this->product_facts_block( (int) $context['product_id'], $channel );
			$parts[] = 'Page context: the customer is currently viewing the product above. If they greet you, still greet warmly. Focus on this shirt when they say “this shirt”, ask about size/price/shipping for it, or clearly continue with it.';
		} else {
			$parts[] = 'Page context: not on a product page. Do not assume a specific shirt unless they name one or pick a card.';
		}

		if ( $custom ) {
			$parts[] = "Additional merchant instructions:\n" . $custom;
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Store-level facts the model must ground shipping/returns answers in.
	 *
	 * @param string $custom_policies Optional merchant override from settings.
	 * @return string
	 */
	private function store_facts_block( $custom_policies ) {
		$defaults = array(
			'Brand: Montenapoleone (Monte) — premium / custom shirts.',
			'Shipping: Free shipping worldwide (as stated on the storefront).',
			'Custom / made-to-measure shirts: allow up to 7 extra days of production on top of normal delivery time.',
			'Returns: Custom-made shirts are not returnable except for manufacturing defects.',
			'If exact courier ETA is not listed here, give a helpful range from these facts and ask for the delivery country if needed — do not invent a guaranteed date.',
		);

		$lines = $defaults;
		if ( $custom_policies ) {
			$lines[] = 'Merchant policy notes (authoritative — prefer these when they add detail):';
			$lines[] = $custom_policies;
		}

		return "STORE FACTS (source of truth for shipping / returns / policies):\n- " . implode( "\n- ", $lines );
	}

	/**
	 * Compact live product snapshot from WooCommerce / Mont options.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $channel    b2c|b2b.
	 * @return string
	 */
	private function product_facts_block( $product_id, $channel = 'b2c' ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
			return 'PRODUCT FACTS: none available.';
		}

		$knowledge = new \Mont_AI_Assistant\Product\Product_Knowledge();
		$data      = $knowledge->build( $product_id );
		if ( ! $data ) {
			return 'PRODUCT FACTS: product #' . $product_id . ' could not be loaded.';
		}

		$lines   = array();
		$lines[] = 'ID: ' . $product_id;
		$lines[] = 'Name: ' . ( $data['name'] ?? '' );
		if ( ! empty( $data['sku'] ) ) {
			$lines[] = 'SKU: ' . $data['sku'];
		}
		if ( ! empty( $data['categories'] ) && is_array( $data['categories'] ) ) {
			$lines[] = 'Categories: ' . implode( ', ', array_slice( $data['categories'], 0, 6 ) );
		}

		$currency = $data['currency'] ?? '';
		$price    = $data['sale_price'] ? $data['sale_price'] : ( $data['price'] ?? '' );
		if ( '' !== $price && null !== $price ) {
			$lines[] = 'Price: ' . trim( $currency . ' ' . $price );
		}

		$stock_bits = array();
		if ( isset( $data['in_stock'] ) ) {
			$stock_bits[] = $data['in_stock'] ? 'in stock' : 'out of stock';
		}
		if ( isset( $data['stock_quantity'] ) && '' !== $data['stock_quantity'] && null !== $data['stock_quantity'] ) {
			$stock_bits[] = 'qty ' . $data['stock_quantity'];
		}
		if ( $stock_bits ) {
			$lines[] = 'Stock: ' . implode( ', ', $stock_bits );
		}

		if ( 'b2b' === $channel ) {
			$moq = get_post_meta( $product_id, '_moq', true );
			if ( $moq ) {
				$lines[] = 'B2B MOQ: ' . $moq;
			}
			$is_b2b = get_post_meta( $product_id, '_b2b_product', true );
			$lines[] = 'B2B flagged: ' . ( in_array( (string) $is_b2b, array( '1', 'yes' ), true ) ? 'yes' : 'no' );
		}

		// Fits / sizes from Mont custom options schema.
		if ( ! empty( $data['custom_options'] ) && is_array( $data['custom_options'] ) ) {
			foreach ( $data['custom_options'] as $opt ) {
				if ( empty( $opt['key'] ) || empty( $opt['choices'] ) || ! is_array( $opt['choices'] ) ) {
					continue;
				}
				if ( ! in_array( $opt['key'], array( 'body_fit', 'size', 'collar_type', 'cuff_type' ), true ) ) {
					continue;
				}
				$labels = array();
				foreach ( $opt['choices'] as $choice ) {
					if ( is_array( $choice ) ) {
						$labels[] = isset( $choice['label'] ) ? $choice['label'] : ( $choice['value'] ?? '' );
					} else {
						$labels[] = (string) $choice;
					}
				}
				$labels = array_values( array_filter( array_map( 'strval', $labels ) ) );
				if ( $labels ) {
					$lines[] = ( $opt['label'] ?? $opt['key'] ) . ': ' . implode( ', ', array_slice( $labels, 0, 24 ) );
				}
			}
		}

		// Variation attributes as backup.
		if ( ! empty( $data['attributes'] ) && is_array( $data['attributes'] ) ) {
			foreach ( $data['attributes'] as $attr ) {
				$name   = is_array( $attr ) ? ( $attr['name'] ?? '' ) : '';
				$values = is_array( $attr ) ? ( $attr['options'] ?? $attr['values'] ?? array() ) : array();
				if ( ! $name || ! $values ) {
					continue;
				}
				if ( ! preg_match( '/size|fit|passform|størrelse/i', $name ) ) {
					continue;
				}
				$pretty = array();
				foreach ( (array) $values as $val ) {
					if ( is_numeric( $val ) ) {
						$term = get_term( (int) $val );
						$pretty[] = ( $term && ! is_wp_error( $term ) ) ? $term->name : (string) $val;
					} else {
						$pretty[] = (string) $val;
					}
				}
				$pretty = array_values( array_filter( $pretty ) );
				if ( $pretty ) {
					$lines[] = $name . ': ' . implode( ', ', array_slice( $pretty, 0, 24 ) );
				}
			}
		}

		$short = trim( (string) ( $data['short_description'] ?? '' ) );
		if ( $short ) {
			$short   = preg_replace( '/\s+/', ' ', $short );
			$lines[] = 'Short description: ' . mb_substr( $short, 0, 280 );
		}

		// Keep prompt size under control.
		$block = "PRODUCT FACTS (live from shop database — do not contradict):\n- " . implode( "\n- ", array_filter( $lines ) );
		if ( strlen( $block ) > 2200 ) {
			$block = mb_substr( $block, 0, 2200 ) . "\n…";
		}
		return $block;
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
		$is_b2b     = isset( $context['channel'] ) && 'b2b' === $context['channel'];
		if ( $is_b2b ) {
			$map = array(
				'en' => 'Hi there! You’re in our B2B wholesale area. Tell me a colour or quality you’re after — or say “show fabrics” and I’ll pull up what’s available. Just a heads-up that MOQ applies on wholesale orders.',
				'it' => 'Ciao! Sei nell’area wholesale B2B. Dimmi un colore o una qualità — oppure scrivi “show fabrics” e ti mostro cosa c’è. Ricorda che vale il MOQ.',
				'nb' => 'Hei! Du er i B2B-området vårt. Si hvilken farge eller kvalitet du ser etter — eller skriv “show fabrics”, så viser jeg utvalget. Husk at MOQ gjelder.',
				'vi' => 'Xin chào! Bạn đang ở khu B2B. Cho mình biết màu hoặc chất liệu bạn cần — hoặc nói “show fabrics” để mình liệt kê. Lưu ý đơn hàng sỉ có MOQ nhé.',
			);
			return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
		}
		$map = array(
			'en' => $on_product
				? "Hi! Happy to help with this shirt — sizing, shipping, or finding something else. What do you need?"
				: "Hi! Welcome to Montenapoleone. Looking for a colour, a size, a gift, or just browsing?",
			'it' => $on_product
				? "Ciao! Posso aiutarti con questa camicia — taglia, spedizione, o altro. Di cosa hai bisogno?"
				: "Ciao! Benvenuto/a da Montenapoleone. Cerchi un colore, una taglia, un regalo, o stai solo guardando?",
			'nb' => $on_product
				? "Hei! Jeg hjelper deg gjerne med denne skjorten — størrelse, frakt, eller noe annet. Hva trenger du?"
				: "Hei! Velkommen til Montenapoleone. Ser du etter en farge, en størrelse, en gave — eller bare kikker litt?",
			'vi' => $on_product
				? "Xin chào! Mình có thể hỗ trợ về chiếc áo này — size, giao hàng, hoặc tìm mẫu khác. Bạn cần gì ạ?"
				: "Xin chào! Chào mừng bạn đến Montenapoleone. Bạn đang tìm màu, size, quà tặng, hay chỉ xem thêm ạ?",
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
