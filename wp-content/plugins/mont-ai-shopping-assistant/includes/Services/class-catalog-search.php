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
	 * Customer wants to see one specific shirt already in play — not a catalog dump.
	 *
	 * @param string $message Message.
	 * @return bool
	 */
	public function wants_specific_show( $message ) {
		$text = strtolower( trim( (string) $message ) );
		if ( '' === $text ) {
			return false;
		}
		if ( preg_match( '/\b(the one you|you (suggested|chose|choosen|chosen|picked|recommended)|that shirt|this shirt|show it|show me (it|that|this|the)|show (me )?first|vis (meg )?den|mostrami (quella|questa)|cho xem (nó|ao|áo))\b/i', $text ) ) {
			return true;
		}
		if ( preg_match( '/\bshow me (the )?shirt\b/i', $text ) && ! preg_match( '/show me shirts/i', $text ) ) {
			return true;
		}
		if ( preg_match( '/\b(show|see|vis)\b.+\b(the one|that one|this one|it to me|so i can see)\b/i', $text ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Customer wants a pick / “best one”, not a 20-question interview.
	 *
	 * @param string $message Message.
	 * @return bool
	 */
	public function wants_recommendation( $message ) {
		$text = strtolower( trim( (string) $message ) );
		if ( '' === $text ) {
			return false;
		}
		if ( preg_match( '/\b(best one|top pick|help me find|recommend|suggest|which (one|shirt)|pick (one|for me)|choose (one|for me)|similar please|something similar|suite on me|suit me)\b/i', $text ) ) {
			return true;
		}
		return false;
	}

	/**
	 * How many product cards to show for this turn.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return int 1-3
	 */
	public function card_limit( $message, array $history = array() ) {
		$text = strtolower( trim( (string) $message ) );
		if ( preg_match( '/\b(only one|just one|single (pick|one|shirt)|one pick|one shirt|pick one for me|choose one for me|your top pick|top pick for me|best one only)\b/i', $text ) ) {
			return 1;
		}
		if ( $this->wants_recommendation( $message ) && preg_match( '/\b(pick|choose|one)\b/i', $text ) && ! preg_match( '/\b(options|shirts|more)\b/i', $text ) ) {
			return 1;
		}
		unset( $history );
		return 3;
	}

	/**
	 * Whether to run catalog browse.
	 * Only when the customer clearly wants to see products — not for chat Q&A.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return bool
	 */
	public function should_browse( $message, array $history = array() ) {
		$text = strtolower( trim( (string) $message ) );
		if ( '' === $text ) {
			return false;
		}

		// “Show it / the one you suggested” is a focused show, not a catalog dump.
		if ( $this->wants_specific_show( $text ) ) {
			return false;
		}

		// Explicit catalog browse — avoid bare “show me” (matches “show me the shirt”).
		$explicit = array(
			'show shirts', 'show shirt', 'show fabrics', 'show products',
			'list shirts', 'list products', 'list fabrics',
			'browse', 'see shirts', 'see products', 'see fabrics',
			'what shirts do you have', 'which shirts do you have',
			'show me some', 'show me shirts', 'any shirts',
			'recommend shirts', 'recommend a shirt',
			'vis skjorter', 'se skjorter', 'finn skjorter',
			'mostra camicie', 'vediamo camicie',
			'xem áo', 'liệt kê', 'liet ke',
			'show wholesale', 'b2b fabrics',
		);
		foreach ( $explicit as $phrase ) {
			if ( false !== strpos( $text, $phrase ) ) {
				return true;
			}
		}

		// Short browse commands.
		if ( preg_match( '/^(show|list|browse|see)\s+(shirts?|products?|fabrics?)?$/i', $text ) ) {
			return true;
		}

		// “looking for / need / want” + product type, but NOT size/shipping/advice questions.
		if ( preg_match( '/(ship|shipping|deliver|arrival|arrive|frakt|levering|return|retur|how long|when will|moq)/i', $text ) ) {
			return false;
		}

		if ( preg_match( '/\b(looking for|need|want|find|search for)\b.{0,40}\b(shirt|shirts|skjorte|camicia|linen|oxford|fabric|fabrics)\b/i', $text ) ) {
			return true;
		}

		// Colour/style shopping shorthand: "blue linen shirts", "business shirts".
		if ( preg_match( '/\b(blue|white|black|linen|oxford|business|casual|wedding|formal)\b.{0,20}\b(shirt|shirts|skjorte|camicia|fabric)\b/i', $text ) ) {
			return true;
		}

		unset( $history );
		return false;
	}

	/**
	 * Colour dictionary (longest needles first).
	 *
	 * @return array
	 */
	private function color_map() {
		return array(
			'light blue' => 'light blue',
			'dark blue'  => 'dark blue',
			'navy blue'  => 'navy',
			'navy'       => 'navy',
			'blue'       => 'blue',
			'blå'        => 'blue',
			'blu'        => 'blue',
			'white'      => 'white',
			'bianca'     => 'white',
			'bianco'     => 'white',
			'hvit'       => 'white',
			'black'      => 'black',
			'grey'       => 'grey',
			'gray'       => 'grey',
			'green'      => 'green',
			'emerald'    => 'green',
			'red'        => 'red',
			'olive'      => 'olive',
			'pink'       => 'pink',
			'beige'      => 'beige',
			'check'      => 'check',
		);
	}

	/**
	 * User-only chat text (never product titles from assistant cards).
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return string
	 */
	private function user_blob( $message, array $history ) {
		$parts = array();
		foreach ( $history as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			$role = isset( $h['role'] ) ? $h['role'] : 'user';
			if ( 'assistant' === $role ) {
				continue;
			}
			$parts[] = strtolower( (string) $h['content'] );
		}
		$parts[] = strtolower( (string) $message );
		return implode( "\n", $parts );
	}

	/**
	 * Pull colour / fabric / fit / size / named shirt from the chat.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @return array
	 */
	public function extract_prefs( $message, array $history = array() ) {
		// IMPORTANT: only user turns — assistant history contains product names like
		// “RED, NAVY BLUE…” which previously poisoned colour prefs.
		$blob = $this->user_blob( $message, $history );
		$now  = strtolower( trim( (string) $message ) );

		$prefs = array(
			'color'           => '',
			'exclude_colors'  => array(),
			'fabric'          => '',
			'fit'             => '',
			'size'            => '',
			'occasion'        => '',
			'name'            => '',
			'sku'             => '',
			'avoid_shown'     => false,
		);

		$colors = $this->color_map();

		// “I don’t like red / no red / not red”
		foreach ( $colors as $needle => $value ) {
			if ( preg_match( '/\b(don\'?t like|do not like|hate|no|not|without|skip|ikke)\b[^.\n]{0,20}\b' . preg_quote( $needle, '/' ) . '\b/i', $blob )
				|| preg_match( '/\b' . preg_quote( $needle, '/' ) . '\b[^.\n]{0,12}\b(no|not for me|off)\b/i', $blob ) ) {
				$prefs['exclude_colors'][] = $value;
				$prefs['avoid_shown']      = true;
			}
		}
		$prefs['exclude_colors'] = array_values( array_unique( $prefs['exclude_colors'] ) );

		// Preferred colour — latest user message first, then older user turns.
		foreach ( array( $now, $blob ) as $scope ) {
			foreach ( $colors as $needle => $value ) {
				if ( in_array( $value, $prefs['exclude_colors'], true ) ) {
					continue;
				}
				// Skip colour words that only appear inside “don’t like …”
				if ( preg_match( '/\b(don\'?t like|do not like|hate|no|not|without)\b[^.\n]{0,20}\b' . preg_quote( $needle, '/' ) . '\b/i', $scope ) ) {
					continue;
				}
				if ( preg_match( '/\b' . preg_quote( $needle, '/' ) . '\b/i', $scope ) ) {
					$prefs['color'] = $value;
					break 2;
				}
			}
		}

		$fabrics = array( 'linen' => 'linen', 'oxford' => 'oxford', 'flannel' => 'flannel', 'twill' => 'twill', 'poplin' => 'poplin', 'cotton' => 'cotton' );
		foreach ( $fabrics as $needle => $value ) {
			if ( preg_match( '/\b' . preg_quote( $needle, '/' ) . '\b/i', $blob ) ) {
				$prefs['fabric'] = $value;
				break;
			}
		}

		if ( preg_match( '/\b(extra\s*slim|slim\s*fit|slim|classic|regular|body\s*fit)\b/i', $blob, $m ) ) {
			$fit = strtolower( $m[1] );
			$prefs['fit'] = ( false !== strpos( $fit, 'slim' ) ) ? 'slim' : $fit;
		}

		if ( preg_match( '/\b(size\s*)?(3[7-9]|4[0-6])\b/', $blob, $m ) ) {
			$prefs['size'] = $m[2];
		}

		if ( preg_match( '/\b(casual|everyday|daily|business|office|wedding|formal)\b/i', $blob, $m ) ) {
			$occ = strtolower( $m[1] );
			$prefs['occasion'] = in_array( $occ, array( 'everyday', 'daily' ), true ) ? 'casual' : $occ;
		}

		if ( preg_match( '/#([a-z]{1,4}\d{1,4})/i', $now, $m ) ) {
			$prefs['sku'] = strtoupper( $m[1] );
		}

		if ( preg_match( '/["“]([^"”]{3,60})["”]/u', $message, $m ) ) {
			$prefs['name'] = trim( $m[1] );
		} elseif ( preg_match( '/\b(camicia\s+[a-zàèéìòù]+)\b/iu', $message, $m ) ) {
			$prefs['name'] = $m[1];
		}

		return $prefs;
	}

	/**
	 * Customer is rejecting a colour / style — re-pick without AI.
	 *
	 * @param string $message Message.
	 * @return bool
	 */
	public function wants_reselect( $message ) {
		$text = strtolower( trim( (string) $message ) );
		if ( '' === $text ) {
			return false;
		}
		if ( preg_match( '/\b(don\'?t like|do not like|hate|not a fan|no red|no blue|something else|another one|different|other options|not that|ikke liker)\b/i', $text ) ) {
			return true;
		}
		foreach ( array_keys( $this->color_map() ) as $needle ) {
			if ( preg_match( '/\b(no|not|without)\s+' . preg_quote( $needle, '/' ) . '\b/i', $text ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Product IDs the assistant already showed (from history memory line).
	 *
	 * @param array $history History.
	 * @return int[]
	 */
	public function remembered_product_ids( array $history ) {
		$ids = array();
		foreach ( $history as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			if ( preg_match_all( '/product\s*#\s*(\d+)/i', (string) $h['content'], $m ) ) {
				foreach ( $m[1] as $id ) {
					$ids[] = (int) $id;
				}
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Last recommended product id from history.
	 *
	 * @param array $history History.
	 * @return int
	 */
	public function remembered_recommended_id( array $history ) {
		foreach ( array_reverse( $history ) as $h ) {
			if ( empty( $h['content'] ) ) {
				continue;
			}
			if ( preg_match( '/recommended product\s*#\s*(\d+)/i', (string) $h['content'], $m ) ) {
				return (int) $m[1];
			}
		}
		$ids = $this->remembered_product_ids( $history );
		return $ids ? (int) end( $ids ) : 0;
	}

	/**
	 * Find live products by name / SKU.
	 *
	 * @param string $name    Name.
	 * @param string $channel Channel.
	 * @param int    $limit   Limit.
	 * @return array
	 */
	public function find_by_name( $name, $channel = 'b2c', $limit = 3 ) {
		$name = trim( (string) $name );
		if ( strlen( $name ) < 2 ) {
			return $this->empty_result();
		}
		return $this->query_to_result( $name, array( $name ), $limit, $channel, false );
	}

	/**
	 * Card for one product id.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $channel    Channel.
	 * @return array|null
	 */
	public function card( $product_id, $channel = 'b2c' ) {
		return $this->safe_card( (int) $product_id, $channel );
	}

	/**
	 * Cards for ids.
	 *
	 * @param int[]  $ids     IDs.
	 * @param string $channel Channel.
	 * @return array
	 */
	public function cards_for_ids( array $ids, $channel = 'b2c' ) {
		$cards = array();
		foreach ( $ids as $id ) {
			$card = $this->safe_card( (int) $id, $channel );
			if ( $card ) {
				$cards[] = $card;
			}
		}
		return $cards;
	}

	/**
	 * Compact catalog lines for the model (real names only).
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @param string $channel Channel.
	 * @param int    $limit   Limit.
	 * @return string
	 */
	public function catalog_snapshot( $message, array $history, $channel = 'b2c', $limit = 8 ) {
		$found = $this->recommend( $message, $history, $limit, $channel );
		if ( empty( $found['cards'] ) ) {
			return 'CATALOG: no matching shirts for the current filters. Call search_products with broader keywords.';
		}
		$lines = array();
		foreach ( $found['cards'] as $card ) {
			$lines[] = '#' . $card['id'] . ' ' . $card['name'] . ' (' . $card['price'] . ')';
		}
		return "LIVE CATALOG (only these names exist — never invent others):\n- " . implode( "\n- ", $lines );
	}

	/**
	 * Smart recommend using conversation prefs.
	 *
	 * @param string $message Message.
	 * @param array  $history History.
	 * @param int    $limit   Limit.
	 * @param string $channel Channel.
	 * @return array
	 */
	public function recommend( $message, array $history = array(), $limit = 3, $channel = 'b2c' ) {
		$prefs = $this->extract_prefs( $message, $history );
		$shown = $this->remembered_product_ids( $history );

		if ( $prefs['name'] && empty( $prefs['exclude_colors'] ) ) {
			$named = $this->find_by_name( $prefs['name'], $channel, $limit );
			if ( ! empty( $named['count'] ) ) {
				$named['cards'] = $this->rank_cards( $named['cards'], $prefs, $shown, $limit );
				$named['count'] = count( $named['cards'] );
				$named['recommended_id'] = ! empty( $named['cards'][0]['id'] ) ? (int) $named['cards'][0]['id'] : 0;
				$named['prefs'] = $prefs;
				return $named;
			}
		}

		$terms = array();
		if ( ! empty( $prefs['color'] ) ) {
			$terms[] = $prefs['color'];
		}
		foreach ( array( 'fabric', 'occasion', 'sku', 'fit' ) as $key ) {
			if ( ! empty( $prefs[ $key ] ) ) {
				$terms[] = $prefs[ $key ];
			}
		}
		// When excluding a colour, search for alternatives (blue/white/oxford) not the rejected colour.
		if ( empty( $terms ) && ! empty( $prefs['exclude_colors'] ) ) {
			$terms = array( 'shirt', 'oxford', 'blue', 'white' );
		}
		if ( ! $terms ) {
			$terms = $this->build_terms( $message, $history );
			// Drop colour words that are only exclusions / noise from “need shirt”.
			$terms = array_values(
				array_filter(
					$terms,
					function ( $t ) use ( $prefs ) {
						$t = strtolower( (string) $t );
						if ( in_array( $t, array( 'asian', 'looking', 'man', 'suit', 'suite', 'me', 'suggest', 'choose', 'top', 'pick' ), true ) ) {
							return false;
						}
						if ( in_array( $t, $prefs['exclude_colors'], true ) ) {
							return false;
						}
						return true;
					}
				)
			);
			if ( ! $terms ) {
				$terms = array( 'shirt' );
			}
		}

		$query  = implode( ' ', array_slice( $terms, 0, 5 ) );
		$pool   = max( 8, $limit * 4 );
		$result = $this->query_to_result( $query, $terms, $pool, $channel, true );
		$result['cards'] = $this->rank_cards( isset( $result['cards'] ) ? $result['cards'] : array(), $prefs, $shown, $limit );
		$result['count'] = count( $result['cards'] );
		$result['prefs'] = $prefs;
		$result['recommended_id'] = ! empty( $result['cards'][0]['id'] ) ? (int) $result['cards'][0]['id'] : 0;
		return $result;
	}

	/**
	 * Filter + score cards by prefs / exclusions.
	 *
	 * @param array $cards Cards.
	 * @param array $prefs Prefs.
	 * @param int[] $shown Already shown ids.
	 * @param int   $limit Limit.
	 * @return array
	 */
	private function rank_cards( array $cards, array $prefs, array $shown, $limit ) {
		$scored = array();
		foreach ( $cards as $card ) {
			$name = strtolower( (string) ( $card['name'] ?? '' ) );
			$skip = false;
			foreach ( (array) ( $prefs['exclude_colors'] ?? array() ) as $ex ) {
				$ex = strtolower( (string) $ex );
				if ( $ex && false !== strpos( $name, $ex ) ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}

			$score = 0;
			if ( ! empty( $prefs['color'] ) && false !== strpos( $name, strtolower( $prefs['color'] ) ) ) {
				$score += 50;
			}
			if ( ! empty( $prefs['fabric'] ) && false !== strpos( $name, strtolower( $prefs['fabric'] ) ) ) {
				$score += 20;
			}
			// Prefer solid / non-check for a cleaner everyday pick when no colour set.
			if ( empty( $prefs['color'] ) && ! preg_match( '/check|dog\s*tooth|stripe|pinstripe/i', $name ) ) {
				$score += 8;
			}
			if ( ! empty( $prefs['avoid_shown'] ) && in_array( (int) ( $card['id'] ?? 0 ), $shown, true ) ) {
				$score -= 40;
			} elseif ( in_array( (int) ( $card['id'] ?? 0 ), $shown, true ) ) {
				$score -= 5;
			}
			// Soft bias: light/blue/white often flatter slim fit everyday looks.
			if ( ! empty( $prefs['fit'] ) && 'slim' === $prefs['fit'] && preg_match( '/\b(light blue|white|blue|oxford)\b/i', $name ) ) {
				$score += 12;
			}

			$scored[] = array( 'score' => $score, 'card' => $card );
		}

		usort(
			$scored,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		$out = array();
		foreach ( $scored as $row ) {
			$out[] = $row['card'];
			if ( count( $out ) >= $limit ) {
				break;
			}
		}

		// If exclusions wiped everything, fall back to unfiltered minus excluded names from a broader pool.
		if ( ! $out && ! empty( $prefs['exclude_colors'] ) ) {
			foreach ( $cards as $card ) {
				$name = strtolower( (string) ( $card['name'] ?? '' ) );
				$bad  = false;
				foreach ( $prefs['exclude_colors'] as $ex ) {
					if ( $ex && false !== strpos( $name, strtolower( (string) $ex ) ) ) {
						$bad = true;
						break;
					}
				}
				if ( $bad ) {
					continue;
				}
				$out[] = $card;
				if ( count( $out ) >= $limit ) {
					break;
				}
			}
		}

		return $out;
	}

	/**
	 * Join history text.
	 *
	 * @param array $history History.
	 * @return string
	 */
	private function history_blob( array $history ) {
		$parts = array();
		foreach ( $history as $h ) {
			if ( ! empty( $h['content'] ) ) {
				$parts[] = (string) $h['content'];
			}
		}
		return implode( "\n", $parts );
	}

	/**
	 * Empty search payload.
	 *
	 * @return array
	 */
	private function empty_result() {
		return array(
			'cards'           => array(),
			'choices'         => null,
			'count'           => 0,
			'query'           => '',
			'terms'           => array(),
			'recommended_id'  => 0,
		);
	}

	/**
	 * Run WP search → cards (no duplicate choice grid).
	 *
	 * @param string $query          Query.
	 * @param array  $terms          Terms.
	 * @param int    $limit          Limit.
	 * @param string $channel        Channel.
	 * @param bool   $allow_fallback Latest products if nothing matches.
	 * @return array
	 */
	private function query_to_result( $query, array $terms, $limit, $channel, $allow_fallback ) {
		$limit   = max( 1, min( 8, (int) $limit ) );
		$channel = ( 'b2b' === $channel ) ? 'b2b' : 'b2c';
		$ids     = $this->query_product_ids( $query, $terms, $limit, $channel, $allow_fallback );
		$cards   = array();
		foreach ( $ids as $id ) {
			$card = $this->safe_card( (int) $id, $channel );
			if ( $card ) {
				$cards[] = $card;
			}
		}
		return array(
			'cards'          => $cards,
			'choices'        => null,
			'count'          => count( $cards ),
			'query'          => $query,
			'terms'          => $terms,
			'channel'        => $channel,
			'recommended_id' => ! empty( $cards[0]['id'] ) ? (int) $cards[0]['id'] : 0,
		);
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
	public function search( $message, array $history = array(), $limit = 3, $channel = 'b2c' ) {
		try {
			return $this->recommend( $message, $history, $limit, $channel );
		} catch ( \Throwable $e ) {
			Plugin::log( 'Catalog_Search::search error', array( 'error' => $e->getMessage() ) );
			return $this->empty_result();
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
	private function query_product_ids( $query, array $terms, $limit, $channel = 'b2c', $allow_fallback = true ) {
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

		try {
			$index = new \Mont_AI_Assistant\Product\Product_Index();
			$hits  = $index->search( $query ? $query : implode( ' ', array_slice( $terms, 0, 3 ) ), $limit );
			foreach ( $hits as $hit ) {
				if ( ! empty( $hit['id'] ) ) {
					$ids[] = (int) $hit['id'];
				}
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
		}

		if ( count( $ids ) < $limit ) {
			$q = new \WP_Query(
				array_merge(
					$base,
					array(
						's' => $query ? $query : ( 'b2b' === $channel ? '' : implode( ' ', array_slice( $terms, 0, 3 ) ) ),
					)
				)
			);
			foreach ( (array) $q->posts as $pid ) {
				$pid = (int) $pid;
				if ( ! in_array( $pid, $ids, true ) ) {
					$ids[] = $pid;
				}
				if ( count( $ids ) >= $limit ) {
					break;
				}
			}
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

		if ( $allow_fallback && count( $ids ) < 1 ) {
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
				$map = array(
					'en' => 'Hmm, I couldn’t find a match in the B2B fabrics just now. Try a colour or quality — or use the tabs on this page.',
					'it' => 'Mm, non trovo un match nei tessuti B2B al momento. Prova un colore o una qualità — oppure usa le tab sulla pagina.',
					'nb' => 'Hmm, fant ingen match i B2B-stoffene akkurat nå. Prøv en farge eller kvalitet — eller bruk fanene på siden.',
					'vi' => 'Hmm, mình chưa thấy vải B2B phù hợp. Thử màu hoặc chất liệu — hoặc dùng tab trên trang nhé.',
				);
				return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
			}
			$map = array(
				'en' => 'Here are some wholesale fabrics that look like a fit. Open one to set sizes — just remember MOQ — then add to your B2B cart.',
				'it' => 'Ecco alcuni tessuti wholesale che potrebbero fare al caso. Aprine uno per le taglie (vale il MOQ), poi aggiungi al carrello B2B.',
				'nb' => 'Her er noen grossiststoffer som kan passe. Åpne ett for størrelser — husk MOQ — og legg i B2B-kurven.',
				'vi' => 'Đây là một số vải sỉ khá hợp. Mở một mẫu để chọn size — nhớ MOQ — rồi thêm vào giỏ B2B nhé.',
			);
			return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
		}
		if ( $count < 1 ) {
			$map = array(
				'en' => 'I couldn’t quite find a match there. Want to try a colour, fabric (like linen), or a style and I’ll look again?',
				'it' => 'Non trovo proprio una corrispondenza. Proviamo con un colore, un tessuto (tipo lino) o uno stile?',
				'nb' => 'Fant ikke noe som traff helt. Vil du prøve en farge, et stoff (f.eks. lin) eller en stil, så sjekker jeg på nytt?',
				'vi' => 'Mình chưa tìm thấy mẫu hợp lắm. Thử thêm màu, chất liệu (ví dụ linen) hoặc kiểu dáng nhé?',
			);
			return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
		}

		$map = array(
			'en' => 'Nice — here are a few shirts from the shop. Tap one you like, or tell me a colour/fabric if you want me to narrow it down.',
			'it' => 'Ottimo — ecco qualche camicia dello shop. Tocca quella che ti piace, oppure dimmi colore/tessuto se vuoi restringere.',
			'nb' => 'Supert — her er noen skjorter fra butikken. Trykk på en du liker, eller si farge/stoff hvis du vil at jeg snevrer inn.',
			'vi' => 'Đây là vài mẫu trong shop. Chạm vào áo bạn thích, hoặc cho thêm màu/chất liệu nếu muốn mình lọc kỹ hơn.',
		);
		return isset( $map[ $language ] ) ? $map[ $language ] : $map['en'];
	}
}
