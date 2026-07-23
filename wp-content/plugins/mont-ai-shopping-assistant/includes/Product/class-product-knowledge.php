<?php
/**
 * Product knowledge extractor — full WooCommerce + Mont custom options.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product_Knowledge
 *
 * Builds a rich structured payload the AI can reason over.
 */
class Product_Knowledge {

	/**
	 * Build full knowledge payload for one product.
	 *
	 * @param int|\WC_Product $product Product.
	 * @return array|null
	 */
	public function build( $product ) {
		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
		if ( ! $product ) {
			return null;
		}

		$id = $product->get_id();
		$custom = new Custom_Options();

		$data = array(
			'id'               => $id,
			'type'             => $product->get_type(),
			'name'             => $product->get_name(),
			'slug'             => $product->get_slug(),
			'sku'              => $product->get_sku(),
			'permalink'        => get_permalink( $id ),
			'status'           => $product->get_status(),
			'short_description'=> wp_strip_all_tags( $product->get_short_description() ),
			'description'      => wp_strip_all_tags( $product->get_description() ),
			'price'            => $product->get_price(),
			'regular_price'    => $product->get_regular_price(),
			'sale_price'       => $product->get_sale_price(),
			'price_html'       => wp_strip_all_tags( $product->get_price_html() ),
			'currency'         => get_woocommerce_currency(),
			'stock_status'     => $product->get_stock_status(),
			'stock_quantity'   => $product->get_stock_quantity(),
			'in_stock'         => $product->is_in_stock(),
			'on_sale'          => $product->is_on_sale(),
			'image'            => wp_get_attachment_url( $product->get_image_id() ),
			'gallery'          => array_map( 'wp_get_attachment_url', $product->get_gallery_image_ids() ),
			'categories'       => wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'names' ) ),
			'tags'             => wp_get_post_terms( $id, 'product_tag', array( 'fields' => 'names' ) ),
			'attributes'       => $this->attributes( $product ),
			'variations'       => $this->variations( $product ),
			'custom_options'   => $custom->for_product( $product ),
			'meta'             => $this->relevant_meta( $id ),
			'acf'              => $this->acf_fields( $id ),
		);

		return $data;
	}

	/**
	 * Compact card for chat UI.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	public function card( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return null;
		}
		return array(
			'id'        => $product_id,
			'name'      => $product->get_name(),
			'price'     => wp_strip_all_tags( $product->get_price_html() ),
			'image'     => wp_get_attachment_url( $product->get_image_id() ),
			'permalink' => get_permalink( $product_id ),
			'in_stock'  => $product->is_in_stock(),
		);
	}

	/**
	 * Attributes list.
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	private function attributes( $product ) {
		$out = array();
		foreach ( $product->get_attributes() as $attr ) {
			if ( is_a( $attr, 'WC_Product_Attribute' ) ) {
				$out[] = array(
					'name'     => wc_attribute_label( $attr->get_name() ),
					'slug'     => $attr->get_name(),
					'options'  => $attr->get_options(),
					'visible'  => $attr->get_visible(),
					'variation'=> $attr->get_variation(),
				);
			}
		}
		return $out;
	}

	/**
	 * Variations summary.
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	private function variations( $product ) {
		if ( ! $product->is_type( 'variable' ) ) {
			return array();
		}
		$out = array();
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( ! $v ) {
				continue;
			}
			$out[] = array(
				'id'           => $vid,
				'sku'          => $v->get_sku(),
				'price'        => $v->get_price(),
				'regular_price'=> $v->get_regular_price(),
				'sale_price'   => $v->get_sale_price(),
				'stock_status' => $v->get_stock_status(),
				'in_stock'     => $v->is_in_stock(),
				'attributes'   => $v->get_attributes(),
			);
		}
		return $out;
	}

	/**
	 * Site-relevant product meta.
	 *
	 * @param int $id Product ID.
	 * @return array
	 */
	private function relevant_meta( $id ) {
		$keys = array(
			'_fabric_color',
			'_fabric_color_english',
			'_fabric_no',
			'_moq',
			'_b2b_product',
			'_quality',
			'_fabric_width',
			'_weight',
			'_dc_multicurrency_prices',
			'_product_video',
			'_custom_title',
		);
		$out = array();
		foreach ( $keys as $key ) {
			$val = get_post_meta( $id, $key, true );
			if ( '' !== $val && null !== $val ) {
				$out[ $key ] = maybe_unserialize( $val );
			}
		}
		return $out;
	}

	/**
	 * ACF fields if available.
	 *
	 * @param int $id Product ID.
	 * @return array
	 */
	private function acf_fields( $id ) {
		if ( ! function_exists( 'get_fields' ) ) {
			return array();
		}
		$fields = get_fields( $id );
		return is_array( $fields ) ? $fields : array();
	}
}
