<?php
/*
Plugin Name:       Peki – Fiken Integration for WooCommerce
Plugin URI:        https://peki.no/integration/fiken
Description:       Connect WooCommerce with Fiken to export orders and accounting data. Not affiliated with Fiken AS or WooCommerce/Automattic.
Version:           1.0.22
Author:            Peki
Author URI:        https://peki.no
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       peki-fiken-integration-for-woocommerce
Domain Path:       /languages
Requires at least: 5.8
Tested up to:      6.9
Requires PHP:      7.4
Requires Plugins:  woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** -----------------------------------------------------------------
 * Konstanter (unikt prefix) + back-compat aliases
 * ------------------------------------------------------------------ */
if ( ! defined( 'PEKIFIKEN_VERSION' ) ) {
    define( 'PEKIFIKEN_VERSION', '1.0.22' );
}
if ( ! defined( 'PEKIFIKEN_FILE' ) ) {
	define( 'PEKIFIKEN_FILE', __FILE__ );
}
if ( ! defined( 'PEKIFIKEN_DIR' ) ) {
	define( 'PEKIFIKEN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'PEKIFIKEN_URL' ) ) {
	define( 'PEKIFIKEN_URL', plugin_dir_url( __FILE__ ) );
}

/* Back-compat for eldre kode */
if ( ! defined( 'PWTF_VERSION' ) ) { define( 'PWTF_VERSION', PEKIFIKEN_VERSION ); }
if ( ! defined( 'PWTF_FILE' ) )    { define( 'PWTF_FILE', PEKIFIKEN_FILE ); }
if ( ! defined( 'PWTF_DIR' ) )     { define( 'PWTF_DIR', PEKIFIKEN_DIR ); }
if ( ! defined( 'PWTF_URL' ) )     { define( 'PWTF_URL', PEKIFIKEN_URL ); }

if ( ! defined( 'PEKIFIKEN_STATE_THROTTLE_SEC' ) ) {
	define( 'PEKIFIKEN_STATE_THROTTLE_SEC', 60 ); // endre om du vil
}

/** -----------------------------------------------------------------
 * Inkluderingsstier (NY: bruk $pekifiken_inc_dir for includes/)
 * ------------------------------------------------------------------ */
$pekifiken_inc_dir = PEKIFIKEN_DIR . 'includes/';

/** Optional includes (settings/upgrade) */
if ( file_exists( $pekifiken_inc_dir . 'class-fiken-settings-page.php' ) ) {
	require_once $pekifiken_inc_dir . 'class-fiken-settings-page.php';
}
if ( file_exists( $pekifiken_inc_dir . 'class-fiken-upgrade-page.php' ) ) {
	require_once $pekifiken_inc_dir . 'class-fiken-upgrade-page.php';
}
if ( file_exists( $pekifiken_inc_dir . 'class-fiken-status.php' ) ) {
    require_once $pekifiken_inc_dir . 'class-fiken-status.php';
}

/** -----------------------------------------------------------------
 * Admin modules (oppdelt struktur)
 * ------------------------------------------------------------------ */
require_once $pekifiken_inc_dir . 'admin/class-admin-state.php';
require_once $pekifiken_inc_dir . 'admin/class-admin-menu.php';
require_once $pekifiken_inc_dir . 'admin/class-admin-assets.php';
require_once $pekifiken_inc_dir . 'admin/class-admin-notices.php';
require_once $pekifiken_inc_dir . 'admin/class-admin-connect.php';
require_once $pekifiken_inc_dir . 'admin/class-admin.php';

/** -----------------------------------------------------------------
 * Whitelist eksterne redirect-hosts (policy-korrekt for wp_safe_redirect)
 * ------------------------------------------------------------------ */
add_action( 'plugins_loaded', static function () {
	add_filter( 'allowed_redirect_hosts', static function ( $hosts ) {
		$hosts   = is_array( $hosts ) ? $hosts : array();
		$hosts[] = 'peki.no';
		$hosts[] = 'www.peki.no';
		$hosts[] = 'fiken.no';
		$hosts[] = 'www.fiken.no';
		// $hosts[] = 'staging.peki.no'; // ev. testmiljø
		return array_values( array_unique( $hosts ) );
	} );
}, 1 );

/** -----------------------------------------------------------------
 * Bootstrap (last alltid admin-UI; gate Woo-avhengig logikk)
 * ------------------------------------------------------------------ */
add_action(
    'plugins_loaded',
    static function () {

        // Varsle om manglende WooCommerce (men ikke stopp plugin)
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action(
                'admin_notices',
                static function () {
                    echo '<div class="notice notice-error"><p>' .
                        esc_html__( 'Peki – Fiken Integration for WooCommerce requires WooCommerce to be active for exports, but the settings page will still be available.', 'peki-fiken-integration-for-woocommerce' ) .
                        '</p></div>';
                }
            );
        }


        // ✅ Start hoved-admin (menyer, sider, assets, osv.)
        if ( class_exists( '\FikenBilag\Admin\Admin' ) ) {
            \FikenBilag\Admin\Admin::instance();
        }

        // Woo-avhengige ting (eksport etc.) kun når Woo finnes
        if ( class_exists( 'WooCommerce' ) ) {
            $export_file = PEKIFIKEN_DIR . 'includes/class-fiken-export.php';
            if ( file_exists( $export_file ) ) {
                require_once $export_file;
            }
        }
    },
    5
);

/** -----------------------------------------------------------------
 * REST endpoint for webhooks: /wp-json/fiken/v1/ping
 * ------------------------------------------------------------------ */
add_action( 'rest_api_init', static function() {
    register_rest_route( 'fiken/v1', '/ping', array(
        'methods'  => 'POST',
        'callback' => static function( \WP_REST_Request $request ) {
            $raw_body = $request->get_body();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Server variable for HMAC validation
            $sig = isset( $_SERVER['HTTP_X_PEKI_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_PEKI_SIGNATURE'] ) ) : '';
            $secret = (string) get_option( 'fiken_webhook_secret', '' );
            if ( $secret === '' ) {
                return new \WP_REST_Response( array( 'ok' => false, 'error' => 'no_secret' ), 400 );
            }
            $calc = hash_hmac( 'sha256', $raw_body, $secret );
            if ( ! hash_equals( $calc, $sig ) ) {
                return new \WP_REST_Response( array( 'ok' => false, 'error' => 'bad_signature' ), 401 );
            }
            $json = json_decode( $raw_body, true );
            if ( ! is_array( $json ) ) {
                return new \WP_REST_Response( array( 'ok' => false, 'error' => 'bad_json' ), 400 );
            }
            $event = isset( $json['event'] ) ? (string) $json['event'] : '';
            if ( in_array( $event, array( 'quota_updated', 'plan_changed' ), true ) ) {
                if ( class_exists( '\FikenBilag\Fiken_Status' ) ) {
                    $live = \FikenBilag\Fiken_Status::fetch_live();
                    if ( is_array( $live ) ) {
                        \FikenBilag\Fiken_Status::update_cache_from_array( $live, 300 );
                    }
                }
            }
            return new \WP_REST_Response( array( 'ok' => true ), 200 );
        },
        'permission_callback' => '__return_true',
    ) );
} );

/** -----------------------------------------------------------------
 * Cron: refresh status cache every 10 minutes
 * ------------------------------------------------------------------ */
add_action( 'pekifiken_cron_refresh_status', static function () {
    if ( class_exists( '\FikenBilag\Fiken_Status' ) ) {
        \FikenBilag\Fiken_Status::cron_refresh();
    }
} );

// Activation: schedule event if not scheduled
register_activation_hook( __FILE__, static function () {
    if ( ! wp_next_scheduled( 'pekifiken_cron_refresh_status' ) ) {
        wp_schedule_event( time() + 60, 'ten_minutes', 'pekifiken_cron_refresh_status' );
    }
} );

// Deactivation: clear scheduled event
register_deactivation_hook( __FILE__, static function () {
    $ts = wp_next_scheduled( 'pekifiken_cron_refresh_status' );
    if ( $ts ) {
        wp_unschedule_event( $ts, 'pekifiken_cron_refresh_status' );
    }
} );

// Add custom schedule every 10 minutes
add_filter( 'cron_schedules', static function ( $schedules ) {
    $schedules['ten_minutes'] = array(
        'interval' => 600,
        'display'  => __( 'Every 10 Minutes', 'peki-fiken-integration-for-woocommerce' ),
    );
    return $schedules;
} );

/** -----------------------------------------------------------------
 * Auto-export hooks
 * ------------------------------------------------------------------ */
add_action(
	'woocommerce_order_status_completed',
	static function ( $order_id ) {
		$order_id = absint( $order_id );
		// Guard: do not auto-export again if an invoice id already exists
		if ( $order_id ) {
			$already = (int) get_post_meta( $order_id, '_fiken_invoice_id', true );
			if ( $already > 0 ) {
				// Skip auto-export; order was already exported
				return;
			}
		}
		if ( $order_id && class_exists( '\\FikenBilag\\Fiken_Export' ) && method_exists( '\\FikenBilag\\Fiken_Export', 'eksporter_ordren' ) ) {
			try {
				\FikenBilag\Fiken_Export::eksporter_ordren( $order_id );
			} catch ( \Throwable $e ) {
				if ( function_exists( 'wc_get_logger' ) ) {
					wc_get_logger()->error( 'Peki Fiken export error (completed): ' . $e->getMessage(), array( 'source' => 'peki-fiken' ) );
				}
			}
		}
	}
);

add_action(
	'woocommerce_order_refunded',
	static function ( $order_id, $refund_id ) {
		$order_id  = absint( $order_id );
		$refund_id = absint( $refund_id );
		if ( ! $order_id || ! $refund_id ) {
			return;
		}
		if ( class_exists( '\\FikenBilag\\Fiken_Export' ) && method_exists( '\\FikenBilag\\Fiken_Export', 'eksporter_refund' ) ) {
			try {
				\FikenBilag\Fiken_Export::eksporter_refund( $order_id, $refund_id );
			} catch ( \Throwable $e ) {
				if ( function_exists( 'wc_get_logger' ) ) {
					wc_get_logger()->error( 'Peki Fiken export error (refund): ' . $e->getMessage(), array( 'source' => 'peki-fiken' ) );
				}
			}
		}
	},
	10,
	2
);

/** -----------------------------------------------------------------
 * Order action: Force export to Fiken (overrides duplicate guard)
 * ------------------------------------------------------------------ */
add_filter( 'woocommerce_order_actions', static function( $actions ) {
	// Add an explicit order action in the "Order actions" dropdown (admin order edit)
	$actions['pekifiken_force_export'] = __( 'Export to Fiken (force)', 'peki-fiken-integration-for-woocommerce' );
	return $actions;
} );

add_action( 'woocommerce_order_action_pekifiken_force_export', static function( $order ) {
	// Woo passes WC_Order object here
	try {
		if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
			$order_id = (int) $order->get_id();
		} else {
			$order_id = (int) $order;
		}
		if ( $order_id && class_exists( '\\FikenBilag\\Fiken_Export' ) && method_exists( '\\FikenBilag\\Fiken_Export', 'eksporter_ordren' ) ) {
			$res = \FikenBilag\Fiken_Export::eksporter_ordren( $order_id );
			if ( is_array( $res ) && ! empty( $res['success'] ) ) {
				// Success note already added by exporter; also add brief admin notice via order note
				if ( is_object( $order ) && method_exists( $order, 'add_order_note' ) ) {
					$order->add_order_note( __( 'Fiken: Forced export executed from order actions.', 'peki-fiken-integration-for-woocommerce' ) );
				}
			} else {
				$msg = is_array( $res ) && ! empty( $res['error'] ) ? (string) $res['error'] : 'Unknown error';
				if ( is_object( $order ) && method_exists( $order, 'add_order_note' ) ) {
					/* translators: %s: error message */
					$order->add_order_note( sprintf( __( 'Fiken: Forced export failed - %s', 'peki-fiken-integration-for-woocommerce' ), $msg ) );
				}
			}
		}
	} catch ( \Throwable $e ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( 'Peki Fiken force export error: ' . $e->getMessage(), array( 'source' => 'peki-fiken' ) );
		}
		if ( is_object( $order ) && method_exists( $order, 'add_order_note' ) ) {
			/* translators: %s: error message */
			$order->add_order_note( sprintf( __( 'Fiken: Forced export error - %s', 'peki-fiken-integration-for-woocommerce' ), $e->getMessage() ) );
		}
	}
} );

/** -----------------------------------------------------------------
 * Plugin-lenker i utvidelseslista (Settings + portal)
 * ------------------------------------------------------------------ */
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( $links ) {
		$links = (array) $links;

		$settings_url  = admin_url( 'admin.php?page=fiken_innstillinger' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'peki-fiken-integration-for-woocommerce' ) . '</a>';
		array_unshift( $links, $settings_link );

		// Link directly to the internal Upgrade/Account page in WP Admin
		$portal_url = admin_url( 'admin.php?page=pekifiken_manage_subscription' );
		$state = (string) get_option( 'pekifiken_subscription_state', 'pending' );
		$link_text = ( $state === 'active' )
			? esc_html__( 'Manage Subscription', 'peki-fiken-integration-for-woocommerce' )
			: esc_html__( 'Go Pro', 'peki-fiken-integration-for-woocommerce' );
		$link_attrs = 'target="_blank" rel="noopener noreferrer"';
		if ( $state !== 'active' ) {
			$link_attrs .= ' style="color:#1a7f37;font-weight:600;"';
		}
		$portal     = '<a href="' . esc_url( $portal_url ) . '" ' . $link_attrs . '>' . $link_text . '</a>';
		$links[]    = $portal;

		return $links;
	}
);

/** Baseline admin notice removed (probe finished) */

/** -----------------------------------------------------------------
 * Orders list: Fiken export status column (HPOS + legacy)
 * ------------------------------------------------------------------ */
// Legacy posts table
add_filter( 'manage_edit-shop_order_columns', static function( $columns ) {
	if ( ! is_array( $columns ) ) { return $columns; }
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'order_status' ) {
			$new['fiken_export'] = __( 'Fiken', 'peki-fiken-integration-for-woocommerce' );
		}
	}
	if ( ! isset( $new['fiken_export'] ) ) {
		$new['fiken_export'] = __( 'Fiken', 'peki-fiken-integration-for-woocommerce' );
	}
	return $new;
}, 20 );

add_action( 'manage_shop_order_posts_custom_column', static function( $column, $post_id ) {
	if ( $column !== 'fiken_export' ) { return; }
	$order_id = (int) $post_id;
	$iid = (int) get_post_meta( $order_id, '_fiken_invoice_id', true );
	$invno = get_post_meta( $order_id, '_fiken_invoice_number', true );
	if ( $iid > 0 ) {
		/* translators: %s: invoice number */
		$title = $invno ? sprintf( __( 'Exported to Fiken (Invoice #%s)', 'peki-fiken-integration-for-woocommerce' ), (string) $invno ) : __( 'Exported to Fiken', 'peki-fiken-integration-for-woocommerce' );
		echo '<span class="dashicons dashicons-yes tips" style="color:#46b450;" aria-hidden="true" data-tip="' . esc_attr( $title ) . '"></span>';
	} else {
		echo '<span class="dashicons dashicons-minus tips" style="color:#ccd0d4;" aria-hidden="true" data-tip="' . esc_attr__( 'Not exported to Fiken', 'peki-fiken-integration-for-woocommerce' ) . '"></span>';
	}
}, 10, 2 );

// HPOS list table
add_filter( 'woocommerce_shop_order_list_table_columns', static function( $columns ) {
	if ( ! is_array( $columns ) ) { return $columns; }
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'order_status' ) {
			$new['fiken_export'] = __( 'Fiken', 'peki-fiken-integration-for-woocommerce' );
		}
	}
	if ( ! isset( $new['fiken_export'] ) ) {
		$new['fiken_export'] = __( 'Fiken', 'peki-fiken-integration-for-woocommerce' );
	}
	return $new;
}, 20 );

add_action( 'woocommerce_shop_order_list_table_custom_column', static function( $column, $order ) {
	if ( $column !== 'fiken_export' ) { return; }
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) { return; }
	$iid = (int) $order->get_meta( '_fiken_invoice_id', true );
	$invno = (string) $order->get_meta( '_fiken_invoice_number', true );
	if ( $iid > 0 ) {
		/* translators: %s: invoice number */
		$title = $invno ? sprintf( __( 'Exported to Fiken (Invoice #%s)', 'peki-fiken-integration-for-woocommerce' ), (string) $invno ) : __( 'Exported to Fiken', 'peki-fiken-integration-for-woocommerce' );
		echo '<span class="dashicons dashicons-yes tips" style="color:#46b450;" aria-hidden="true" data-tip="' . esc_attr( $title ) . '"></span>';
	} else {
		echo '<span class="dashicons dashicons-minus tips" style="color:#ccd0d4;" aria-hidden="true" data-tip="' . esc_attr__( 'Not exported to Fiken', 'peki-fiken-integration-for-woocommerce' ) . '"></span>';
	}
}, 10, 2 );
