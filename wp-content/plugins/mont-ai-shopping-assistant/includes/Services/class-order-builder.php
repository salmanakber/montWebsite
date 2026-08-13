<?php
/**
 * Local order-building without AI (reliable path).
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Services;

use Mont_AI_Assistant\Cart\Cart_Service;
use Mont_AI_Assistant\Product\Custom_Options;
use Mont_AI_Assistant\Product\Product_Knowledge;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Builder
 *
 * Parses chat history + latest message to collect shirt options and add to cart
 * without calling Groq/Gemini.
 */
class Order_Builder {

	/**
	 * Whether the customer is mid shirt configuration (picked product, options pending).
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return bool
	 */
	public static function is_configuring( $message, array $history ) {
		if ( self::active_product_id( $history ) <= 0 ) {
			return false;
		}
		// Explicit new browse intent cancels configuration.
		$text = strtolower( trim( (string) $message ) );
		if ( preg_match( '/\b(cancel|start over|different shirt|another shirt|new shirt|forget (it|that)|browse again)\b/i', $text ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Product id currently being configured from chat history.
	 *
	 * @param array $history History.
	 * @return int
	 */
	public static function active_product_id( array $history ) {
		foreach ( array_reverse( $history ) as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			if ( preg_match( '/(is in your cart|Cart updated|checkout whenever|ligger i handlekurven|nel carrello)/i', (string) $h['content'] ) ) {
				return 0;
			}
		}

		$pick = 0;
		foreach ( array_reverse( $history ) as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			$role = isset( $h['role'] ) ? $h['role'] : 'user';
			if ( 'user' === $role && preg_match( '/I want product\s*#?\s*(\d+)/i', (string) $h['content'], $m ) ) {
				$pick = (int) $m[1];
				break;
			}
		}
		if ( $pick <= 0 ) {
			return 0;
		}

		$after_pick = false;
		foreach ( $history as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			$content = (string) $h['content'];
			$role    = isset( $h['role'] ) ? $h['role'] : 'user';
			if ( 'user' === $role && preg_match( '/I want product\s*#?\s*' . $pick . '\b/i', $content ) ) {
				$after_pick = true;
				continue;
			}
			if ( $after_pick && 'assistant' === $role && preg_match( '/(which fit|Which size|collar|cuff|passform|størrelse|details right|Love that pick|Almost there)/i', $content ) ) {
				return $pick;
			}
		}
		return 0;
	}

	/**
	 * Try to handle a message locally. Returns response array or null to fall through.
	 *
	 * @param string $message  Message.
	 * @param array  $history  History.
	 * @param string $language Language.
	 * @return array|null
	 */
	public function maybe_handle( $message, array $history, $language = 'en' ) {
		$product_id = $this->find_product_id( $message, $history );
		if ( ! $product_id ) {
			return null;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array(
				'success' => true,
				'message' => $this->msg( $language, 'missing_product' ),
				'cards'   => array(),
				'choices' => null,
			);
		}

		$options   = ( new Custom_Options() )->for_product( $product );
		$selection = $this->collect_selection( $message, $history, $options );

		// Just selected the product (message contains product #) — show first option.
		if ( $this->message_picks_product( $message ) ) {
			$next = $this->next_missing_option( $options, $selection );
			if ( $next ) {
				$choices = Tool_Executor::choices_from_option( $next );
				if ( $choices ) {
					$choices['product_id'] = $product_id;
					$choices['title']      = $this->ask_title( $language, $next );
					$card = ( new Product_Knowledge() )->card( $product_id );
					return array(
						'success'  => true,
						'message'  => $this->msg( $language, 'picked', $product->get_name() ),
						'cards'    => $card ? array( $card ) : array(),
						'choices'  => $choices,
						'provider' => 'local',
					);
				}
			}
		}

		// Merge latest tap into selection if it matches an option choice.
		$selection = $this->apply_latest_choice( $message, $options, $selection );

		$validation = ( new Custom_Options() )->validate( $product, $selection );
		if ( ! empty( $validation['valid'] ) ) {
			$result = ( new Cart_Service() )->add_to_cart( $product_id, $selection );
			if ( ! empty( $result['success'] ) ) {
				$card = ( new Product_Knowledge() )->card( $product_id );
				return array(
					'success'      => true,
					'message'      => $this->msg( $language, 'added', $product->get_name() ),
					'cards'        => $card ? array( $card ) : array(),
					'choices'      => null,
					'cart_updated' => true,
					'provider'     => 'local',
				);
			}
			return array(
				'success' => false,
				'message' => isset( $result['message'] ) ? $result['message'] : $this->msg( $language, 'cart_fail' ),
				'cards'   => array(),
				'choices' => isset( $result['choices'] ) ? $result['choices'] : null,
			);
		}

		// Ask for next missing option.
		$missing = ! empty( $validation['missing'] ) ? $validation['missing'] : array();
		foreach ( $options as $opt ) {
			if ( in_array( $opt['key'], $missing, true ) || ( ! empty( $opt['required'] ) && empty( $selection[ $opt['key'] ] ) ) ) {
				if ( 'quantity' === $opt['key'] && empty( $selection['quantity'] ) ) {
					$selection['quantity'] = 1;
					continue;
				}
				if ( empty( $opt['choices'] ) ) {
					continue;
				}
				$choices = Tool_Executor::choices_from_option( $opt );
				if ( $choices ) {
					$choices['product_id'] = $product_id;
					$choices['title']      = $this->ask_title( $language, $opt );
					return array(
						'success'  => true,
						'message'  => $this->ask_title( $language, $opt ),
						'cards'    => array(),
						'choices'  => $choices,
						'provider' => 'local',
					);
				}
			}
		}

		// Quantity default then retry add.
		if ( empty( $selection['quantity'] ) ) {
			$selection['quantity'] = 1;
			$result = ( new Cart_Service() )->add_to_cart( $product_id, $selection );
			if ( ! empty( $result['success'] ) ) {
				$card = ( new Product_Knowledge() )->card( $product_id );
				return array(
					'success'      => true,
					'message'      => $this->msg( $language, 'added', $product->get_name() ),
					'cards'        => $card ? array( $card ) : array(),
					'choices'      => null,
					'cart_updated' => true,
					'provider'     => 'local',
				);
			}
		}

		return null;
	}

	/**
	 * @param string $message Message.
	 * @return bool
	 */
	private function message_picks_product( $message ) {
		return (bool) preg_match( '/product\s*#?\s*\d+/i', $message );
	}

	/**
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return int
	 */
	private function find_product_id( $message, array $history ) {
		if ( preg_match( '/product\s*#?\s*(\d+)/i', $message, $m ) ) {
			return (int) $m[1];
		}
		$active = self::active_product_id( $history );
		if ( $active > 0 && self::looks_like_option_answer( $message ) ) {
			return $active;
		}
		return 0;
	}

	/**
	 * True when message is likely a fit/size/collar/cuff tap.
	 *
	 * @param string $message Message.
	 * @return bool
	 */
	private static function looks_like_option_answer( $message ) {
		$msg = strtolower( trim( (string) $message ) );
		if ( preg_match( '/^\d{2}$/', $msg ) ) {
			return true;
		}
		if ( preg_match( '/\b(slim\s*fit|regular\s*fit|classic\s*fit|extra\s*slim|slim|regular|classic)\b/i', $msg ) ) {
			return true;
		}
		if ( preg_match( '/\b(collar|cuff|snipp|mansjett|collo|polsino|quantity|qty)\b/i', $msg ) ) {
			return true;
		}
		// Short taps from choice buttons (usually under 40 chars, no product browse intent).
		if ( strlen( $msg ) <= 40 && ! preg_match( '/\b(shirt|skjorte|camicia|show|browse|recommend|pick one|top pick|only one)\b/i', $msg ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Collect selection values already present in history + message.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @param array  $options Options schema.
	 * @return array
	 */
	private function collect_selection( $message, array $history, array $options ) {
		$texts = array();
		foreach ( $history as $h ) {
			if ( empty( $h['content'] ) || ( isset( $h['role'] ) && 'assistant' === $h['role'] ) ) {
				continue;
			}
			$texts[] = trim( (string) $h['content'] );
		}
		$texts[] = trim( $message );

		$selection = array( 'quantity' => 1 );

		foreach ( $options as $opt ) {
			$key = $opt['key'];
			if ( empty( $opt['choices'] ) || ! is_array( $opt['choices'] ) ) {
				continue;
			}
			foreach ( $opt['choices'] as $c ) {
				$label = is_array( $c ) ? ( isset( $c['label'] ) ? $c['label'] : '' ) : (string) $c;
				if ( '' === $label ) {
					continue;
				}
				foreach ( $texts as $t ) {
					$tn = $this->normalize_choice( $t );
					$ln = $this->normalize_choice( $label );
					if ( $tn === $ln || 0 === strcasecmp( trim( $t ), trim( $label ) ) ) {
						$selection[ $key ] = $label;
						break 2;
					}
				}
			}
		}

		return $selection;
	}

	/**
	 * Apply the latest user message if it is an exact choice label.
	 *
	 * @param string $message   Message.
	 * @param array  $options   Options.
	 * @param array  $selection Selection.
	 * @return array
	 */
	private function apply_latest_choice( $message, array $options, array $selection ) {
		$msg = $this->normalize_choice( $message );
		foreach ( $options as $opt ) {
			if ( empty( $opt['choices'] ) ) {
				continue;
			}
			foreach ( $opt['choices'] as $c ) {
				$label = is_array( $c ) ? ( isset( $c['label'] ) ? $c['label'] : '' ) : (string) $c;
				$norm  = $this->normalize_choice( $label );
				if ( ! $label ) {
					continue;
				}
				if ( $msg === $norm || 0 === strcasecmp( trim( $message ), trim( $label ) ) ) {
					$selection[ $opt['key'] ] = $label;
					return $selection;
				}
			}
		}
		if ( preg_match( '/^\d+$/', trim( $message ) ) ) {
			$selection['quantity'] = max( 1, (int) trim( $message ) );
		}
		return $selection;
	}

	/**
	 * Normalize option labels for fuzzy match (SLIM FIT ↔ Slim Fit).
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function normalize_choice( $text ) {
		$text = strtolower( trim( (string) $text ) );
		$text = preg_replace( '/\s+/', ' ', $text );
		return $text;
	}

	/**
	 * @param array $options   Options.
	 * @param array $selection Selection.
	 * @return array|null
	 */
	private function next_missing_option( array $options, array $selection ) {
		foreach ( $options as $opt ) {
			if ( empty( $opt['required'] ) ) {
				continue;
			}
			$key = $opt['key'];
			if ( 'quantity' === $key ) {
				continue;
			}
			if ( empty( $selection[ $key ] ) && ! empty( $opt['choices'] ) ) {
				return $opt;
			}
		}
		return null;
	}

	/**
	 * @param string $language Language.
	 * @param array  $opt      Option.
	 * @return string
	 */
	private function ask_title( $language, array $opt ) {
		$key = isset( $opt['key'] ) ? $opt['key'] : '';
		$map = array(
			'en' => array(
				'body_fit'    => 'Nice — which fit feels more like you?',
				'size'        => 'Perfect. Which size do you need?',
				'collar_type' => 'Almost there — which collar do you prefer?',
				'cuff_type'   => 'And for the cuffs?',
				'quantity'    => 'How many would you like?',
			),
			'nb' => array(
				'body_fit'    => 'Supert — hvilken passform kjenner du deg mest hjemme i?',
				'size'        => 'Flott. Hvilken størrelse trenger du?',
				'collar_type' => 'Nesten i mål — hvilken snipp vil du ha?',
				'cuff_type'   => 'Og mansjettene?',
				'quantity'    => 'Hvor mange ønsker du?',
			),
			'it' => array(
				'body_fit'    => 'Ottimo — quale vestibilità ti sta meglio?',
				'size'        => 'Perfetto. Quale taglia ti serve?',
				'collar_type' => 'Quasi fatto — quale collo preferisci?',
				'cuff_type'   => 'E per i polsini?',
				'quantity'    => 'Quanti pezzi vuoi?',
			),
			'vi' => array(
				'body_fit'    => 'Được rồi — bạn thích form nào hơn?',
				'size'        => 'Tuyệt. Bạn cần size nào?',
				'collar_type' => 'Gần xong — bạn thích kiểu cổ nào?',
				'cuff_type'   => 'Còn kiểu tay thì sao?',
				'quantity'    => 'Bạn muốn bao nhiêu chiếc?',
			),
		);
		$lang = isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
		if ( isset( $lang[ $key ] ) ) {
			return $lang[ $key ];
		}
		return isset( $opt['label'] ) ? $opt['label'] : 'Please choose an option';
	}

	/**
	 * @param string $language Language.
	 * @param string $key      Key.
	 * @param string $name     Name.
	 * @return string
	 */
	private function msg( $language, $key, $name = '' ) {
		$all = array(
			'en' => array(
				'picked'         => 'Love that pick — “' . $name . '”. Let’s get the details right:',
				'added'          => 'You’re all set — “' . $name . '” is in your cart. Checkout whenever you’re ready.',
				'missing_product'=> 'Hmm, I lost that product. Could you tap another one from the list?',
				'cart_fail'      => 'Something hiccuped adding that to the cart — mind trying once more?',
			),
			'nb' => array(
				'picked'         => 'Fint valg — “' . $name . '”. La oss få detaljene på plass:',
				'added'          => 'Da er du i mål — “' . $name . '” ligger i handlekurven. Check ut når du vil.',
				'missing_product'=> 'Hmm, fant ikke det produktet. Kan du trykke på et annet fra listen?',
				'cart_fail'      => 'Noe gikk galt med handlekurven — prøv én gang til?',
			),
			'it' => array(
				'picked'         => 'Bellissima scelta — “' . $name . '”. Sistemiamo i dettagli:',
				'added'          => 'Tutto fatto — “' . $name . '” è nel carrello. Puoi pagare quando vuoi.',
				'missing_product'=> 'Mm, non trovo quel prodotto. Ne scegli un altro dalla lista?',
				'cart_fail'      => 'C’è stato un intoppo col carrello — riproviamo?',
			),
			'vi' => array(
				'picked'         => 'Chọn đẹp đấy — “' . $name . '”. Mình chỉnh chi tiết nhé:',
				'added'          => 'Xong rồi — “' . $name . '” đã vào giỏ. Thanh toán khi nào cũng được.',
				'missing_product'=> 'Hmm, mình không thấy sản phẩm đó. Chọn mẫu khác trong list giúp mình nhé?',
				'cart_fail'      => 'Có chút trục trặc khi thêm giỏ — thử lại một lần nữa nhé?',
			),
		);
		$lang = isset( $all[ $language ] ) ? $all[ $language ] : $all['en'];
		return isset( $lang[ $key ] ) ? $lang[ $key ] : $all['en'][ $key ];
	}
}
