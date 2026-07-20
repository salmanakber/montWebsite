<?php
namespace FikenBilag\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin notices for Peki – Fiken Integration for WooCommerce
 */
class Admin_Notices {
    /** @var Admin_State */
    private $state;

    /** Prevent multiple bootstraps */
    private static $booted = false;

    public function __construct( Admin_State $state ) {
        if ( self::$booted ) return;
        self::$booted = true;

        $this->state = $state;

        add_action( 'admin_notices', [ $this, 'render_admin_notices' ], 3 );
        add_action( 'admin_init',   [ $this, 'handle_dismiss' ] );
        add_action( 'network_admin_notices', [ $this, 'render_admin_notices' ], 3 );
        add_action( 'wp_ajax_pekifiken_dismiss_notice', [ $this, 'ajax_dismiss_notice' ] );
    }

    /* ---------------------- Helpers ---------------------- */

    private function debug_mode(): bool {
        return defined('PEKIFIKEN_DEBUG_REMAINING') || defined('PEKIFIKEN_DEBUG_FORCE');
    }

    private function get_dismissed_notices() : array {
        $user_id = get_current_user_id();
        if ( ! $user_id ) return [];
        $arr = get_user_meta( $user_id, 'pekifiken_dismissed_notices', true );
        return is_array( $arr ) ? $arr : [];
    }

    private function dismiss_notice( string $key ) : void {
        $user_id = get_current_user_id();
        if ( ! $user_id ) return;
        $arr = $this->get_dismissed_notices();
        $arr[ $key ] = time();
        update_user_meta( $user_id, 'pekifiken_dismissed_notices', $arr );
    }

    private function is_relevant_screen(): bool {
        if ( ! is_admin() ) return false;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for current admin page
        $page = isset($_GET['page']) ? sanitize_key( wp_unslash($_GET['page']) ) : '';
        $our_pages = [ 'fiken_innstillinger', 'fiken_upgrade', 'fiken_portal' ];
        if ( in_array( $page, $our_pages, true ) ) return true;

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ( $screen && is_object($screen) ) {
            $id = (string) $screen->id;
            if ( $id === 'edit-shop_order' || $id === 'shop_order' ) return true;
            if ( strpos( $id, 'wc-orders' ) !== false || strpos( $id, 'woocommerce_page_wc-orders' ) !== false ) return true;
            if ( strpos( $id, 'woocommerce' ) !== false ) return true;
        }
        return false;
    }

    private function parse_int( $v ): ?int {
        if ( ! is_scalar( $v ) ) return null;
        $s = trim( (string) $v );
        if ( $s === '' ) return null;
        return is_numeric( $s ) ? (int) $s : null;
    }

    private function local_remaining_override(): ?int {
        if ( defined( 'PEKIFIKEN_DEBUG_REMAINING' ) ) {
            $n = $this->parse_int( constant( 'PEKIFIKEN_DEBUG_REMAINING' ) );
            if ( $n !== null ) return max( 0, $n );
        }
        $rem = $this->parse_int( get_option( 'pekifiken_quota_remaining', null ) );
        if ( $rem !== null ) return max( 0, $rem );
        $limit = $this->parse_int( get_option( 'pekifiken_quota_limit', null ) );
        $used  = $this->parse_int( get_option( 'pekifiken_quota_used', null ) );
        if ( $limit !== null && $used !== null ) return max( 0, $limit - $used );
        return null;
    }

    private function normalize_server_remaining( $quota ): ?int {
        if ( is_array( $quota ) ) {
            if ( isset( $quota['remaining'] ) ) {
                $r = $this->parse_int( $quota['remaining'] );
                if ( $r !== null ) return max( 0, $r );
            }
            if ( isset( $quota['limit'], $quota['used'] ) ) {
                $L = $this->parse_int( $quota['limit'] );
                $U = $this->parse_int( $quota['used'] );
                if ( $L !== null && $U !== null ) return max( 0, $L - $U );
            }
        } else {
            $n = $this->parse_int( $quota );
            if ( $n !== null ) return max( 0, $n );
        }
        return null;
    }

    private function period_suffix( array $quota = null ): string {
        if ( is_array( $quota ) && ! empty( $quota['reset'] ) && is_string( $quota['reset'] ) ) {
            $ym = substr( $quota['reset'], 0, 7 );
            if ( preg_match( '/^\d{4}-\d{2}$/', $ym ) ) return $ym;
        }
        return gmdate( 'Y-m' );
    }

    /* ---------------------- Dismiss ---------------------- */

    public function handle_dismiss() {
        if ( ! is_admin() ) return;
        $dismiss = isset( $_GET['pekifiken_dismiss'] ) ? sanitize_key( wp_unslash($_GET['pekifiken_dismiss'] ) ) : '';
        if ( ! $dismiss ) return;
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) return;
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'pekifiken_dismiss_' . $dismiss ) ) return;
        $this->dismiss_notice( $dismiss );
        wp_safe_redirect( remove_query_arg( [ 'pekifiken_dismiss', '_wpnonce' ] ) );
        exit;
    }

    public function ajax_dismiss_notice() {
        if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
            wp_die();
        }
        $key   = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! $key || ! wp_verify_nonce( $nonce, 'pekifiken_dismiss_' . $key ) ) {
            wp_send_json_error( [ 'message' => 'bad_nonce' ], 400 );
            wp_die();
        }
        $this->dismiss_notice( $key );
        wp_send_json_success( [ 'dismissed' => $key ] );
        wp_die();
    }

    /* ---------------------- Render ---------------------- */

    public function render_admin_notices() {
        static $has_run = false;
        if ( $has_run ) return;
        $has_run = true;
        if ( ! is_admin() || ! $this->is_relevant_screen() ) return;

        // Force refresh from Peki server when showing notices (throttled to 10 sec)
        if ( ! get_transient( 'pekifiken_notice_check' ) ) {
            set_transient( 'pekifiken_notice_check', 1, 10 );
            delete_transient( 'pekifiken_state_throttle' );
            if ( method_exists( '\FikenBilag\Admin\Admin_State', 'refresh_subscription_state_from_server' ) ) {
                \FikenBilag\Admin\Admin_State::refresh_subscription_state_from_server();
            }
        }
        
        // Debug: show last sync info (log removed to satisfy coding standards)

        $dismissed   = $this->get_dismissed_notices();
        $printed     = false;

        // Check connection status
        $refresh_tok  = (string) get_option( 'pekifiken_refresh_token', '' );
        $employee_tok = (string) get_option( 'pekifiken_employee_token', '' );
        if ( $employee_tok === '' ) {
            $employee_tok = (string) get_option( 'fiken_employee_token', '' );
            if ( $employee_tok === '' ) {
                $employee_tok = (string) get_option( 'wfb_employee_token', '' );
            }
        }
        $is_connected = ( $refresh_tok !== '' || $employee_tok !== '' );

        // Get quota from server (now refreshed values from pekifiken_transfers_used/limit)
        $used_opt  = get_option( 'pekifiken_transfers_used', null );
        $limit_opt = get_option( 'pekifiken_transfers_limit', null );
        $used  = ($used_opt === false || $used_opt === '' || $used_opt === null) ? null : (int)$used_opt;
        $limit = ($limit_opt === false || $limit_opt === '' || $limit_opt === null) ? null : (int)$limit_opt;

        $remaining = null;
        $quota_raw = null;

        $remaining = $this->local_remaining_override();
        if ( $remaining === null && method_exists( $this->state, 'get_remaining_quota' ) ) {
            $quota_raw = $this->state->get_remaining_quota();
            $remaining = $this->normalize_server_remaining( $quota_raw );
        }

        $state        = (string) get_option( 'pekifiken_subscription_state', 'pending' );
        $is_paid      = ( $state === 'active' );
        $auto_enabled = (string) get_option( 'pekifiken_auto_upgrade_enabled', '0' ) === '1';

        if ( ! $is_paid && $limit === null ) { $limit = 15; }

        if ( $remaining === null && $limit !== null && $used !== null ) {
            $remaining = max( 0, $limit - $used );
        }

        // Debug output (only when WP_DEBUG is enabled)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $debug_always = defined( 'PEKIFIKEN_DEBUG_NOTICES' ) && PEKIFIKEN_DEBUG_NOTICES;
            if ( $debug_always ) {
                echo '<div class="notice notice-info"><p><strong>🔍 Debug:</strong> '
                    . 'connected=' . esc_html( $is_connected ? 'yes' : 'no' ) . ', '
                    . 'used=' . esc_html( $used !== null ? (string) $used : 'null' ) . ', '
                    . 'limit=' . esc_html( $limit !== null ? (string) $limit : 'null' ) . ', '
                    . 'remaining=' . esc_html( $remaining !== null ? (string) $remaining : 'null' ) . ', '
                    . 'is_paid=' . esc_html( $is_paid ? 'yes' : 'no' )
                    . '</p></div>';
            }
        }

        /* -------- NOT CONNECTED -------- */
        if ( ! $is_connected && empty( $dismissed['not_connected'] ) ) {
            $nonce       = wp_create_nonce( 'pekifiken_dismiss_not_connected' );
            $dismiss_url = wp_nonce_url( add_query_arg( 'pekifiken_dismiss', 'not_connected' ), 'pekifiken_dismiss_not_connected' );
            echo '<div id="pekifiken-notice-not-connected" class="notice notice-warning is-dismissible fiken-dismissible"'
                . ' data-key="not_connected"'
                . ' data-dismiss-nonce="' . esc_attr( $nonce ) . '"'
                . ' data-dismiss-url="' . esc_url( $dismiss_url ) . '">';
            echo '<p><strong>' . esc_html__( 'Fiken is not connected yet.', 'peki-fiken-integration-for-woocommerce' ) . '</strong> ';
            echo esc_html__( 'Please connect to enable exports.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
            echo '<p>';
            echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=fiken_innstillinger' ) ) . '">';
            echo esc_html__( 'Connect to Fiken', 'peki-fiken-integration-for-woocommerce' );
            echo '</a>';
            echo '</p>';
            echo '</div>';
            $printed = true;
            return; // Don't show quota notices if not connected
        }

        /* -------- FREE PLAN -------- */
        // Free: info at used=10; error only when remaining==0 (or used>=limit if remaining unknown)
        if ( ! $is_paid ) {
            $upgrade_url = admin_url( 'admin.php?page=pekifiken_manage_subscription' );
            
            // Calculate remaining and used consistently
            $limit_safe = ($limit !== null) ? (int)$limit : 15;
            $used_safe  = null;
            $remaining_safe = null;

            // Priority 1: Use explicit remaining value
            if ( $remaining !== null ) {
                $remaining_safe = (int) max(0, $remaining);
                $used_safe = max(0, $limit_safe - $remaining_safe);
            }
            // Priority 2: Calculate from used and limit
            elseif ( $used !== null ) {
                $used_safe = (int) $used;
                $remaining_safe = max(0, $limit_safe - $used_safe);
            }

            // HARD STOP: Show error if limit reached (remaining = 0 OR used >= limit)
            $is_hard_stop = false;
            if ( $remaining_safe !== null && $remaining_safe === 0 ) {
                $is_hard_stop = true;
            } elseif ( $used_safe !== null && $used_safe >= $limit_safe ) {
                $is_hard_stop = true;
            }

            if ( $is_hard_stop ) {
                $notice_id = 'pekifiken-hardstop-' . $used_safe . '-of-' . $limit_safe;
                echo '<div id="' . esc_attr( $notice_id ) . '" class="notice notice-error"><p>';
                /* translators: %d: transfer limit */
                printf( esc_html__( 'You have reached your free monthly transfer limit (%d). Exports are paused until next month or you upgrade your plan.', 'peki-fiken-integration-for-woocommerce' ), (int) $limit_safe );
                echo '</p><p><a href="' . esc_url( $upgrade_url ) . '" class="button button-primary">'
                    . esc_html__( 'Upgrade plan', 'peki-fiken-integration-for-woocommerce' )
                    . '</a></p>';
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    echo '<p style="font-size:11px;color:#666;margin-top:10px;"><code>used=' . esc_html( $used_safe ) . ', limit=' . esc_html( $limit_safe ) . ', remaining=' . esc_html( $remaining_safe !== null ? $remaining_safe : 'null' ) . '</code></p>';
                }
                echo '</div>';
                $printed = true;
                return;
            }

            // SOFT WARNING: Show info if used >= 10 (but not yet at limit)
            if ( $used_safe !== null && $used_safe >= 10 && $used_safe < $limit_safe ) {
                $period       = $this->period_suffix( is_array( $quota_raw ) ? $quota_raw : [] );
                $dismiss_key  = 'pekifiken_free_warn10_' . $period . '_u' . $used_safe;
                $is_dismissed = ! empty( $dismissed[ $dismiss_key ] );
                if ( ! $is_dismissed ) {
                    $nonce       = wp_create_nonce( 'pekifiken_dismiss_' . $dismiss_key );
                    $dismiss_url = wp_nonce_url( add_query_arg( 'pekifiken_dismiss', $dismiss_key ), 'pekifiken_dismiss_' . $dismiss_key );
                    echo '<div id="pekifiken-notice-' . esc_attr( $dismiss_key ) . '" class="notice notice-info is-dismissible fiken-dismissible"'
                        . ' data-key="' . esc_attr( $dismiss_key ) . '"'
                        . ' data-dismiss-nonce="' . esc_attr( $nonce ) . '"'
                        . ' data-dismiss-url="' . esc_url( $dismiss_url ) . '">';
                    echo '<p><strong>';
                    /* translators: %1$d: number of transfers used, %2$d: transfer limit */
                    printf( esc_html__( 'You have used %1$d of %2$d free transfers.', 'peki-fiken-integration-for-woocommerce' ), (int) $used_safe, (int) $limit_safe );
                    echo '</strong> '
                        . esc_html__( 'Consider upgrading to avoid interruptions.', 'peki-fiken-integration-for-woocommerce' )
                        . '</p>';
                    echo '<p><a href="' . esc_url( $upgrade_url ) . '" class="button button-primary">'
                        . esc_html__( 'Upgrade plan', 'peki-fiken-integration-for-woocommerce' )
                        . '</a></p>';
                    echo '</div>';
                    $printed = true;
                }
            }
        }

        /* -------- PAID PLAN -------- */
        if ( $is_paid && $remaining !== null ) {
            if ( (int) $remaining === 0 ) {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'You have reached your monthly transfer limit. Exports are paused until your quota resets or you upgrade your plan.', 'peki-fiken-integration-for-woocommerce' ) . '</p></div>';
                $printed = true;
            } elseif ( ! $auto_enabled && (int) $remaining <= 10 ) {
                echo '<div class="notice notice-warning"><p>';
                /* translators: %d: number of transfers remaining in the current month */
                printf( esc_html__( 'Only %d transfers left this month.', 'peki-fiken-integration-for-woocommerce' ), (int) $remaining );
                echo '</p></div>';
                $printed = true;
            }
        }
    }
}
