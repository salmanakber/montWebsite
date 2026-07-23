<?php
/**
 * Catalog search — WooCommerce products for the chat widget.
 *
 * Intentionally avoids get_price_html() / heavy product builders that can
 * conflict with multi-currency filters and crash the REST request.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Services;

use Mont_AI_Assistant\Language\Language_Manager;
use Mont_AI_Assistant\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Catalog_Search
 */
class Catalog_Search {

	/**
	 * Synonym expansions.
	 *
	 * @return array
	 */
	private function synonyms() {
		return array(
			'business' => array( 'business', 'formal', 'office', 'dress', 'classic' ),
			'classic'  => array( 'classic', 'oxford', 'dress' ),
			'oxford'   => array( 'oxford' ),
			'casual'   => array( 'casual', 'linen' ),
			'wedding'  => array( 'wedding', 'white' ),
			'linen'    => array( 'linen', 'lin' ),
			'shirt'    => array( 'shirt', 'skjorte', 'camicia' ),
			'shirts'   => array( 'shirt', 'skjorte', 'camicia' ),
			'blue'     => array( 'blue', 'blå', 'blu' ),
			'white'    => array( 'white', 'hvit', 'bianco' ),
			'black'    => array( 'black', 'svart', 'nero' ),
		);
	}

	/**
	 * Build search terms.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return string[]
	 */
	public function build_terms( $message, array $history = array() ) {
		$blob = strtolower( (string) $message );
		foreach ( array_reverse( $history ) as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			$blob .= ' ' . strtolower( (string) $h['content'] );
			break;
		}

		$terms = array();
		foreach ( $this->synonyms() as $key => $expand ) {
			if ( false !== strpos( $blob, $key ) ) {
				$terms = array_merge( $terms, $expand );
			}
		}

		$words = preg_split( '/[^a-z0-9]+/i', $blob );
		$skip  = array( 'the', 'and', 'for', 'need', 'want', 'show', 'some', 'please', 'list', 'with', 'from', 'that', 'this', 'have', 'looking', 'am', 'are' );
		foreach ( (array) $words as $w ) {
			$w = strtolower( trim( $w ) );
			if ( strlen( $w ) >= 3 && ! in_array( $w, $skip, true ) ) {
				$terms[] = $w;
			}
		}

		$terms = array_values( array_unique( array_filter( $terms ) ) );
		return $terms ? $terms : array( 'shirt' );
	}

	/**
	 * Whether to run catalog browse.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return bool
	 */
	public function should_browse( $message, array $history = array() ) {
		$text = strtolower( trim( (string) $message ) );
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
		foreach ( array_reverse( $history ) as $h ) {
			if ( ! isset( $h['role'] ) || 'assistant' !== $h['role'] ) {
				continue;
			}
			$prev = strtolower( (string) ( isset( $h['content'] ) ? $h['content'] : '' ) );
			if ( false !== strpos( $prev, 'shirt' ) || false !== strpos( $prev, 'looking for' ) || false !== strpos( $prev, 'occasion' ) ) {
				return true;
			}
			break;
		}
		return false;
	}

	/**
	 * Search products. Never throws.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @param int    $limit   Limit.
	 * @return array
	 */
	public function search( $message, array $history = array(), $limit = 6 ) {
		$empty = array(
			'cards'   => array(),
			'choices' => null,
			'count'   => 0,
			'query'   => '',
			'terms'   => array(),
		);

		try {
			$limit = max( 1, min( 12, (int) $limit ) );
			$terms = $this->build_terms( $message, $history );
			$query = implode( ' ', array_slice( $terms, 0, 5 ) );

			$ids = $this->query_product_ids( $query, $terms, $limit );
			$cards = array();
			$choice_items = array();

			foreach ( $ids as $id ) {
				$card = $this->safe_card( (int) $id );
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
		} catch ( \Throwable $e ) {
			Plugin::log( 'Catalog_Search::search error', array( 'error' => $e->getMessage() ) );
			return $empty;
		}
	}

	/**
	 * Resolve product IDs via WP_Query (most reliable).
	 *
	 * @param string $query Query string.
	 * @param array  $terms Terms.
	 * @param int    $limit Limit.
	 * @return int[]
	 */
	private function query_product_ids( $query, array $terms, $limit ) {
		$ids = array();

		// 1) Keyword search.
		$q = new \WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				's'                      => $query ? $query : 'shirt',
				'posts_per_page'         => $limit,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		if ( ! empty( $q->posts ) ) {
			$ids = array_map( 'intval', $q->posts );
		}

		// 2) Try single important terms.
		if ( count( $ids ) < 2 ) {
			foreach ( array_slice( $terms, 0, 5 ) as $term ) {
				$q2 = new \WP_Query(
					array(
						'post_type'              => 'product',
						'post_status'            => 'publish',
						's'                      => $term,
						'posts_per_page'         => $limit,
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					)
				);
				foreach ( (array) $q2->posts as $pid ) {
					$pid = (int) $pid;
					if ( ! in_array( $pid, $ids, true ) ) {
						$ids[] = $pid;
					}
					if ( count( $ids ) >= $limit ) {
						break 2;
					}
				}
			}
		}

		// 3) Latest published products as last resort.
		if ( count( $ids ) < 1 ) {
			$q3 = new \WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => $limit,
					'orderby'                => 'date',
					'order'                  => 'DESC',
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			$ids = array_map( 'intval', (array) $q3->posts );
		}

		return array_slice( array_values( array_unique( $ids ) ), 0, $limit );
	}

	/**
	 * Build a chat card without price_html (avoids multi-currency recursion).
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	private function safe_card( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id < 1 ) {
			return null;
		}

		$post = get_post( $product_id );
		if ( ! $post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		$image = '';
		$thumb = get_post_thumbnail_id( $product_id );
		if ( $thumb ) {
			$src = wp_get_attachment_image_src( $thumb, 'medium' );
			if ( ! empty( $src[0] ) ) {
				$image = $src[0];
			}
		}

		// Raw meta only — do not call WC price getters/filters.
		$price = get_post_meta( $product_id, '_price', true );
		if ( '' === $price || null === $price ) {
			$price = get_post_meta( $product_id, '_regular_price', true );
		}
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
		$price_label = ( '' !== $price && null !== $price ) ? trim( $currency . ' ' . $price ) : '';

		return array(
			'id'        => $product_id,
			'name'      => get_the_title( $product_id ),
			'price'     => $price_label,
			'image'     => $image,
			'permalink' => get_permalink( $product_id ),
			'in_stock'  => true,
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
				'en' => 'I could not find matching shirts just now. Tell me a colour, fabric (e.g. linen), or style and I will try again.',
				'it' => 'Non trovo camicie corrispondenti al momento. Dimmi un colore, un tessuto o uno stile e riprovo.',
				'nb' => 'Jeg fant ingen matchende skjorter akkurat nå. Si en farge, stoff eller stil, så prøver jeg igjen.',
				'vi' => 'Hiện tôi chưa tìm thấy áo phù hợp. Cho tôi biết màu, chất liệu hoặc kiểu dáng để tìm lại nhé.',
			);
			return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
		}

		$map = array(
			'en' => 'Here are some shirts from our shop. Tap one to select it — or tell me a colour/fabric for a tighter match.',
			'it' => 'Ecco alcune camicie dal nostro shop. Tocca una per selezionarla — oppure dimmi colore/tessuto per affinare.',
			'nb' => 'Her er noen skjorter fra butikken. Trykk for å velge — eller si farge/stoff hvis du vil snevre inn.',
			'vi' => 'Đây là một số áo trong cửa hàng. Chạm để chọn — hoặc cho thêm màu/chất liệu nếu muốn lọc kỹ hơn.',
		);
		return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
	}
}
