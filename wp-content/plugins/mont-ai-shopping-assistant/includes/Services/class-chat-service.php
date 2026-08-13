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
		$is_advice = $this->is_advice_question( $message );

		// 2a) “Show me THAT shirt” — one card, never a catalog dump.
		if ( ! $picked_id && $catalog->wants_specific_show( $message ) ) {
			return $this->show_focused_product( $message, $history, $language, $channel, $catalog );
		}

		// 2a2) Payment talk — never collect card details; show the shirt + send to checkout.
		if ( ! $picked_id && $this->is_payment_message( $message ) ) {
			$focus = $this->show_focused_product( $message, $history, $language, $channel, $catalog );
			$focus['message'] = $this->copy_lang(
				$language,
				'Visa, Vipps and PayPal are all fine — but I never take card details in chat. Here’s the shirt; tap Select to put it in the cart, then pay securely at checkout.',
				'Visa, Vipps og PayPal går fint — men jeg tar aldri kortdetaljer i chatten. Her er skjorten; trykk Velg, og betal trygt i kassen.',
				'Visa, Vipps e PayPal vanno tutti bene — ma non chiedo i dati della carta in chat. Ecco la camicia; tocca Seleziona e paga al checkout.',
				'Visa, Vipps và PayPal đều được — nhưng mình không lấy thông tin thẻ trong chat. Đây là áo; chạm Select rồi thanh toán ở checkout.'
			);
			return $focus;
		}

		// 2b) “Need a shirt / best one / show shirts” — 2–3 real products, pick a favourite.
		if ( ! $picked_id && ! $is_advice && ! $this->is_followup_option_answer( $message, $history )
			&& ( $catalog->should_browse( $message, $history ) || $catalog->wants_recommendation( $message ) || $this->is_shopping_pref_answer( $message, $history ) ) ) {
			$found = $catalog->recommend( $message, $history, 3, $channel );
			$cards = isset( $found['cards'] ) ? $found['cards'] : array();
			$rec   = ! empty( $found['recommended_id'] ) ? (int) $found['recommended_id'] : 0;
			return $this->response(
				$this->recommend_copy( $language, $cards, $found['prefs'] ?? array(), $catalog->wants_recommendation( $message ) ),
				$cards,
				null,
				false,
				'catalog',
				false,
				$language,
				$rec
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

		// Skip option/cart builder for shipping/size advice — answer naturally via AI.
		if ( ! $is_advice ) {
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
		}

		// 4) Free-form questions → natural AI answers grounded in store/product facts.
		return $this->handle_with_ai( $message, $history, $language, $context );
	}

	/**
	 * True for Q&A that should stay conversational (not catalog / not forced option steps).
	 *
	 * @param string $message Message.
	 * @return bool
	 */
	private function is_advice_question( $message ) {
		$text = strtolower( trim( (string) $message ) );
		if ( '' === $text ) {
			return false;
		}

		// Shipping / delivery / returns / timing.
		if ( preg_match( '/(ship|shipping|deliver|delivery|arrive|arrival|frakt|levering|leveringstid|n[åa]r\s+kommer|spedizione|consegna|quando\s+arriva|giao\s*h[aà]ng|khi\s+n[aà]o|return|retur|refund|how\s+long|when\s+will|track\s*order)/i', $text ) ) {
			return true;
		}

		// Real size QUESTIONS — not “slim fit” / “40” as a preference.
		if ( preg_match( '/(what size|which size|does it fit|size chart|hvilken st|che taglia|size guide)/i', $text ) ) {
			return true;
		}

		// Price / fabric / care questions (not “show me the shirt”).
		if ( preg_match( '/(how\s+much|what.?s the price|price of|material|fabric composition|care instructions|return\s+policy|can\s+i\s+return)/i', $text ) ) {
			return true;
		}

		return false;
	}

	/**
	 * True when the user is naming a payment method (never collect secrets).
	 *
	 * @param string $message Message.
	 * @return bool
	 */
	private function is_payment_message( $message ) {
		$text = strtolower( trim( (string) $message ) );
		if ( '' === $text ) {
			return false;
		}
		return (bool) preg_match( '/\b(visa|mastercard|paypal|vipps|credit\s*card|debit\s*card|pay\s*with|payment\s*method)\b/i', $text );
	}

	/**
	 * Short preference after they already asked for a shirt — show products, don’t interview.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return bool
	 */
	private function is_shopping_pref_answer( $message, array $history ) {
		$text = strtolower( trim( (string) $message ) );
		if ( ! preg_match( '/^(slim|slim\s*fit|classic|regular|casual|everyday|business|blue|light\s*blue|white|linen|oxford|flannel|\d{2}|idk|i don.?t know|not sure)$/i', $text ) ) {
			return false;
		}
		$blob = strtolower( $this->history_text( $history ) );
		return (bool) preg_match( '/(shirt|skjorte|camicia|browse|looking for|need)/i', $blob );
	}

	/**
	 * History as text.
	 *
	 * @param array $history History.
	 * @return string
	 */
	private function history_text( array $history ) {
		$parts = array();
		foreach ( $history as $h ) {
			if ( ! empty( $h['content'] ) ) {
				$parts[] = (string) $h['content'];
			}
		}
		return implode( ' ', $parts );
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
		$trim = trim( (string) $message );
		if ( ! preg_match( '/^(slim|regular|classic|extra\s*slim|\d{2}|xxs|xs|s|m|l|xl|xxl|1|2|3|5)$/i', $trim ) ) {
			return false;
		}
		foreach ( array_reverse( $history ) as $h ) {
			if ( empty( $h['content'] ) || ( isset( $h['role'] ) && 'assistant' === $h['role'] ) ) {
				continue;
			}
			if ( preg_match( '/I want product\s*#?\s*\d+/i', (string) $h['content'] ) ) {
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
		$sys        = $this->system_prompt( $language, $context );
		$sys       .= "\n\n" . $catalog->catalog_snapshot( $message, $history, $channel, 8 );
		$memory_ids = $catalog->remembered_product_ids( $history );
		if ( $memory_ids ) {
			$sys .= "\n\nAlready shown in this chat (if they ask for “the one you suggested”, call get_product on this id): product #" . implode( ', product #', $memory_ids );
		}
		$messages[] = array(
			'role'    => 'system',
			'content' => $sys,
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
		$rec_id        = 0;
		$executor      = new Tool_Executor();
		$tools         = $executor->definitions();

		try {
			$settings = Plugin::settings();
			$temp     = isset( $settings['temperature'] ) ? (float) $settings['temperature'] : 0.5;
			if ( $temp < 0.35 ) {
				$temp = 0.45;
			}
			if ( $temp > 0.7 ) {
				$temp = 0.55;
			}

			$content = '';
			for ( $round = 0; $round < self::MAX_TOOL_ROUNDS; $round++ ) {
				$result = $manager->chat(
					$messages,
					$tools,
					array(
						'temperature' => $temp,
					)
				);
				$provider_used = $result['provider'];
				$used_fallback = ! empty( $result['used_fallback'] );
				$tool_calls    = ! empty( $result['tool_calls'] ) && is_array( $result['tool_calls'] ) ? $result['tool_calls'] : array();

				if ( ! $tool_calls ) {
					$content = trim( (string) $result['content'] );
					break;
				}

				foreach ( $tool_calls as $i => $tc ) {
					if ( empty( $tc['id'] ) ) {
						$tool_calls[ $i ]['id'] = 'call_' . $round . '_' . $i;
					}
				}

				$messages[] = array(
					'role'       => 'assistant',
					'content'    => isset( $result['content'] ) ? $result['content'] : null,
					'tool_calls' => $tool_calls,
				);

				foreach ( $tool_calls as $i => $tc ) {
					$fname = isset( $tc['function']['name'] ) ? (string) $tc['function']['name'] : '';
					$fargs = array();
					if ( ! empty( $tc['function']['arguments'] ) ) {
						$decoded = json_decode( (string) $tc['function']['arguments'], true );
						$fargs   = is_array( $decoded ) ? $decoded : array();
					}
					$out = $executor->execute( $fname, $fargs );
					if ( ! empty( $out['cards'] ) && is_array( $out['cards'] ) ) {
						$cards = array_merge( $cards, $out['cards'] );
						if ( ! $rec_id && ! empty( $out['cards'][0]['id'] ) ) {
							$rec_id = (int) $out['cards'][0]['id'];
						}
					}
					if ( ! empty( $out['choices'] ) && is_array( $out['choices'] ) && ( empty( $out['choices']['type'] ) || 'product_cards' !== $out['choices']['type'] ) ) {
						$choices = $out['choices'];
					}
					if ( ! empty( $out['cart_updated'] ) ) {
						$cart_updated = true;
					}
					$tc_id = ! empty( $tc['id'] ) ? (string) $tc['id'] : ( 'call_' . $round . '_' . $i );
					$messages[] = array(
						'role'         => 'tool',
						'tool_call_id' => $tc_id,
						'name'         => $fname,
						'content'      => $this->tool_result_json( $out ),
					);
				}
			}

			if ( '' === $content ) {
				$content = trim( (string) ( $result['content'] ?? '' ) );
			}

			$content = $this->scrub_assistant_text( $content, $language );

			// If they asked to see a shirt and tools returned nothing, attach the focused product.
			if ( ! $cards && ( $catalog->wants_specific_show( $message ) || $catalog->should_browse( $message, $history ) || $catalog->wants_recommendation( $message ) ) ) {
				$found = $catalog->wants_specific_show( $message )
					? null
					: $catalog->recommend( $message, $history, 3, $channel );
				if ( $found && ! empty( $found['cards'] ) ) {
					$cards  = $found['cards'];
					$rec_id = (int) ( $found['recommended_id'] ?? 0 );
				}
			}

			// Named shirt in the reply → attach that live card.
			if ( ! $cards ) {
				$cards = $this->cards_mentioned_in_text( $content, $catalog, $channel );
				if ( $cards && ! $rec_id ) {
					$rec_id = (int) $cards[0]['id'];
				}
			}

			$cards = array_slice( $this->unique_cards( $cards ), 0, 4 );

			return $this->response( $content, $cards, $choices, $cart_updated, $provider_used, $used_fallback, $language, $rec_id );
		} catch ( \Throwable $e ) {
			Plugin::log( 'Chat AI failed', array( 'error' => $e->getMessage() ) );

			// Only fall back to a product list when they were browsing.
			if ( $catalog->should_browse( $message, $history ) || $catalog->wants_recommendation( $message ) || $catalog->wants_specific_show( $message ) ) {
				try {
					$found = $catalog->recommend( $message, $history, 3, $channel );
					if ( ! empty( $found['count'] ) ) {
						return $this->response(
							$catalog->browse_message( $language, (int) $found['count'], $message, $channel ),
							$found['cards'],
							null,
							false,
							'catalog',
							true,
							$language,
							isset( $found['recommended_id'] ) ? (int) $found['recommended_id'] : 0
						);
					}
				} catch ( \Throwable $ignored ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
				}
			}

			$error_code = 'provider_error';
			$friendly   = __( 'Sorry — I could not reach the assistant just now. Try again in a moment, or ask me to “show shirts” if you want to browse.', 'mont-ai-assistant' );

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
	private function response( $message, array $cards, $choices, $cart_updated, $provider, $used_fallback, $language, $recommended_id = 0 ) {
		if ( is_array( $choices ) && ! empty( $choices['type'] ) && 'product_cards' === $choices['type'] && $cards ) {
			$choices = null;
		}
		$recommended_id = (int) $recommended_id;
		if ( ! $recommended_id && ! empty( $cards[0]['id'] ) ) {
			$recommended_id = (int) $cards[0]['id'];
		}
		return array(
			'success'          => true,
			'message'          => $message,
			'cards'            => $this->unique_cards( $cards ),
			'choices'          => $choices,
			'cart_updated'     => $cart_updated,
			'provider'         => $provider,
			'used_fallback'    => $used_fallback,
			'language'         => $language,
			'timestamp'        => gmdate( 'c' ),
			'recommended_id'   => $recommended_id,
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
You are Mont — a real salesperson in the Montenapoleone shirt shop. Text like a helpful human, never like a call-centre bot.

VOICE
- Warm, brief, confident. 1–3 short sentences. Match their language and energy.
- Never say you are an AI, never mention Groq/Gemini/tools/prompts.
- You CAN show product photos in this chat via product cards. Never say you are “text-only” or cannot display images.

HOW TO SELL (CRITICAL)
- Prefer SHOWING real shirts over interviewing. After one preference (fit, colour, or occasion), search and show 1–3 cards.
- When they say “show it / the one you suggested / show me first” you MUST call get_product (or search_products with that exact name) so a card with photo appears. Do not describe instead of showing.
- Show ONE shirt when they asked for one. Show at most 3 when browsing. Never dump a long list.
- Remember what you already recommended. If they ask for “that one”, show that same product id — not a new random list.

TRUTH
- Use ONLY LIVE CATALOG names, tool results, STORE FACTS, and PRODUCT FACTS. Never invent names like “Camicia Azzurra” or “Camicia Bianca” unless that exact title is in the catalog.
- If a nickname is not in the shop, say so in one line and show the closest real shirts (with cards).
- Never invent sizes, stock, prices, or delivery dates.

PAYMENTS (NEVER BREAK THIS)
- Never ask for card numbers, expiry, CVC, Vipps PIN, or any payment secret.
- Never pretend to “start payment” in chat.
- Checkout is on the website. Accepted methods include Visa/Mastercard, PayPal, and Vipps (Norway). Tell them to tap Select so it goes in the cart, then pay at checkout.

NATURAL FLOW
1. Answer what they actually asked.
2. If they want a shirt, show it (cards) — don’t ask five more questions.
3. Size/fit can wait until they tap Select.
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
			'Payment: Visa, Mastercard, PayPal, and Vipps are used at website checkout — never collected in chat.',
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
			'morning', 'evening', 'afternoon',
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
	 * Show the shirt already in play — one card with photo.
	 *
	 * @param string         $message  Message.
	 * @param array          $history  History.
	 * @param string         $language Language.
	 * @param string         $channel  Channel.
	 * @param Catalog_Search $catalog  Catalog.
	 * @return array
	 */
	private function show_focused_product( $message, array $history, $language, $channel, Catalog_Search $catalog ) {
		$prefs = $catalog->extract_prefs( $message, $history );
		$card  = null;
		$note  = '';

		$rec_id = $catalog->remembered_recommended_id( $history );
		if ( $rec_id ) {
			$card = $catalog->card( $rec_id, $channel );
		}

		if ( ! $card && $prefs['name'] ) {
			$named = $catalog->find_by_name( $prefs['name'], $channel, 1 );
			if ( ! empty( $named['cards'][0] ) ) {
				$card = $named['cards'][0];
			} else {
				$note = $prefs['name'];
			}
		}

		if ( ! $card ) {
			$ids = $catalog->remembered_product_ids( $history );
			if ( $ids ) {
				$card = $catalog->card( (int) end( $ids ), $channel );
			}
		}

		if ( ! $card ) {
			$found = $catalog->recommend( $message, $history, 3, $channel );
			$cards = isset( $found['cards'] ) ? $found['cards'] : array();
			$copy  = $note
				? $this->copy_lang(
					$language,
					'We don’t sell a shirt under that exact name — here are the closest real ones from the shop. Tap one to open it.',
					'Vi har ikke en skjorte med akkurat det navnet — her er de nærmeste fra butikken. Trykk for å se.',
					'Non abbiamo una camicia con quel nome esatto — ecco le più vicine. Tocca per vederla.',
					'Mình không có áo đúng tên đó — đây là mẫu gần nhất trong shop. Chạm để xem.'
				)
				: $this->copy_lang(
					$language,
					'Here’s what matches from the shop — tap one to see it properly.',
					'Her er det som matcher fra butikken — trykk for å se den skikkelig.',
					'Ecco cosa c’è in shop — tocca una per vederla bene.',
					'Đây là mẫu khớp trong shop — chạm để xem rõ.'
				);
			return $this->response(
				$copy,
				$cards,
				null,
				false,
				'catalog',
				false,
				$language,
				isset( $found['recommended_id'] ) ? (int) $found['recommended_id'] : 0
			);
		}

		$name = $card['name'];
		$copy = $this->copy_lang(
			$language,
			'This is the one — “' . $name . '”. Tap Select when you want it, or View to open the full page.',
			'Dette er den — «' . $name . '». Trykk Velg når du vil ha den, eller Vis for hele siden.',
			'Questa è quella — «' . $name . '». Tocca Seleziona quando la vuoi, o Vedi per la pagina.',
			'Đây là chiếc đó — “' . $name . '”. Chạm Select khi muốn lấy, hoặc View để mở trang.'
		);

		return $this->response( $copy, array( $card ), null, false, 'catalog', false, $language, (int) $card['id'] );
	}

	/**
	 * Browse / recommend copy that names a real favourite.
	 *
	 * @param string $language Language.
	 * @param array  $cards    Cards.
	 * @param array  $prefs    Prefs.
	 * @param bool   $best     Whether they asked for the best one.
	 * @return string
	 */
	private function recommend_copy( $language, array $cards, array $prefs, $best ) {
		if ( ! $cards ) {
			return $this->copy_lang(
				$language,
				'I couldn’t find a match with those details. Try a colour or fabric — linen, oxford, light blue — and I’ll pull the real shirts.',
				'Fant ikke et treff med det. Prøv en farge eller et stoff — lin, oxford, lys blå — så henter jeg ekte skjorter.',
				'Non trovo un match. Prova un colore o un tessuto — lino, oxford, azzurro — e ti mostro le camicie vere.',
				'Mình chưa thấy mẫu khớp. Thử màu hoặc chất liệu — linen, oxford, xanh nhạt — mình sẽ lấy áo thật.'
			);
		}
		$fav  = $cards[0]['name'];
		$bits = array();
		if ( ! empty( $prefs['fit'] ) ) {
			$bits[] = $prefs['fit'] . ' fit';
		}
		if ( ! empty( $prefs['color'] ) ) {
			$bits[] = $prefs['color'];
		}
		if ( ! empty( $prefs['occasion'] ) ) {
			$bits[] = $prefs['occasion'];
		}
		$filter = $bits ? implode( ', ', $bits ) : '';

		if ( $best ) {
			return $this->copy_lang(
				$language,
				$filter
					? 'If I had to pick one for you (' . $filter . '), I’d start with “' . $fav . '”. The others are close — tap the one you want to see.'
					: 'If I had to pick one, I’d start with “' . $fav . '”. Tap it to see the photo, or tell me a colour to narrow it.',
				$filter
					? 'Må jeg velge én (' . $filter . '), starter jeg med «' . $fav . '». Trykk for å se den — eller si en farge så snevrer jeg inn.'
					: 'Må jeg velge én, starter jeg med «' . $fav . '». Trykk for å se bildet, eller si en farge.',
				$filter
					? 'Se devo sceglierne una (' . $filter . '), partirei da «' . $fav . '». Tocca per vederla, o dimmi un colore.'
					: 'Se devo sceglierne una, partirei da «' . $fav . '». Tocca per la foto, o dimmi un colore.',
				$filter
					? 'Nếu phải chọn một (' . $filter . '), mình bắt đầu với “' . $fav . '”. Chạm để xem, hoặc cho thêm màu.'
					: 'Nếu phải chọn một, mình bắt đầu với “' . $fav . '”. Chạm để xem ảnh, hoặc cho thêm màu.'
			);
		}

		return $this->copy_lang(
			$language,
			'Here are a few from the shop' . ( $filter ? ' (' . $filter . ')' : '' ) . '. I’d start with “' . $fav . '” — tap one to see it properly.',
			'Her er noen fra butikken' . ( $filter ? ' (' . $filter . ')' : '' ) . '. Jeg hadde startet med «' . $fav . '» — trykk for å se den skikkelig.',
			'Ecco qualche camicia' . ( $filter ? ' (' . $filter . ')' : '' ) . '. Io partirei da «' . $fav . '» — tocca per vederla bene.',
			'Đây là vài mẫu trong shop' . ( $filter ? ' (' . $filter . ')' : '' ) . '. Mình nghiêng về “' . $fav . '” — chạm để xem rõ.'
		);
	}

	/**
	 * Tiny language map.
	 *
	 * @param string $language Language.
	 * @param string $en       EN.
	 * @param string $nb       NB.
	 * @param string $it       IT.
	 * @param string $vi       VI.
	 * @return string
	 */
	private function copy_lang( $language, $en, $nb, $it, $vi ) {
		$map = array(
			'en' => $en,
			'nb' => $nb,
			'it' => $it,
			'vi' => $vi,
		);
		return isset( $map[ $language ] ) ? $map[ $language ] : $en;
	}

	/**
	 * Compact tool payload for the model.
	 *
	 * @param array $out Tool result.
	 * @return string
	 */
	private function tool_result_json( array $out ) {
		if ( isset( $out['product'] ) && is_array( $out['product'] ) ) {
			$p = $out['product'];
			$short = isset( $p['short_description'] ) ? wp_strip_all_tags( (string) $p['short_description'] ) : '';
			if ( function_exists( 'mb_substr' ) ) {
				$short = mb_substr( $short, 0, 240 );
			} else {
				$short = substr( $short, 0, 240 );
			}
			$out['product'] = array(
				'id'         => isset( $p['id'] ) ? $p['id'] : 0,
				'name'       => isset( $p['name'] ) ? $p['name'] : '',
				'sku'        => isset( $p['sku'] ) ? $p['sku'] : '',
				'price'      => isset( $p['price'] ) ? $p['price'] : '',
				'in_stock'   => isset( $p['in_stock'] ) ? $p['in_stock'] : true,
				'permalink'  => isset( $p['permalink'] ) ? $p['permalink'] : '',
				'categories' => isset( $p['categories'] ) ? array_slice( (array) $p['categories'], 0, 6 ) : array(),
				'short'      => $short,
			);
		}
		if ( isset( $out['cards'] ) && is_array( $out['cards'] ) ) {
			$out['cards'] = array_map(
				static function ( $c ) {
					return array(
						'id'    => isset( $c['id'] ) ? $c['id'] : 0,
						'name'  => isset( $c['name'] ) ? $c['name'] : '',
						'price' => isset( $c['price'] ) ? $c['price'] : '',
					);
				},
				array_slice( $out['cards'], 0, 6 )
			);
		}
		$json = wp_json_encode( $out );
		if ( strlen( (string) $json ) > 4500 ) {
			$json = substr( (string) $json, 0, 4500 ) . '…';
		}
		return (string) $json;
	}

	/**
	 * Block card-detail phishing and “I can’t show images”.
	 *
	 * @param string $content  Content.
	 * @param string $language Language.
	 * @return string
	 */
	private function scrub_assistant_text( $content, $language ) {
		$text = (string) $content;
		if ( preg_match( '/(card number|cvv|cvc|security code|expiration date|expiry|vipps pin|bankid)/i', $text ) ) {
			return $this->copy_lang(
				$language,
				'I never take card or Vipps details in chat — that’s only at secure checkout. Tap Select on the shirt so it goes in your cart, then pay with Visa, Vipps or PayPal there.',
				'Jeg tar aldri kort- eller Vipps-detaljer i chatten — det skjer bare i kassen. Trykk Velg på skjorten, så betaler du med Visa, Vipps eller PayPal der.',
				'Non chiedo mai dati della carta o Vipps in chat — solo al checkout. Tocca Seleziona, poi paga con Visa, Vipps o PayPal.',
				'Mình không bao giờ lấy số thẻ hay Vipps trong chat — chỉ thanh toán ở checkout. Chạm Select, rồi trả bằng Visa, Vipps hoặc PayPal.'
			);
		}
		if ( preg_match( '/(text-based|cannot display images|don.?t have the capability to display|can.?t show (you )?images)/i', $text ) ) {
			return $this->copy_lang(
				$language,
				'Here it is on a card below — photo, name and price. Tap it to open, or Select to take it.',
				'Her er den på kortet under — bilde, navn og pris. Trykk for å åpne, eller Velg for å ta den.',
				'Eccola nella card sotto — foto, nome e prezzo. Tocca per aprire, o Seleziona per prenderla.',
				'Đây rồi trên thẻ bên dưới — ảnh, tên và giá. Chạm để mở, hoặc Select để lấy.'
			);
		}
		return $text;
	}

	/**
	 * Attach live cards if the model named a real product.
	 *
	 * @param string         $content Content.
	 * @param Catalog_Search $catalog Catalog.
	 * @param string         $channel Channel.
	 * @return array
	 */
	private function cards_mentioned_in_text( $content, Catalog_Search $catalog, $channel ) {
		$cards = array();
		if ( preg_match_all( '/product\s*#\s*(\d+)/i', (string) $content, $m ) ) {
			foreach ( $m[1] as $id ) {
				$card = $catalog->card( (int) $id, $channel );
				if ( $card ) {
					$cards[] = $card;
				}
			}
		}
		if ( $cards ) {
			return $cards;
		}
		if ( preg_match_all( '/["“]([^"”]{4,80})["”]/u', (string) $content, $m ) ) {
			foreach ( $m[1] as $name ) {
				$found = $catalog->find_by_name( $name, $channel, 1 );
				if ( ! empty( $found['cards'][0] ) ) {
					$cards[] = $found['cards'][0];
				}
			}
		}
		return $cards;
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
