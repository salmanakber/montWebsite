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
		foreach ( array_reverse( $history ) as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			if ( preg_match( '/product\s*#?\s*(\d+)/i', (string) $h['content'], $m ) ) {
				return (int) $m[1];
			}
		}
		return 0;
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
					if ( 0 === strcasecmp( $t, trim( $label ) ) ) {
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
		$msg = trim( $message );
		foreach ( $options as $opt ) {
			if ( empty( $opt['choices'] ) ) {
				continue;
			}
			foreach ( $opt['choices'] as $c ) {
				$label = is_array( $c ) ? ( isset( $c['label'] ) ? $c['label'] : '' ) : (string) $c;
				if ( $label && 0 === strcasecmp( $msg, trim( $label ) ) ) {
					$selection[ $opt['key'] ] = $label;
					return $selection;
				}
			}
		}
		if ( preg_match( '/^\d+$/', $msg ) ) {
			$selection['quantity'] = max( 1, (int) $msg );
		}
		return $selection;
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
				'body_fit'    => 'Which body fit would you like?',
				'size'        => 'Which size do you need?',
				'collar_type' => 'Which collar style do you prefer?',
				'cuff_type'   => 'Which cuff style do you prefer?',
				'quantity'    => 'How many would you like?',
			),
			'nb' => array(
				'body_fit'    => 'Hvilken passform ønsker du?',
				'size'        => 'Hvilken størrelse trenger du?',
				'collar_type' => 'Hvilken snipp ønsker du?',
				'cuff_type'   => 'Hvilke mansjetter ønsker du?',
				'quantity'    => 'Hvor mange ønsker du?',
			),
			'it' => array(
				'body_fit'    => 'Quale vestibilità preferisci?',
				'size'        => 'Quale taglia ti serve?',
				'collar_type' => 'Quale collo preferisci?',
				'cuff_type'   => 'Quali polsini preferisci?',
				'quantity'    => 'Quanti pezzi vuoi?',
			),
			'vi' => array(
				'body_fit'    => 'Bạn muốn form áo nào?',
				'size'        => 'Bạn cần size nào?',
				'collar_type' => 'Bạn thích kiểu cổ nào?',
				'cuff_type'   => 'Bạn thích kiểu tay nào?',
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
				'picked'         => 'Great choice — “' . $name . '”. Let’s configure it:',
				'added'          => 'Done — “' . $name . '” is in your cart. You can checkout whenever you’re ready.',
				'missing_product'=> 'I could not find that product. Please pick another from the list.',
				'cart_fail'      => 'I could not add that to the cart yet. Please try again.',
			),
			'nb' => array(
				'picked'         => 'Flott valg — “' . $name . '”. La oss tilpasse den:',
				'added'          => 'Ferdig — “' . $name . '” er i handlekurven.',
				'missing_product'=> 'Fant ikke det produktet. Velg et annet fra listen.',
				'cart_fail'      => 'Kunne ikke legge i handlekurven. Prøv igjen.',
			),
			'it' => array(
				'picked'         => 'Ottima scelta — “' . $name . '”. Configuriamola:',
				'added'          => 'Fatto — “' . $name . '” è nel carrello.',
				'missing_product'=> 'Prodotto non trovato. Scegline un altro.',
				'cart_fail'      => 'Non sono riuscito ad aggiungerlo al carrello.',
			),
			'vi' => array(
				'picked'         => 'Tuyệt — “' . $name . '”. Chúng ta cấu hình nhé:',
				'added'          => 'Xong — “' . $name . '” đã vào giỏ hàng.',
				'missing_product'=> 'Không tìm thấy sản phẩm. Vui lòng chọn sản phẩm khác.',
				'cart_fail'      => 'Chưa thêm vào giỏ được. Thử lại nhé.',
			),
		);
		$lang = isset( $all[ $language ] ) ? $all[ $language ] : $all['en'];
		return isset( $lang[ $key ] ) ? $lang[ $key ] : $all['en'][ $key ];
	}
}
