<?php
/**
 * Catalog search with synonym expansion + WooCommerce fallback.
 *
 * Used for reliable "show me shirts" flows without burning AI tool rounds.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Services;

use Mont_AI_Assistant\Product\Product_Index;
use Mont_AI_Assistant\Product\Product_Knowledge;

defined( 'ABSPATH' ) || exit;

/**
 * Class Catalog_Search
 */
class Catalog_Search {

	/**
	 * Synonym / intent expansions for shirt shopping.
	 *
	 * @return array<string,string[]>
	 */
	private function synonyms() {
		return array(
			'business'  => array( 'business', 'formal', 'office', 'dress', 'classic', 'shirt' ),
			'classic'   => array( 'classic', 'traditional', 'oxford', 'dress', 'shirt' ),
			'oxford'    => array( 'oxford', 'shirt', 'classic' ),
			'casual'    => array( 'casual', 'linen', 'relaxed', 'shirt' ),
			'wedding'   => array( 'wedding', 'white', 'formal', 'dress', 'shirt' ),
			'linen'     => array( 'linen', 'lin', 'shirt' ),
			'shirt'     => array( 'shirt', 'skjorte', 'camicia' ),
			'shirts'    => array( 'shirt', 'skjorte', 'camicia' ),
			'blue'      => array( 'blue', 'blå', 'blu', 'light blue' ),
			'white'     => array( 'white', 'hvit', 'bianco' ),
			'black'     => array( 'black', 'svart', 'nero' ),
		);
	}

	/**
	 * Build search terms from user message + recent history.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return string[]
	 */
	public function build_terms( $message, array $history = array() ) {
		$blob = strtolower( $message );
		foreach ( array_reverse( $history ) as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			$blob .= ' ' . strtolower( (string) $h['content'] );
			break; // last user/assistant turn is enough context
		}

		$terms = array();
		foreach ( $this->synonyms() as $key => $expand ) {
			if ( false !== strpos( $blob, $key ) ) {
				$terms = array_merge( $terms, $expand );
			}
		}

		// Raw words longer than 2 chars.
		$words = preg_split( '/[^a-z0-9àáäåæøöü]+/u', $blob );
		foreach ( (array) $words as $w ) {
			if ( strlen( $w ) >= 3 && ! in_array( $w, array( 'the', 'and', 'for', 'need', 'want', 'show', 'some', 'please', 'list', 'with', 'from', 'that', 'this', 'have', 'looking' ), true ) ) {
				$terms[] = $w;
			}
		}

		$terms = array_values( array_unique( array_filter( $terms ) ) );
		if ( ! $terms ) {
			$terms = array( 'shirt' );
		}
		return $terms;
	}

	/**
	 * Whether this message should trigger a direct catalog browse (skip AI tool gambling).
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return bool
	 */
	public function should_browse( $message, array $history = array() ) {
		$text = strtolower( trim( $message ) );
		$patterns = array(
			'show', 'list', 'see', 'browse', 'options', 'choose', 'which',
			'shirt', 'shirts', 'skjorte', 'camicia',
			'business', 'classic', 'casual', 'oxford', 'linen', 'wedding',
			'blue', 'white', 'black', 'formal', 'office',
			'looking for', 'need', 'want', 'find',
		);
		foreach ( $patterns as $p ) {
			if ( false !== strpos( $text, $p ) ) {
				return true;
			}
		}
		// Follow-up after assistant asked clarifying question about shirts.
		foreach ( array_reverse( $history ) as $h ) {
			if ( ( $h['role'] ?? '' ) !== 'assistant' ) {
				continue;
			}
			$prev = strtolower( (string) ( $h['content'] ?? '' ) );
			if ( false !== strpos( $prev, 'shirt' ) || false !== strpos( $prev, 'looking for' ) || false !== strpos( $prev, 'occasion' ) ) {
				return true;
			}
			break;
		}
		return false;
	}

	/**
	 * Search index + WooCommerce fallback. Returns cards + choice UI.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @param int    $limit   Limit.
	 * @return array{cards:array,choices:?array,count:int,query:string}
	 */
	public function search( $message, array $history = array(), $limit = 6 ) {
		$terms = $this->build_terms( $message, $history );
		$query = implode( ' ', array_slice( $terms, 0, 6 ) );
		$index = new Product_Index();
		$knowledge = new Product_Knowledge();

		$hits = array();
		$seen = array();

		// Try each important term against the index.
		foreach ( array_slice( $terms, 0, 8 ) as $term ) {
			foreach ( $index->search( $term, $limit ) as $hit ) {
				$id = isset( $hit['id'] ) ? (int) $hit['id'] : 0;
				if ( ! $id || isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$hits[] = $hit;
				if ( count( $hits ) >= $limit ) {
					break 2;
				}
			}
		}

		// Full query string.
		if ( count( $hits ) < $limit ) {
			foreach ( $index->search( $query, $limit ) as $hit ) {
				$id = isset( $hit['id'] ) ? (int) $hit['id'] : 0;
				if ( ! $id || isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$hits[] = $hit;
				if ( count( $hits ) >= $limit ) {
					break;
				}
			}
		}

		// WooCommerce fallback (index empty or thin).
		if ( count( $hits ) < 2 && function_exists( 'wc_get_products' ) ) {
			$wc_ids = array();
			try {
				$wc_ids = wc_get_products(
					array(
						'status' => 'publish',
						'limit'  => $limit,
						'return' => 'ids',
						's'      => $query,
					)
				);
				if ( empty( $wc_ids ) ) {
					$wc_ids = wc_get_products(
						array(
							'status' => 'publish',
							'limit'  => $limit,
							'return' => 'ids',
							's'      => 'shirt',
						)
					);
				}
				if ( empty( $wc_ids ) ) {
					$wc_ids = wc_get_products(
						array(
							'status'  => 'publish',
							'limit'   => $limit,
							'return'  => 'ids',
							'orderby' => 'date',
							'order'   => 'DESC',
						)
					);
				}
			} catch ( \Throwable $e ) {
				$wc_ids = array();
			}

			foreach ( (array) $wc_ids as $id ) {
				$id = (int) $id;
				if ( isset( $seen[ $id ] ) ) {
					continue;
				}
				$payload = null;
				try {
					$payload = $index->get( $id );
				} catch ( \Throwable $e ) {
					$payload = null;
				}
				if ( ! $payload && function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $id );
					if ( ! $product ) {
						continue;
					}
					$payload = array(
						'id'   => $id,
						'name' => $product->get_name(),
					);
				}
				if ( ! $payload ) {
					continue;
				}
				$seen[ $id ] = true;
				$hits[] = $payload;
				if ( count( $hits ) >= $limit ) {
					break;
				}
			}
		}

		$cards = array();
		$choice_items = array();
		foreach ( $hits as $hit ) {
			$id = isset( $hit['id'] ) ? (int) $hit['id'] : 0;
			$card = $knowledge->card( $id );
			if ( ! $card ) {
				continue;
			}
			$cards[] = $card;
			$choice_items[] = array(
				'label'      => $card['name'],
				'value'      => 'I want product #' . $card['id'] . ': ' . $card['name'],
				'image'      => $card['image'],
				'sub'        => $card['price'],
				'product_id' => $card['id'],
			);
		}

		$choices = null;
		if ( $choice_items ) {
			$choices = array(
				'title'   => 'Tap a shirt to select it',
				'field'   => 'product_id',
				'type'    => 'product_cards',
				'choices' => $choice_items,
			);
		}

		return array(
			'cards'   => $cards,
			'choices' => $choices,
			'count'   => count( $cards ),
			'query'   => $query,
			'terms'   => $terms,
		);
	}

	/**
	 * Friendly browse copy.
	 *
	 * @param string $language Language.
	 * @param int    $count    Count.
	 * @param string $message  User message.
	 * @return string
	 */
	public function browse_message( $language, $count, $message ) {
		$language = Language_Manager::normalize( $language );
		if ( $count < 1 ) {
			$map = array(
				'en' => "I could not find matching shirts just now. Tell me a colour, fabric (e.g. linen), or style and I will try again.",
				'it' => "Non trovo camicie corrispondenti al momento. Dimmi un colore, un tessuto o uno stile e riprovo.",
				'nb' => "Jeg fant ingen matchende skjorter akkurat nå. Si en farge, stoff eller stil, så prøver jeg igjen.",
				'vi' => "Hiện tôi chưa tìm thấy áo phù hợp. Cho tôi biết màu, chất liệu hoặc kiểu dáng để tìm lại nhé.",
			);
			return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
		}

		$map = array(
			'en' => "Here are some shirts that fit what you described. Tap one to select it — or tell me a colour/fabric if you want a tighter match.",
			'it' => "Ecco alcune camicie in linea con quello che cerchi. Tocca una per selezionarla — oppure dimmi colore/tessuto per affinare.",
			'nb' => "Her er noen skjorter som passer det du beskrev. Trykk for å velge — eller si farge/stoff hvis du vil snevre inn.",
			'vi' => "Đây là một số áo phù hợp với mô tả của bạn. Chạm để chọn — hoặc cho thêm màu/chất liệu nếu muốn lọc kỹ hơn.",
		);
		return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
	}
}
