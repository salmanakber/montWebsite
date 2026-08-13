<?php
/**
 * WooCommerce order lookup + support / complaint intake for the chat assistant.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Services;

use Mont_AI_Assistant\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Service
 */
class Order_Service {

	/**
	 * Look up an order by number + billing email (privacy gate).
	 *
	 * @param string $order_number Order number or id.
	 * @param string $email        Billing email.
	 * @return array
	 */
	public function lookup( $order_number, $email = '' ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return array( 'error' => 'WooCommerce is not available.' );
		}

		$order_number = trim( (string) $order_number );
		$email        = sanitize_email( (string) $email );
		$order        = null;

		if ( is_numeric( $order_number ) ) {
			$order = wc_get_order( (int) $order_number );
		}
		if ( ! $order ) {
			$order = wc_get_order( $order_number );
		}
		if ( ! $order && function_exists( 'wc_get_orders' ) ) {
			$matches = wc_get_orders(
				array(
					'limit'      => 1,
					'orderby'    => 'date',
					'order'      => 'DESC',
					'meta_key'   => '_order_number_formatted',
					'meta_value' => $order_number,
				)
			);
			if ( ! empty( $matches[0] ) ) {
				$order = $matches[0];
			}
		}

		if ( ! $order ) {
			return array(
				'found'   => false,
				'message' => 'No order found with that number. Double-check the order # from your confirmation email.',
			);
		}

		$billing_email = strtolower( (string) $order->get_billing_email() );
		if ( $email && $billing_email && strtolower( $email ) !== $billing_email ) {
			return array(
				'found'   => false,
				'message' => 'That email does not match this order. Please use the billing email from checkout.',
			);
		}

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = array(
				'name'     => $item->get_name(),
				'qty'      => $item->get_quantity(),
				'total'    => wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ),
				'meta'     => $this->format_item_meta( $item ),
			);
		}

		return array(
			'found'          => true,
			'order_id'       => $order->get_id(),
			'order_number'   => $order->get_order_number(),
			'status'         => wc_get_order_status_name( $order->get_status() ),
			'status_slug'    => $order->get_status(),
			'date'           => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '',
			'total'          => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'currency'       => $order->get_currency(),
			'billing_name'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'shipping_city'  => $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city(),
			'shipping_country' => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country(),
			'items'          => $items,
			'tracking'       => $this->tracking_hint( $order ),
		);
	}

	/**
	 * Submit a support / complaint message (stored + emailed to shop).
	 *
	 * @param string $email   Customer email.
	 * @param string $message Complaint / issue text.
	 * @param string $name    Customer name.
	 * @param string $order_id Optional order reference.
	 * @return array
	 */
	public function submit_complaint( $email, $message, $name = '', $order_id = '' ) {
		$email   = sanitize_email( (string) $email );
		$message = sanitize_textarea_field( (string) $message );
		$name    = sanitize_text_field( (string) $name );
		$order_id = sanitize_text_field( (string) $order_id );

		if ( ! $email || ! is_email( $email ) ) {
			return array( 'success' => false, 'error' => 'A valid email is required so we can follow up.' );
		}
		if ( strlen( trim( $message ) ) < 10 ) {
			return array( 'success' => false, 'error' => 'Please describe the issue in a few words so the team can help.' );
		}

		$ticket = array(
			'time'     => gmdate( 'c' ),
			'email'    => $email,
			'name'     => $name,
			'order_id' => $order_id,
			'message'  => $message,
			'ip'       => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		);

		$log = get_option( 'mont_ai_support_tickets', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		array_unshift( $log, $ticket );
		$log = array_slice( $log, 0, 200 );
		update_option( 'mont_ai_support_tickets', $log, false );

		$to = apply_filters( 'mont_ai_support_email', get_option( 'admin_email' ) );
		$subject = sprintf(
			'[Mont Chat] Support from %s%s',
			$email,
			$order_id ? ' (order ' . $order_id . ')' : ''
		);
		$body = "Customer: {$name}\nEmail: {$email}\n";
		if ( $order_id ) {
			$body .= "Order: {$order_id}\n";
		}
		$body .= "\nMessage:\n{$message}\n";

		wp_mail( $to, $subject, $body );

		Plugin::log( 'Support ticket', $ticket );

		return array(
			'success' => true,
			'message' => 'Thanks — your message is with our team. We will reply to ' . $email . ' as soon as we can (usually within 1–2 business days).',
		);
	}

	/**
	 * Format line item meta for display.
	 *
	 * @param \WC_Order_Item $item Item.
	 * @return string
	 */
	private function format_item_meta( $item ) {
		$bits = array();
		foreach ( $item->get_formatted_meta_data( '', true ) as $meta ) {
			$bits[] = wp_strip_all_tags( $meta->display_key . ': ' . $meta->display_value );
		}
		return implode( '; ', array_slice( $bits, 0, 8 ) );
	}

	/**
	 * Best-effort tracking / shipment note.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	private function tracking_hint( $order ) {
		$keys = array( '_tracking_number', 'tracking_number', '_wc_shipment_tracking_items' );
		foreach ( $keys as $key ) {
			$val = $order->get_meta( $key );
			if ( $val ) {
				return is_string( $val ) ? $val : wp_json_encode( $val );
			}
		}
		if ( in_array( $order->get_status(), array( 'completed', 'processing' ), true ) ) {
			return 'Order is being processed — tracking may appear in your confirmation email when shipped.';
		}
		return '';
	}
}
