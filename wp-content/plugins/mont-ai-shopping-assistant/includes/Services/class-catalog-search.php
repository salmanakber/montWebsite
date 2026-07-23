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
			'b2b'      => array( 'wholesale', 'moq', 'fabric' ),
			'wholesale'=> array( 'wholesale', 'b2b', 'moq' ),
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
			'wholesale', 'b2b', 'fabric', 'moq',
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
			if ( false !== strpos( $prev, 'shirt' ) || false !== strpos( $prev, 'looking for' ) || false !== strpos( $prev, 'occasion' ) || false !== strpos( $prev, 'b2b' ) ) {
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
	 * @param string $channel Channel b2c|b2b.
	 * @return array
	 */
	public function search( $message, array $history = array(), $limit = 6, $channel = 'b2c' ) {
		$empty = array(
			'cards'   => array(),
			'choices' => null,
			'count'   => 0,
			'query'   => '',
			'terms'   => array(),
		);

		try {
			$limit   = max( 1, min( 12, (int) $limit ) );
			$channel = ( 'b2b' === $channel ) ? 'b2b' : 'b2c';
			$terms   = $this->build_terms( $message, $history );
			$query   = implode( ' ', array_slice( $terms, 0, 5 ) );

			$ids          = $this->query_product_ids( $query, $terms, $limit, $channel );
			$cards        = array();
			$choice_items = array();

			foreach ( $ids as $id ) {
				$card = $this->safe_card( (int) $id, $channel );
				if ( ! $card ) {
					continue;
				}
				$cards[]        = $card;
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
					'title'   => ( 'b2b' === $channel ) ? 'Tap a B2B fabric' : 'Tap a shirt to select it',
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
				'channel' => $channel,
			);
		} catch ( \Throwable $e ) {
			Plugin::log( 'Catalog_Search::search error', array( 'error' => $e->getMessage() ) );
			return $empty;
		}
	}

	/**
	 * Meta query for B2B channel.
	 *
	 * @param string $channel Channel.
	 * @return array
	 */
	private function channel_meta_query( $channel ) {
		if ( 'b2b' !== $channel ) {
			return array();
		}
		return array(
			'relation' => 'OR',
			array(
				'key'     => '_b2b_product',
				'value'   => 'yes',
				'compare' => '=',
			),
			array(
				'key'     => '_b2b_product',
				'value'   => '1',
				'compare' => '=',
			),
		);
	}

	/**
	 * Resolve product IDs via WP_Query (most reliable).
	 *
	 * @param string $query   Query string.
	 * @param array  $terms   Terms.
	 * @param int    $limit   Limit.
	 * @param string $channel Channel.
	 * @return int[]
	 */
	private function query_product_ids( $query, array $terms, $limit, $channel = 'b2c' ) {
		$ids        = array();
		$meta_query = $this->channel_meta_query( $channel );

		$base = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		);
		if ( $meta_query ) {
			$base['meta_query'] = $meta_query;
		}

		$q = new \WP_Query(
			array_merge(
				$base,
				array(
					's' => $query ? $query : ( 'b2b' === $channel ? '' : 'shirt' ),
				)
			)
		);
		if ( ! empty( $q->posts ) ) {
			$ids = array_map( 'intval', $q->posts );
		}

		if ( count( $ids ) < 2 ) {
			foreach ( array_slice( $terms, 0, 5 ) as $term ) {
				$q2 = new \WP_Query(
					array_merge(
						$base,
						array(
							's' => $term,
						)
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

		if ( count( $ids ) < 1 ) {
			$q3_args = array_merge(
				$base,
				array(
					'orderby' => 'date',
					'order'   => 'DESC',
				)
			);
			$q3  = new \WP_Query( $q3_args );
			$ids = array_map( 'intval', (array) $q3->posts );
		}

		return array_slice( array_values( array_unique( $ids ) ), 0, $limit );
	}

	/**
	 * Build a chat card without price_html (avoids multi-currency recursion).
	 *
	 * @param int    $product_id Product ID.
	 * @param string $channel    Channel.
	 * @return array|null
	 */
	private function safe_card( $product_id, $channel = 'b2c' ) {
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

		$price = get_post_meta( $product_id, '_price', true );
		if ( '' === $price || null === $price ) {
			$price = get_post_meta( $product_id, '_regular_price', true );
		}
		$currency    = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
		$price_label = ( '' !== $price && null !== $price ) ? trim( $currency . ' ' . $price ) : '';

		$moq = get_post_meta( $product_id, '_moq', true );
		if ( 'b2b' === $channel && $moq ) {
			$price_label = trim( $price_label . ' · MOQ ' . $moq );
		}

		$permalink = get_permalink( $product_id );
		if ( 'b2b' === $channel ) {
			$b2b_page = $this->b2b_page_url();
			if ( $b2b_page ) {
				$permalink = $b2b_page;
			}
		}

		return array(
			'id'        => $product_id,
			'name'      => get_the_title( $product_id ),
			'price'     => $price_label,
			'image'     => $image,
			'permalink' => $permalink,
			'in_stock'  => true,
			'channel'   => $channel,
			'moq'       => $moq,
		);
	}

	/**
	 * Monte B2B listing page URL.
	 *
	 * @return string
	 */
	private function b2b_page_url() {
		$page = get_page_by_path( 'monte-connected-b2b' );
		if ( $page ) {
			return get_permalink( $page );
		}
		$page = get_page_by_title( 'Monte Connected B2B' );
		if ( $page ) {
			return get_permalink( $page );
		}
		return '';
	}

	/**
	 * Friendly browse copy.
	 *
	 * @param string $language Language.
	 * @param int    $count    Count.
	 * @param string $message  User message.
	 * @param string $channel  Channel.
	 * @return string
	 */
	public function browse_message( $language, $count, $message, $channel = 'b2c' ) {
		$language = Language_Manager::normalize( $language );
		if ( 'b2b' === $channel ) {
			if ( $count < 1 ) {
				return __( 'I could not find matching B2B / wholesale fabrics yet. Ask for a colour or quality, or use the category tabs on this page.', 'mont-ai-assistant' );
			}
			return __( 'Here are B2B fabrics that match. Open one on the wholesale portal to set size breakdowns — MOQ applies — then add to your B2B cart.', 'mont-ai-assistant' );
		}
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
