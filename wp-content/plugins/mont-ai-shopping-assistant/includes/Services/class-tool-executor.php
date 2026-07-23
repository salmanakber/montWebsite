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
 * Defines OpenAI-compatible tool schemas and executes them safely.
 */
class Tool_Executor {

	/**
	 * Tool definitions for the model.
	 *
	 * @return array
	 */
	public function definitions() {
		return array(
			$this->fn(
				'search_products',
				'Search the catalog by keywords, color, purpose, budget cues, SKU, tags, or attributes.',
				array(
					'query' => array( 'type' => 'string', 'description' => 'Search query' ),
					'limit' => array( 'type' => 'integer', 'description' => 'Max results (1-12)' ),
				),
				array( 'query' )
			),
			$this->fn(
				'get_product',
				'Get full product details including attributes, variations, prices, stock, and custom options.',
				array(
					'product_id' => array( 'type' => 'integer', 'description' => 'WooCommerce product ID' ),
				),
				array( 'product_id' )
			),
			$this->fn(
				'get_variations',
				'List variations for a variable product.',
				array(
					'product_id' => array( 'type' => 'integer' ),
				),
				array( 'product_id' )
			),
			$this->fn(
				'get_custom_options',
				'Get required/optional custom options (body fit, size, collar, cuff, measurements) for a product.',
				array(
					'product_id' => array( 'type' => 'integer' ),
				),
				array( 'product_id' )
			),
			$this->fn(
				'validate_selection',
				'Validate that all required options are present before add to cart.',
				array(
					'product_id' => array( 'type' => 'integer' ),
					'selection'  => array(
						'type'        => 'object',
						'description' => 'Keys: body_fit, size, collar_type, cuff_type, quantity, custom_measurements, added_price',
					),
				),
				array( 'product_id', 'selection' )
			),
			$this->fn(
				'add_to_cart',
				'Add a fully configured product to the WooCommerce cart. Only call after validate_selection succeeds.',
				array(
					'product_id' => array( 'type' => 'integer' ),
					'selection'  => array( 'type' => 'object' ),
				),
				array( 'product_id', 'selection' )
			),
			$this->fn(
				'get_cart',
				'View current cart contents and totals.',
				array(),
				array()
			),
			$this->fn(
				'update_cart_item',
				'Update quantity of a cart line (qty 0 removes).',
				array(
					'cart_key' => array( 'type' => 'string' ),
					'quantity' => array( 'type' => 'integer' ),
				),
				array( 'cart_key', 'quantity' )
			),
			$this->fn(
				'remove_cart_item',
				'Remove a line from the cart.',
				array(
					'cart_key' => array( 'type' => 'string' ),
				),
				array( 'cart_key' )
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
		switch ( $name ) {
			case 'search_products':
				return $this->search_products( $args );
			case 'get_product':
				return $this->get_product( $args );
			case 'get_variations':
				return $this->get_variations( $args );
			case 'get_custom_options':
				return $this->get_custom_options( $args );
			case 'validate_selection':
				return $this->validate_selection( $args );
			case 'add_to_cart':
				return $this->add_to_cart( $args );
			case 'get_cart':
				return ( new Cart_Service() )->get_cart();
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
	 * @param array $args Args.
	 * @return array
	 */
	private function search_products( array $args ) {
		$query = isset( $args['query'] ) ? sanitize_text_field( $args['query'] ) : '';
		$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 6;
		$index = new Product_Index();
		$hits  = $index->search( $query, $limit );
		$knowledge = new Product_Knowledge();
		$cards = array();
		foreach ( $hits as $hit ) {
			$card = $knowledge->card( $hit['id'] );
			if ( $card ) {
				$cards[] = $card;
			}
		}
		return array(
			'results' => array_map(
				function ( $h ) {
					return array(
						'id'          => $h['id'],
						'name'        => $h['name'],
						'price'       => $h['price_html'],
						'sku'         => $h['sku'],
						'categories'  => $h['categories'],
						'in_stock'    => $h['in_stock'],
						'permalink'   => $h['permalink'],
						'short'       => mb_substr( $h['short_description'], 0, 160 ),
					);
				},
				$hits
			),
			'cards'   => $cards,
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
		return array(
			'options' => ( new Custom_Options() )->for_product( $product ),
		);
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	private function validate_selection( array $args ) {
		$id = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$selection = isset( $args['selection'] ) && is_array( $args['selection'] ) ? $args['selection'] : array();
		$product = wc_get_product( $id );
		if ( ! $product ) {
			return array( 'error' => 'Product not found' );
		}
		return ( new Custom_Options() )->validate( $product, $selection );
	}

	/**
	 * @param array $args Args.
	 * @return array
	 */
	private function add_to_cart( array $args ) {
		$id = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;
		$selection = isset( $args['selection'] ) && is_array( $args['selection'] ) ? $args['selection'] : array();
		$result = ( new Cart_Service() )->add_to_cart( $id, $selection );
		if ( ! empty( $result['success'] ) ) {
			$card = ( new Product_Knowledge() )->card( $id );
			$result['cards'] = $card ? array( $card ) : array();
			$result['cart_updated'] = true;
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
