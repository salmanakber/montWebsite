<?php
/**
 * Order portal — B2C (WooCommerce) + B2B (wholesale) with print sheets.
 *
 * @package DC_Product_Manager
 */

namespace DC_Product_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Order_Portal
 */
class Order_Portal {

	const CPT = 'dc_b2b_order';

	/**
	 * Boot hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'dc_b2b_order_placed', array( $this, 'store_b2b_order' ), 10, 3 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'tag_b2c_order' ), 20, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'tag_b2c_order' ), 20, 1 );
	}

	/**
	 * Register B2B order storage CPT (hidden from public menus).
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'          => __( 'B2B Orders', 'dc-product-manager' ),
					'singular_name' => __( 'B2B Order', 'dc-product-manager' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Persist a B2B wholesale order when Monte B2B places one.
	 *
	 * @param array  $customer Customer fields.
	 * @param array  $items    Cart line items from session.
	 * @param string $api_raw  Raw API response (optional).
	 * @return int Post ID.
	 */
	public function store_b2b_order( $customer, $items, $api_raw = '' ) {
		$customer = is_array( $customer ) ? $customer : array();
		$items    = is_array( $items ) ? $items : array();

		$company = isset( $customer['companyname'] ) ? sanitize_text_field( $customer['companyname'] ) : '';
		$email   = isset( $customer['email'] ) ? sanitize_email( $customer['email'] ) : '';
		$title   = sprintf(
			/* translators: 1: company or email, 2: datetime */
			__( 'B2B · %1$s · %2$s', 'dc-product-manager' ),
			$company ? $company : ( $email ? $email : __( 'Wholesale', 'dc-product-manager' ) ),
			current_time( 'Y-m-d H:i' )
		);

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		$total_shirts = 0;
		foreach ( $items as $item ) {
			if ( isset( $item['price'] ) ) {
				$total_shirts += (int) $item['price'];
			}
		}

		update_post_meta( $post_id, '_dc_channel', 'b2b' );
		update_post_meta( $post_id, '_dc_customer', $customer );
		update_post_meta( $post_id, '_dc_items', $items );
		update_post_meta( $post_id, '_dc_total_shirts', $total_shirts );
		update_post_meta( $post_id, '_dc_api_response', is_string( $api_raw ) ? $api_raw : wp_json_encode( $api_raw ) );
		update_post_meta( $post_id, '_dc_production_status', 'new' );

		return (int) $post_id;
	}

	/**
	 * Tag WooCommerce orders as B2C channel for the portal.
	 *
	 * @param mixed $order Order ID or WC_Order.
	 */
	public function tag_b2c_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}
		if ( ! $order->get_meta( '_dc_channel' ) ) {
			$order->update_meta_data( '_dc_channel', 'b2c' );
			$order->save();
		}
	}

	/**
	 * Whether current user may view the portal.
	 *
	 * @return bool
	 */
	public static function can_access() {
		return current_user_can( 'manage_woocommerce' )
			|| current_user_can( 'manage_options' )
			|| current_user_can( 'dc_access_crm' );
	}

	/**
	 * Collect unified orders for listing.
	 *
	 * @param string $channel all|b2c|b2b.
	 * @param int    $limit   Max rows.
	 * @return array
	 */
	public function get_orders( $channel = 'all', $limit = 100 ) {
		$channel = in_array( $channel, array( 'all', 'b2c', 'b2b' ), true ) ? $channel : 'all';
		$rows    = array();

		if ( 'b2b' !== $channel && function_exists( 'wc_get_orders' ) ) {
			$wc_orders = wc_get_orders(
				array(
					'limit'   => $limit,
					'orderby' => 'date',
					'order'   => 'DESC',
					'status'  => array_keys( wc_get_order_statuses() ),
				)
			);
			foreach ( $wc_orders as $order ) {
				$rows[] = $this->normalize_wc_order( $order );
			}
		}

		if ( 'b2c' !== $channel ) {
			$q = new \WP_Query(
				array(
					'post_type'      => self::CPT,
					'post_status'    => 'publish',
					'posts_per_page' => $limit,
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);
			foreach ( $q->posts as $post ) {
				$rows[] = $this->normalize_b2b_order( $post );
			}
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return strcmp( $b['date_gmt'], $a['date_gmt'] );
			}
		);

		return array_slice( $rows, 0, $limit );
	}

	/**
	 * @param \WC_Order $order Order.
	 * @return array
	 */
	private function normalize_wc_order( $order ) {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$meta_lines = array();
			foreach ( $item->get_formatted_meta_data( '' ) as $meta ) {
				$meta_lines[] = array(
					'label' => wp_strip_all_tags( $meta->display_key ),
					'value' => wp_strip_all_tags( $meta->display_value ),
				);
			}
			$product = $item->get_product();
			$items[] = array(
				'name' => $item->get_name(),
				'qty'  => $item->get_quantity(),
				'meta' => $meta_lines,
				'sku'  => ( $product && is_object( $product ) ) ? $product->get_sku() : '',
			);
		}

		return array(
			'id'           => $order->get_id(),
			'number'       => $order->get_order_number(),
			'channel'      => 'b2c',
			'status'       => $order->get_status(),
			'date'         => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'Y-m-d H:i' ) : '',
			'date_gmt'     => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
			'customer'     => trim( $order->get_formatted_billing_full_name() ),
			'email'        => $order->get_billing_email(),
			'company'      => $order->get_billing_company(),
			'total'        => $order->get_formatted_order_total(),
			'items'        => $items,
			'print_url'    => add_query_arg(
				array(
					'tab'      => 'orders',
					'print'    => 1,
					'channel'  => 'b2c',
					'order_id' => $order->get_id(),
				),
				home_url( '/crm/' )
			),
			'view_url'     => $order->get_edit_order_url(),
		);
	}

	/**
	 * @param \WP_Post $post Post.
	 * @return array
	 */
	private function normalize_b2b_order( $post ) {
		$customer = get_post_meta( $post->ID, '_dc_customer', true );
		$items    = get_post_meta( $post->ID, '_dc_items', true );
		$customer = is_array( $customer ) ? $customer : array();
		$items    = is_array( $items ) ? $items : array();

		$normalized_items = array();
		foreach ( $items as $item ) {
			$size_bits = array();
			if ( ! empty( $item['size'] ) && is_array( $item['size'] ) ) {
				foreach ( $item['size'] as $size ) {
					if ( empty( $size['value'] ) ) {
						continue;
					}
					$size_bits[] = ( isset( $size['dataValue'] ) ? $size['dataValue'] : '' ) . ' × ' . $size['value'];
				}
			}
			$fabric = isset( $item['fabircDetails'][0] ) ? $item['fabircDetails'][0] : array();
			$name   = isset( $fabric['fabricName'] ) ? $fabric['fabricName'] : __( 'Fabric', 'dc-product-manager' );
			$meta   = array();
			if ( $size_bits ) {
				$meta[] = array( 'label' => __( 'Size breakdown', 'dc-product-manager' ), 'value' => implode( ', ', $size_bits ) );
			}
			if ( ! empty( $item['checkedForms'] ) ) {
				$meta[] = array( 'label' => __( 'Fit', 'dc-product-manager' ), 'value' => implode( ', ', (array) $item['checkedForms'] ) );
			}
			if ( ! empty( $item['collarType'] ) ) {
				$meta[] = array( 'label' => __( 'Collar', 'dc-product-manager' ), 'value' => $item['collarType'] );
			}
			if ( ! empty( $item['cuffType'] ) ) {
				$meta[] = array( 'label' => __( 'Cuff', 'dc-product-manager' ), 'value' => $item['cuffType'] );
			}
			if ( ! empty( $item['comments'] ) ) {
				$meta[] = array( 'label' => __( 'Comments', 'dc-product-manager' ), 'value' => $item['comments'] );
			}
			if ( ! empty( $fabric['fabircColor'] ) ) {
				$meta[] = array( 'label' => __( 'Color', 'dc-product-manager' ), 'value' => $fabric['fabircColor'] );
			}
			if ( ! empty( $fabric['fabricQuality'] ) ) {
				$meta[] = array( 'label' => __( 'Quality', 'dc-product-manager' ), 'value' => $fabric['fabricQuality'] );
			}

			$normalized_items[] = array(
				'name' => $name,
				'qty'  => isset( $item['price'] ) ? (int) $item['price'] : 0,
				'meta' => $meta,
				'sku'  => '',
			);
		}

		$company = isset( $customer['companyname'] ) ? $customer['companyname'] : '';
		$person  = isset( $customer['contactperson'] ) ? $customer['contactperson'] : '';
		$email   = isset( $customer['email'] ) ? $customer['email'] : '';

		return array(
			'id'        => $post->ID,
			'number'    => 'B2B-' . $post->ID,
			'channel'   => 'b2b',
			'status'    => get_post_meta( $post->ID, '_dc_production_status', true ) ?: 'new',
			'date'      => get_the_date( 'Y-m-d H:i', $post ),
			'date_gmt'  => get_post_time( 'c', true, $post ),
			'customer'  => $person ? $person : $company,
			'email'     => $email,
			'company'   => $company,
			'total'     => (int) get_post_meta( $post->ID, '_dc_total_shirts', true ) . ' ' . __( 'shirts', 'dc-product-manager' ),
			'items'     => $normalized_items,
			'customer_raw' => $customer,
			'print_url' => add_query_arg(
				array(
					'tab'      => 'orders',
					'print'    => 1,
					'channel'  => 'b2b',
					'order_id' => $post->ID,
				),
				home_url( '/crm/' )
			),
			'view_url'  => '',
		);
	}

	/**
	 * Fetch one order for print.
	 *
	 * @param string $channel b2c|b2b.
	 * @param int    $order_id ID.
	 * @return array|null
	 */
	public function get_order_for_print( $channel, $order_id ) {
		$order_id = absint( $order_id );
		if ( ! $order_id ) {
			return null;
		}
		if ( 'b2c' === $channel && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			return $order ? $this->normalize_wc_order( $order ) : null;
		}
		if ( 'b2b' === $channel ) {
			$post = get_post( $order_id );
			if ( ! $post || self::CPT !== $post->post_type ) {
				return null;
			}
			return $this->normalize_b2b_order( $post );
		}
		return null;
	}
}
