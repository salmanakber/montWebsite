<?php
/**
 * WooCommerce cart operations for the AI assistant.
 *
 * Integrates with Mont theme custom_data (Passform, Størrelse, collar, cuff, measurements).
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Cart;

use Mont_AI_Assistant\Product\Custom_Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cart_Service
 */
class Cart_Service {

	/**
	 * Build cart item data from AI selection.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $selection  Selection bag.
	 * @return array{ok:bool,cart_item_data:array,quantity:int,errors:array,missing:array}
	 */
	public function build_cart_item( $product_id, array $selection ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array(
				'ok'             => false,
				'cart_item_data' => array(),
				'quantity'       => 0,
				'errors'         => array( 'Product not found' ),
				'missing'        => array(),
			);
		}

		$options  = new Custom_Options();
		$validation = $options->validate( $product, $selection );
		if ( ! $validation['valid'] ) {
			return array(
				'ok'             => false,
				'cart_item_data' => array(),
				'quantity'       => isset( $selection['quantity'] ) ? (int) $selection['quantity'] : 1,
				'errors'         => $validation['errors'],
				'missing'        => $validation['missing'],
			);
		}

		$custom_data = array();
		$map = array(
			'body_fit'    => 'Passform',
			'size'        => 'Størrelse',
			'collar_type' => 'Snipp (Collar)',
			'cuff_type'   => 'Mansjetter (Cuff)',
		);
		foreach ( $map as $key => $label ) {
			if ( ! empty( $selection[ $key ] ) ) {
				$custom_data[ $label ] = sanitize_text_field( $selection[ $key ] );
			}
		}

		// Measurements — Norwegian labels matching cart.js.
		$measure_map = array(
			'shirt_length'         => 'Skjortelengde',
			'sleeve_length_left'   => 'Ermelengde (Venstre)',
			'sleeve_length_right'  => 'Ermelengde (Høyre)',
			'chest'                => 'Bryst',
			'waist'                => 'Liv',
			'half_bottom'          => 'Halv bunnmål',
			'shoulder'             => 'Skulder',
		);
		if ( ! empty( $selection['custom_measurements'] ) && is_array( $selection['custom_measurements'] ) ) {
			foreach ( $measure_map as $key => $label ) {
				if ( isset( $selection['custom_measurements'][ $key ] ) && '' !== $selection['custom_measurements'][ $key ] ) {
					$val = sanitize_text_field( (string) $selection['custom_measurements'][ $key ] );
					$custom_data[ $label ] = preg_match( '/cm/i', $val ) ? $val : $val . ' cm';
				}
			}
		}

		if ( ! empty( $selection['added_price'] ) ) {
			$custom_data['custom_price'] = floatval( $selection['added_price'] );
		}

		$cart_item_data = array(
			'custom_data' => $custom_data,
			'unique_key'  => md5( microtime() . wp_rand() ),
		);

		$qty = isset( $selection['quantity'] ) ? max( 1, (int) $selection['quantity'] ) : 1;

		/**
		 * Filter cart item data before add.
		 *
		 * @param array $cart_item_data Data.
		 * @param int   $product_id     Product.
		 * @param array $selection      Selection.
		 */
		$cart_item_data = apply_filters( 'mont_ai_cart_item_data', $cart_item_data, $product_id, $selection );

		return array(
			'ok'             => true,
			'cart_item_data' => $cart_item_data,
			'quantity'       => $qty,
			'errors'         => array(),
			'missing'        => array(),
			'variation_id'   => isset( $selection['variation_id'] ) ? (int) $selection['variation_id'] : 0,
			'variation'      => isset( $selection['variation'] ) && is_array( $selection['variation'] ) ? $selection['variation'] : array(),
		);
	}

	/**
	 * Add product to cart.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $selection  Selection.
	 * @return array
	 */
	public function add_to_cart( $product_id, array $selection ) {
		if ( ! WC()->cart ) {
			wc_load_cart();
		}

		$built = $this->build_cart_item( $product_id, $selection );
		if ( ! $built['ok'] ) {
			return array(
				'success' => false,
				'message' => 'Missing required options',
				'missing' => $built['missing'],
				'errors'  => $built['errors'],
			);
		}

		$variation_id = $built['variation_id'];
		$variation    = $built['variation'];

		// Resolve variation from body_fit + size attributes when possible.
		if ( ! $variation_id ) {
			$resolved = $this->resolve_variation( $product_id, $selection );
			$variation_id = $resolved['variation_id'];
			$variation    = $resolved['variation'];
		}

		$key = WC()->cart->add_to_cart(
			$product_id,
			$built['quantity'],
			$variation_id,
			$variation,
			$built['cart_item_data']
		);

		if ( ! $key ) {
			return array(
				'success' => false,
				'message' => 'Could not add to cart',
			);
		}

		// Apply custom price surcharge if present.
		if ( ! empty( $built['cart_item_data']['custom_data']['custom_price'] ) ) {
			$cart = WC()->cart->get_cart();
			if ( isset( $cart[ $key ] ) ) {
				$base = (float) $cart[ $key ]['data']->get_price();
				$extra = (float) $built['cart_item_data']['custom_data']['custom_price'];
				$cart[ $key ]['data']->set_price( $base + $extra );
			}
		}

		WC()->cart->calculate_totals();

		$product = wc_get_product( $product_id );

		return array(
			'success'     => true,
			'message'     => sprintf( 'Added “%s” to your cart.', $product ? $product->get_name() : 'product' ),
			'cart_key'    => $key,
			'cart_count'  => WC()->cart->get_cart_contents_count(),
			'cart_total'  => wp_strip_all_tags( WC()->cart->get_cart_total() ),
			'cart_url'    => wc_get_cart_url(),
			'product'     => array(
				'id'   => $product_id,
				'name' => $product ? $product->get_name() : '',
			),
		);
	}

	/**
	 * Resolve WC variation from body fit + size labels/slugs.
	 *
	 * @param int   $product_id Product.
	 * @param array $selection  Selection.
	 * @return array{variation_id:int,variation:array}
	 */
	private function resolve_variation( $product_id, array $selection ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return array( 'variation_id' => 0, 'variation' => array() );
		}

		$attrs = array();
		if ( ! empty( $selection['body_fit'] ) ) {
			$attrs['attribute_pa_body-fit'] = $this->to_slug( $selection['body_fit'], 'pa_body-fit' );
		}
		if ( ! empty( $selection['size'] ) ) {
			$attrs['attribute_pa_size'] = $this->to_slug( $selection['size'], 'pa_size' );
		}

		if ( empty( $attrs ) ) {
			return array( 'variation_id' => 0, 'variation' => array() );
		}

		$data_store = \WC_Data_Store::load( 'product' );
		$vid        = $data_store->find_matching_product_variation( $product, $attrs );

		return array(
			'variation_id' => $vid ? (int) $vid : 0,
			'variation'    => $attrs,
		);
	}

	/**
	 * Label or slug → term slug.
	 *
	 * @param string $value    Value.
	 * @param string $taxonomy Taxonomy.
	 * @return string
	 */
	private function to_slug( $value, $taxonomy ) {
		$term = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );
		if ( $term ) {
			return $term->slug;
		}
		$term = get_term_by( 'name', $value, $taxonomy );
		if ( $term ) {
			return $term->slug;
		}
		return sanitize_title( $value );
	}

	/**
	 * Cart summary for AI.
	 *
	 * @return array
	 */
	public function get_cart() {
		if ( ! WC()->cart ) {
			wc_load_cart();
		}
		$items = array();
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$p = $item['data'];
			$items[] = array(
				'key'      => $key,
				'product_id'=> $item['product_id'],
				'name'     => $p ? $p->get_name() : '',
				'quantity' => $item['quantity'],
				'price'    => $p ? $p->get_price() : '',
				'custom'   => isset( $item['custom_data'] ) ? $item['custom_data'] : array(),
			);
		}
		return array(
			'items'      => $items,
			'count'      => WC()->cart->get_cart_contents_count(),
			'total'      => wp_strip_all_tags( WC()->cart->get_cart_total() ),
			'cart_url'   => wc_get_cart_url(),
			'checkout_url'=> wc_get_checkout_url(),
		);
	}

	/**
	 * Remove cart line.
	 *
	 * @param string $cart_key Cart key.
	 * @return array
	 */
	public function remove_item( $cart_key ) {
		if ( ! WC()->cart ) {
			wc_load_cart();
		}
		$removed = WC()->cart->remove_cart_item( sanitize_text_field( $cart_key ) );
		WC()->cart->calculate_totals();
		return array(
			'success' => (bool) $removed,
			'cart'    => $this->get_cart(),
		);
	}

	/**
	 * Update quantity.
	 *
	 * @param string $cart_key Cart key.
	 * @param int    $qty      Qty.
	 * @return array
	 */
	public function update_item( $cart_key, $qty ) {
		if ( ! WC()->cart ) {
			wc_load_cart();
		}
		WC()->cart->set_quantity( sanitize_text_field( $cart_key ), max( 0, (int) $qty ), true );
		return array(
			'success' => true,
			'cart'    => $this->get_cart(),
		);
	}
}
