<?php
/**
 * Monte B2B storefront as a DC Garments extension.
 *
 * Keeps the separate `b2b` plugin for the customer-facing portal while
 * wiring orders and status into DC Product Manager.
 *
 * @package DC_Product_Manager
 */

namespace DC_Product_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class B2B_Extension
 */
class B2B_Extension {

	/**
	 * Boot.
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'maybe_notice_missing_b2b' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Whether the Monte B2B storefront plugin is active.
	 *
	 * @return bool
	 */
	public static function is_storefront_active() {
		return defined( 'B2B_PATH' ) || class_exists( 'b2b', false ) || class_exists( 'ajax', false );
	}

	/**
	 * Soft notice in WP admin if B2B storefront is missing.
	 */
	public function maybe_notice_missing_b2b() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( self::is_storefront_active() ) {
			return;
		}
		// Only show on plugins / CRM-related screens.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ( 'plugins' !== $screen->id && false === strpos( (string) $screen->id, 'Stock' ) ) ) {
			return;
		}
		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'DC Product Manager: activate the Monte B2B plugin to enable the wholesale storefront. B2B product flags and the Order Portal still work without it.', 'dc-product-manager' );
		echo '</p></div>';
	}

	/**
	 * Label DC Garments as the parent of B2B.
	 *
	 * @param array  $links Links.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( false !== strpos( $file, 'dc-product-manager.php' ) || false !== strpos( $file, 'dc-garments/' ) ) {
			$links[] = '<span>' . esc_html__( 'Includes Order Portal (B2C + B2B) and B2B product channel controls', 'dc-product-manager' ) . '</span>';
		}
		if ( false !== strpos( $file, 'b2b/index.php' ) || false !== strpos( $file, '/b2b/' ) ) {
			$links[] = '<span>' . esc_html__( 'Extension of DC Product Manager — orders sync to CRM Order Portal', 'dc-product-manager' ) . '</span>';
		}
		return $links;
	}
}
