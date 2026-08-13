<?php
/**
 * Internal tools the AI can call.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Services;

use Mont_AI_Assistant\Cart\Cart_Service;
use Mont_AI_Assistant\Product\Custom_Options;
use Mont_AI_Assistant\Product\Product_Index;
use Mont_AI_Assistant\Product\Product_Knowledge;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tool_Executor
 *
 * Tool schemas are kept simple (flat string fields) to avoid Groq
 * "Failed to call a function" malformed JSON errors.
 */
class Tool_Executor {

	/**
	 * Tool definitions for the model.
	 *
	 * @return array
	 */
	public function definitions() {
		// String types for IDs/numbers — Groq rejects tool calls when the model
		// sends "123" but the schema expects integer.
		$id = array(
			'type'        => 'string',
			'description' => 'Product ID as a number string, e.g. "123"',
		);
		$qty = array(
			'type'        => 'string',
			'description' => 'Quantity as a number string, e.g. "1"',
		);

		return array(
			$this->fn(
				'search_products',
				'Search the LIVE WooCommerce catalog for any shopping intent (need shirt, slim fit, not red, gift, casual, etc.). Always call this before naming shirts. Use natural query text.',
				array(
					'query' => array( 'type' => 'string', 'description' => 'Natural search: colour, fabric, fit, exclusions like “no red”, SKU, or style' ),
					'limit' => array( 'type' => 'string', 'description' => 'Max results 1-3 as string. Prefer 3 for browsing, 1 for a single pick.' ),
				),
				array( 'query' )
			),
			$this->fn(
				'get_product',
				'Load one product by ID and attach its photo card. Use this when they ask to see the shirt you recommended.',
				array(
					'product_id' => $id,
				),
				array( 'product_id' )
			),
			$this->fn(
				'get_variations',
				'List size/fit variations and stock for a product ID.',
				array(
					'product_id' => $id,
				),
				array( 'product_id' )
			),
			$this->fn(
				'get_custom_options',
				'Load option schema for a chosen product. Do NOT show buttons yet — next call present_choices for ONE option.',
				array(
					'product_id' => $id,
				),
				array( 'product_id' )
			),
			$this->fn(
				'present_choices',
				'Show tappable buttons/images. Use ONLY after configuring a specific product — never on a greeting. One option group at a time.',
				array(
					'title'       => array( 'type' => 'string', 'description' => 'Short friendly question' ),
					'field'       => array( 'type' => 'string', 'description' => 'body_fit, size, collar_type, cuff_type, quantity' ),
					'product_id'  => $id,
					'option_key'  => array( 'type' => 'string', 'description' => 'Same as field when loading from product options' ),
					'choices_csv' => array( 'type' => 'string', 'description' => 'Optional Label|imageUrl,Label2 list' ),
				),
				array( 'title', 'field' )
			),
			$this->fn(
				'validate_selection',
				'Validate required options before add_to_cart. All fields as strings.',
				array(
					'product_id'  => $id,
					'body_fit'    => array( 'type' => 'string' ),
					'size'        => array( 'type' => 'string' ),
					'collar_type' => array( 'type' => 'string' ),
					'cuff_type'   => array( 'type' => 'string' ),
					'quantity'    => $qty,
				),
				array( 'product_id' )
			),
			$this->fn(
				'add_to_cart',
				'Add configured product to cart after validation succeeds.',
				array(
					'product_id'  => $id,
					'body_fit'    => array( 'type' => 'string' ),
					'size'        => array( 'type' => 'string' ),
					'collar_type' => array( 'type' => 'string' ),
					'cuff_type'   => array( 'type' => 'string' ),
					'quantity'    => $qty,
				),
				array( 'product_id' )
			),
			$this->fn(
				'get_cart',
				'View cart contents and totals.',
				array(),
				array()
			),
			$this->fn(
				'lookup_order',
				'Look up a customer order by order number + billing email. Use when they ask about order status, delivery, what they ordered, tracking.',
				array(
					'order_number' => array( 'type' => 'string', 'description' => 'Order number from confirmation email' ),
					'email'        => array( 'type' => 'string', 'description' => 'Billing email used at checkout' ),
				),
				array( 'order_number', 'email' )
			),
			$this->fn(
				'submit_support_request',
				'Submit a complaint or support message to the shop team. Use when they report a problem, wrong item, quality issue, or need human follow-up.',
				array(
					'email'    => array( 'type' => 'string', 'description' => 'Customer email for reply' ),
					'message'  => array( 'type' => 'string', 'description' => 'Full description of the issue' ),
					'name'     => array( 'type' => 'string', 'description' => 'Customer name if known' ),
					'order_id' => array( 'type' => 'string', 'description' => 'Related order number if any' ),
				),
				array( 'email', 'message' )
			),
		);
	}

	/**
	 * Support-only tools — no product search, no ticket submit (local PHP handles submit).
	 *
	 * @return array
	 */
	public function support_definitions() {
		return array(
			$this->fn(
				'lookup_order',
				'Look up a customer order by order number + billing email. Use when they ask about order status, delivery, what they ordered, tracking.',
				array(
					'order_number' => array( 'type' => 'string', 'description' => 'Order number from confirmation email' ),
					'email'        => array( 'type' => 'string', 'description' => 'Billing email used at checkout' ),
				),
				array( 'order_number', 'email' )
			),
		);
	}

	/**
	 * Execute a tool by name.
	 *
	 * @param string $name Name.
	 * @param array  $args Args.
	 * @return array
	 */
	public function execute( $name, array $args ) {
		$args = $this->normalize_args( $args );

		switch ( $name ) {
			case 'search_products':
				return $this->search_products( $args );
			case 'get_product':
				return $this->get_product( $args );
			case 'get_custom_options':
				return $this->get_custom_options( $args );
			case 'present_choices':
				return $this->present_choices( $args );
			case 'validate_selection':
				return $this->validate_selection( $args );
			case 'add_to_cart':
				return $this->add_to_cart( $args );
			case 'get_cart':
				return ( new Cart_Service() )->get_cart();
			case 'lookup_order':
				return ( new Order_Service() )->lookup(
					isset( $args['order_number'] ) ? $args['order_number'] : '',
					isset( $args['email'] ) ? $args['email'] : ''
				);
			case 'submit_support_request':
				return ( new Order_Service() )->submit_complaint(
					isset( $args['email'] ) ? $args['email'] : '',
					isset( $args['message'] ) ? $args['message'] : '',
					isset( $args['name'] ) ? $args['name'] : '',
					isset( $args['order_id'] ) ? $args['order_id'] : ''
				);
			case 'get_variations':
				return $this->get_variations( $args );
			case 'update_cart_item':
				return ( new Cart_Service() )->update_item(
					isset( $args['cart_key'] ) ? $args['cart_key'] : '',
					isset( $args['quantity'] ) ? (int) $args['quantity'] : 1
				);
			case 'remove_cart_item':
				return ( new Cart_Service() )->remove_item(
					isset( $args['cart_key'] ) ? $args['cart_key'] : ''
				);
			default:
				return array( 'error' => 'Unknown tool: ' . $name );
		}
	}

	/**
	 * Coerce common tool args so Groq string/int mismatches never break the turn.
	 *
	 * @param array $args Raw args.
	 * @return array
	 */
	private function normalize_args( array $args ) {
		if ( isset( $args['product_id'] ) ) {
			$args['product_id'] = (int) preg_replace( '/\D+/', '', (string) $args['product_id'] );
		}
		if ( isset( $args['limit'] ) ) {
			$args['limit'] = (int) $args['limit'];
		}
		if ( isset( $args['quantity'] ) ) {
			$args['quantity'] = max( 1, (int) $args['quantity'] );
		}
		foreach ( array( 'title', 'field', 'option_key', 'choices_csv', 'body_fit', 'size', 'collar_type', 'cuff_type', 'query', 'cart_key' ) as $key ) {
			if ( isset( $args[ $key ] ) && ! is_string( $args[ $key ] ) ) {
				$args[ $key ] = is_scalar( $args[ $key ] ) ? (string) $args[ $key ] : '';
			}
		}
		return $args;
	}

	/**
	 * Normalize flat tool args into a selection array.
	 *
	 * @param array $args Args.
	 * @return array
	 */
	private function selection_from_args( array $args ) {
		if ( ! empty( $args['selection'] ) && is_array( $args['selection'] ) ) {
			$sel = $args['selection'];
		} elseif ( ! empty( $args['selection_json'] ) && is_string( $args['selection_json'] ) ) {
			$decoded = json_decode( $args['selection_json'], true );
			$sel     = is_array( $decoded ) ? $decoded : array();
		} else {
			$sel = array();
		}

		foreach ( array( 'body_fit', 'size', 'collar_type', 'cuff_type' ) as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				$sel[ $key ] = sanitize_text_field( $args[ $key ] );
			}
		}
		if ( isset( $args['quantity'] ) ) {
			$sel['quantity'] = max( 1, (int) $args['quantity'] );
		} elseif ( empty( $sel['quantity'] ) ) {
			$sel['quantity'] = 1;
		}

		return $sel;
	}

	/**
	 * Build visual choice group from a custom option schema entry.
	 *
	 * @param array $opt Option schema.
	 * @return array|null
	 */
	public static function choices_from_option( array $opt ) {
		if ( empty( $opt['key'] ) || empty( $opt['choices'] ) || ! is_array( $opt['choices'] ) ) {
			return null;
		}
		$items = array();
		foreach ( $opt['choices'] as $c ) {
			if ( is_array( $c ) ) {
				$items[] = array(
					'label' => isset( $c['label'] ) ? $c['label'] : ( isset( $c['value'] ) ? $c['value'] : '' ),
					'value' => isset( $c['label'] ) ? $c['label'] : ( isset( $c['value'] ) ? $c['value'] : '' ),
					'image' => isset( $c['image'] ) ? $c['image'] : '',
					'sub'   => isset( $c['sub_name'] ) ? $c['sub_name'] : '',
				);
			} else {
				$items[] = array(
					'label' => (string) $c,
					'value' => (string) $c,
					'image' => '',
					'sub'   => '',
				);
			}
		}
		if ( ! $items ) {
			return null;
		}
		$has_images = false;
		foreach ( $items as $item ) {
			if ( ! empty( $item['image'] ) ) {
				$has_images = true;
				break;
			}
		}
		return array(
			'title'      => isset( $opt['label'] ) ? $opt['label'] : $opt['key'],
			'field'      => $opt['key'],
			'type'       => $has_images ? 'image_buttons' : 'buttons',
			'choices'    => $items,
		);
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	private function search_products( array $args ) {
		$query   = isset( $args['query'] ) ? sanitize_text_field( $args['query'] ) : '';
		$limit   = isset( $args['limit'] ) ? (int) $args['limit'] : 0;
		$channel = isset( $args['channel'] ) && 'b2b' === $args['channel'] ? 'b2b' : 'b2c';

		$catalog = new Catalog_Search();
		if ( $limit < 1 ) {
			$limit = $catalog->card_limit( $query, array() );
		}
		$limit = max( 1, min( 3, $limit ) );
		$found   = $catalog->recommend( $query, array(), $limit, $channel );
		$cards   = isset( $found['cards'] ) ? $found['cards'] : array();

		// Also try the product index for exact title / SKU hits.
		if ( count( $cards ) < $limit && $query ) {
			$index = new Product_Index();
			$hits  = $index->search( $query, $limit );
			$knowledge = new Product_Knowledge();
			foreach ( $hits as $hit ) {
				if ( empty( $hit['id'] ) ) {
					continue;
				}
				$already = false;
				foreach ( $cards as $c ) {
					if ( (int) $c['id'] === (int) $hit['id'] ) {
						$already = true;
						break;
					}
				}
				if ( $already ) {
					continue;
				}
				$card = $knowledge->card( (int) $hit['id'] );
				if ( $card ) {
					$cards[] = $card;
				}
				if ( count( $cards ) >= $limit ) {
					break;
				}
			}
		}

		$results = array();
		foreach ( $cards as $card ) {
			$results[] = array(
				'id'        => $card['id'],
				'name'      => $card['name'],
				'price'     => $card['price'],
				'permalink' => $card['permalink'],
				'in_stock'  => ! empty( $card['in_stock'] ),
			);
		}

		return array(
			'results'         => $results,
			'cards'           => $cards,
			'choices'         => null,
			'recommended_id'  => ! empty( $cards[0]['id'] ) ? (int) $cards[0]['id'] : 0,
			'hint'            => 'Product cards will appear in chat. Talk about these exact names only.',
		);
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	private function get_product( array $args ) {
		$id = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$index = new Product_Index();
		$data  = $index->get( $id );
		if ( ! $data ) {
			return array( 'error' => 'Product not found' );
		}
		$card = ( new Product_Knowledge() )->card( $id );
		return array(
			'product' => $data,
			'cards'   => $card ? array( $card ) : array(),
		);
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	private function get_variations( array $args ) {
		$id = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$data = ( new Product_Index() )->get( $id );
		return array(
			'variations' => $data && isset( $data['variations'] ) ? $data['variations'] : array(),
		);
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	private function get_custom_options( array $args ) {
		$id = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$product = wc_get_product( $id );
		if ( ! $product ) {
			return array( 'error' => 'Product not found' );
		}
		$options = ( new Custom_Options() )->for_product( $product );
		$groups  = array();
		foreach ( $options as $opt ) {
			$group = self::choices_from_option( $opt );
			if ( $group ) {
				$groups[ $opt['key'] ] = $group;
			}
		}
		return array(
			'product_id'    => $id,
			'options'       => $options,
			'choice_groups' => $groups,
			'hint'          => 'Do not show UI yet. Call present_choices for the next required option only (one at a time), starting with body_fit then size then collar then cuff.',
		);
	}

	/**
	 * Present tappable choices in the chat UI.
	 *
	 * @param array $args Args.
	 * @return array
	 */
	private function present_choices( array $args ) {
		$title = isset( $args['title'] ) ? sanitize_text_field( $args['title'] ) : 'Choose an option';
		$field = isset( $args['field'] ) ? sanitize_text_field( $args['field'] ) : 'choice';
		$product_id = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$option_key = isset( $args['option_key'] ) ? sanitize_text_field( $args['option_key'] ) : '';

		$choices_ui = null;

		if ( $option_key && $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$options = ( new Custom_Options() )->for_product( $product );
				foreach ( $options as $opt ) {
					if ( $opt['key'] === $option_key ) {
						$choices_ui = self::choices_from_option( $opt );
						break;
					}
				}
			}
		}

		if ( ! $choices_ui && ! empty( $args['choices_csv'] ) ) {
			$items = array();
			$parts = explode( ',', (string) $args['choices_csv'] );
			foreach ( $parts as $part ) {
				$part = trim( $part );
				if ( '' === $part ) {
					continue;
				}
				$bits  = array_map( 'trim', explode( '|', $part ) );
				$label = $bits[0];
				$image = isset( $bits[1] ) ? esc_url_raw( $bits[1] ) : '';
				$items[] = array(
					'label' => $label,
					'value' => $label,
					'image' => $image,
					'sub'   => '',
				);
			}
			$has_images = false;
			foreach ( $items as $item ) {
				if ( ! empty( $item['image'] ) ) {
					$has_images = true;
					break;
				}
			}
			$choices_ui = array(
				'title'   => $title,
				'field'   => $field,
				'type'    => $has_images ? 'image_buttons' : 'buttons',
				'choices' => $items,
			);
		}

		if ( ! $choices_ui && 'quantity' === $field ) {
			$choices_ui = array(
				'title'   => $title ? $title : 'Quantity',
				'field'   => 'quantity',
				'type'    => 'buttons',
				'choices' => array(
					array( 'label' => '1', 'value' => '1', 'image' => '', 'sub' => '' ),
					array( 'label' => '2', 'value' => '2', 'image' => '', 'sub' => '' ),
					array( 'label' => '3', 'value' => '3', 'image' => '', 'sub' => '' ),
					array( 'label' => '5', 'value' => '5', 'image' => '', 'sub' => '' ),
				),
			);
		}

		if ( ! $choices_ui ) {
			return array(
				'ok'      => false,
				'error'   => 'No choices available. Provide option_key+product_id or choices_csv.',
				'choices' => null,
			);
		}

		$choices_ui['title'] = $title ? $title : $choices_ui['title'];
		$choices_ui['field'] = $field ? $field : $choices_ui['field'];
		if ( $product_id ) {
			$choices_ui['product_id'] = $product_id;
		}

		return array(
			'ok'      => true,
			'message' => 'Choices are now shown as buttons in the chat UI. Wait for the customer to tap one.',
			'choices' => $choices_ui,
		);
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	private function validate_selection( array $args ) {
		$id = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$selection = $this->selection_from_args( $args );
		$product = wc_get_product( $id );
		if ( ! $product ) {
			return array( 'error' => 'Product not found' );
		}
		$result = ( new Custom_Options() )->validate( $product, $selection );

		// If missing, auto-attach choices for the first missing field.
		if ( ! empty( $result['missing'] ) ) {
			$options = ( new Custom_Options() )->for_product( $product );
			$first_missing = $result['missing'][0];
			foreach ( $options as $opt ) {
				if ( $opt['key'] === $first_missing ) {
					$group = self::choices_from_option( $opt );
					if ( $group ) {
						$result['choices'] = $group;
					}
					break;
				}
			}
			if ( 'quantity' === $first_missing ) {
				$result['choices'] = array(
					'title'   => 'Quantity',
					'field'   => 'quantity',
					'type'    => 'buttons',
					'choices' => array(
						array( 'label' => '1', 'value' => '1', 'image' => '', 'sub' => '' ),
						array( 'label' => '2', 'value' => '2', 'image' => '', 'sub' => '' ),
						array( 'label' => '3', 'value' => '3', 'image' => '', 'sub' => '' ),
					),
				);
			}
		}

		return $result;
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	private function add_to_cart( array $args ) {
		$id = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$selection = $this->selection_from_args( $args );
		$result = ( new Cart_Service() )->add_to_cart( $id, $selection );
		if ( ! empty( $result['success'] ) ) {
			$card = ( new Product_Knowledge() )->card( $id );
			$result['cards'] = $card ? array( $card ) : array();
			$result['cart_updated'] = true;
		} elseif ( ! empty( $result['missing'] ) ) {
			$product = wc_get_product( $id );
			if ( $product ) {
				$options = ( new Custom_Options() )->for_product( $product );
				foreach ( $options as $opt ) {
					if ( $opt['key'] === $result['missing'][0] ) {
						$group = self::choices_from_option( $opt );
						if ( $group ) {
							$result['choices'] = $group;
						}
						break;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Helper to build a function tool schema.
	 *
	 * @param string $name        Name.
	 * @param string $description Description.
	 * @param array  $properties  Properties.
	 * @param array  $required    Required keys.
	 * @return array
	 */
	private function fn( $name, $description, array $properties, array $required ) {
		return array(
			'type'     => 'function',
			'function' => array(
				'name'        => $name,
				'description' => $description,
				'parameters'  => array(
					'type'       => 'object',
					'properties' => empty( $properties ) ? new \stdClass() : $properties,
					'required'   => $required,
				),
			),
		);
	}
}
