<?php
/**
 * Cached product index for fast AI search.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Product;

use Mont_AI_Assistant\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product_Index
 *
 * Stores searchable blobs + JSON payloads in wp_mont_ai_product_index.
 */
class Product_Index {

	/**
	 * Register save hooks.
	 */
	public function register() {
		add_action( 'save_post_product', array( $this, 'index_product' ), 20, 1 );
		add_action( 'woocommerce_update_product', array( $this, 'index_product' ), 20, 1 );
		add_action( 'before_delete_post', array( $this, 'delete_product' ), 10, 1 );
		add_action( 'wp_ajax_mont_ai_rebuild_index', array( $this, 'ajax_rebuild' ) );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'mont_ai_product_index';
	}

	/**
	 * Index one product.
	 *
	 * @param int $product_id Product ID.
	 */
	public function index_product( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || 'publish' !== $product->get_status() ) {
			$this->delete_product( $product_id );
			return;
		}

		$settings = Plugin::settings();
		$allowed  = isset( $settings['allowed_categories'] ) ? (array) $settings['allowed_categories'] : array();
		if ( ! empty( $allowed ) ) {
			$cats = $product->get_category_ids();
			if ( ! array_intersect( array_map( 'intval', $allowed ), array_map( 'intval', $cats ) ) ) {
				$this->delete_product( $product_id );
				return;
			}
		}

		$knowledge = new Product_Knowledge();
		$payload   = $knowledge->build( $product );
		if ( ! $payload ) {
			return;
		}

		$search_parts = array(
			$payload['name'],
			$payload['sku'],
			$payload['short_description'],
			$payload['description'],
			implode( ' ', (array) $payload['categories'] ),
			implode( ' ', (array) $payload['tags'] ),
		);
		if ( ! empty( $payload['meta']['_fabric_color'] ) ) {
			$search_parts[] = $payload['meta']['_fabric_color'];
		}
		if ( ! empty( $payload['meta']['_fabric_color_english'] ) ) {
			$search_parts[] = $payload['meta']['_fabric_color_english'];
		}
		foreach ( $payload['attributes'] as $attr ) {
			$search_parts[] = $attr['name'];
			if ( is_array( $attr['options'] ) ) {
				$search_parts[] = implode( ' ', $attr['options'] );
			}
		}

		$search_blob = wp_strip_all_tags( implode( ' ', $search_parts ) );

		global $wpdb;
		$wpdb->replace(
			self::table(),
			array(
				'product_id'  => $product_id,
				'sku'         => (string) $payload['sku'],
				'title'       => $payload['name'],
				'search_blob' => $search_blob,
				'payload'     => wp_json_encode( $payload ),
				'updated_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Remove from index.
	 *
	 * @param int $post_id Post ID.
	 */
	public function delete_product( $post_id ) {
		if ( get_post_type( $post_id ) !== 'product' && ! wc_get_product( $post_id ) ) {
			return;
		}
		global $wpdb;
		$wpdb->delete( self::table(), array( 'product_id' => $post_id ), array( '%d' ) );
	}

	/**
	 * Rebuild entire index.
	 */
	public static function rebuild_all() {
		$ids = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => -1,
				'return' => 'ids',
			)
		);
		$indexer = new self();
		foreach ( $ids as $id ) {
			$indexer->index_product( $id );
		}
		Plugin::log( 'Product index rebuilt', array( 'count' => count( $ids ) ) );
		return count( $ids );
	}

	/**
	 * Admin AJAX rebuild.
	 */
	public function ajax_rebuild() {
		check_ajax_referer( 'mont_ai_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		$count = self::rebuild_all();
		wp_send_json_success( array( 'count' => $count ) );
	}

	/**
	 * Search the index.
	 *
	 * @param string $query Query.
	 * @param int    $limit Limit.
	 * @return array List of payloads.
	 */
	public function search( $query, $limit = 8 ) {
		global $wpdb;
		$table = self::table();
		$q     = trim( (string) $query );
		$limit = max( 1, min( 20, (int) $limit ) );

		if ( '' === $q ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT payload FROM {$table} ORDER BY updated_at DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);
		} else {
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			// Portable LIKE search (FULLTEXT can fail on some hosts / engine configs).
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT payload FROM {$table}
					 WHERE title LIKE %s OR sku LIKE %s OR search_blob LIKE %s
					 ORDER BY updated_at DESC LIMIT %d",
					$like,
					$like,
					$like,
					$limit
				),
				ARRAY_A
			);
		}

		$out = array();
		foreach ( (array) $rows as $row ) {
			$decoded = json_decode( $row['payload'], true );
			if ( is_array( $decoded ) ) {
				$out[] = $decoded;
			}
		}
		return $out;
	}

	/**
	 * Get cached payload by product id.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	public function get( $product_id ) {
		global $wpdb;
		$row = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT payload FROM ' . self::table() . ' WHERE product_id = %d',
				$product_id
			)
		);
		if ( ! $row ) {
			$this->index_product( $product_id );
			$row = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT payload FROM ' . self::table() . ' WHERE product_id = %d',
					$product_id
				)
			);
		}
		$decoded = json_decode( (string) $row, true );
		return is_array( $decoded ) ? $decoded : null;
	}
}
