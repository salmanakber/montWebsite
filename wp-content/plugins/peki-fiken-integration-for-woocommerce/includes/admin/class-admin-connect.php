<?php
namespace FikenBilag\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin_Connect {
    /** @var Admin_State */
    private $state;

    public function __construct( Admin_State $state ) {
        $this->state = $state;

        add_action( 'admin_post_pekifiken_start_connect', [ $this, 'start_connect' ] );
        add_action( 'admin_post_pekifiken_callback', [ $this, 'handle_callback' ] );
        add_action( 'admin_post_nopriv_pekifiken_callback', [ $this, 'handle_callback' ] );
        // Legacy return target support: if server redirects to admin.php with ?pekifiken_callback=1
        add_action( 'admin_init', [ $this, 'handle_callback' ] );
        add_action( 'wp_ajax_pekifiken_refresh_status', [ $this, 'ajax_refresh_status' ] );
    }

    /**
     * Start the external connect flow (redirect to peki.no).
     */
    public function start_connect() {
        if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'peki-fiken-integration-for-woocommerce' ) );
        }
        // Verifies admin-post form submit
        check_admin_referer( 'pekifiken_start_connect', 'pekifiken_start_connect_nonce' );

        $shop  = site_url();
        $state = wp_generate_password( 20, false, false );
        set_transient( 'pekifiken_connect_state_' . $state, 1, 10 * MINUTE_IN_SECONDS );

        $return = add_query_arg(
            [ 'action' => 'pekifiken_callback', 'state' => $state ],
            admin_url( 'admin-post.php' )
        );

        $connect_url = add_query_arg(
            [ 'shop' => $shop, 'return' => $return, 'state' => $state ],
            'https://peki.no/fiken/connect.php'
        );

        wp_safe_redirect( $connect_url );
        exit;
    }

    /**
     * Handle the callback after connect flow returns from peki.no.
     * Note: This is an OAuth-like external callback where WP nonces are not applicable.
     * CSRF protection is enforced via the opaque "state" parameter stored as a transient.
     */
    public function handle_callback() {
        // Guard: ensure this is our callback (supports admin-post, legacy flag, or direct token return)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from external service
        $req_action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from external service
        $has_flag   = ( isset( $_GET['pekifiken_callback'] ) && $_GET['pekifiken_callback'] === '1' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from external service
        $has_direct0 = isset( $_GET['employee_token'] );
        if ( $req_action !== 'pekifiken_callback' && ! $has_flag && ! $has_direct0 ) {
            return;
        }

        // Capability check (do not die; redirect with notice)
        $user_can = ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) );
        if ( ! $user_can ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'fiken_innstillinger', 'error' => 'forbidden' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        // Validate that this indeed looks like our callback
        $has_direct = isset( $_GET['employee_token'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- external callback cannot carry WP nonce
        $has_state  = isset( $_GET['state'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- external callback cannot carry WP nonce
        if ( ! $has_direct && ! $has_state ) {
            return;
        }

        // Read and sanitize external callback params (nonce not applicable; validated by state transient below)
        $state          = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- validated via transient
        $employee_token = isset( $_GET['employee_token'] ) ? sanitize_text_field( wp_unslash( $_GET['employee_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- external callback
        $connection_id  = isset( $_GET['connection_id'] ) ? sanitize_text_field( wp_unslash( $_GET['connection_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- external callback
        $company_slug   = isset( $_GET['company_slug'] ) ? sanitize_text_field( wp_unslash( $_GET['company_slug'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- external callback
        $refresh_token  = isset( $_GET['refresh_token'] ) ? sanitize_text_field( wp_unslash( $_GET['refresh_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- external callback
        $state_from_srv = isset( $_GET['state_subscription'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['state_subscription'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- external callback

        // CSRF/state validation
        if ( empty( $state ) || ! get_transient( 'pekifiken_connect_state_' . $state ) ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'fiken_innstillinger', 'error' => 'state' ], admin_url( 'admin.php' ) ) );
            exit;
        }
        delete_transient( 'pekifiken_connect_state_' . $state );

        // Must receive at least one token
        if ( empty( $employee_token ) && empty( $refresh_token ) ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'fiken_innstillinger', 'error' => 'token' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        if ( $employee_token !== '' ) update_option( 'pekifiken_employee_token', $employee_token, 'no' );
        if ( $refresh_token  !== '' ) update_option( 'pekifiken_refresh_token',  $refresh_token,  'no' );
        if ( $connection_id  !== '' ) update_option( 'pekifiken_connection_id',  $connection_id,  'no' );
        if ( $company_slug   !== '' ) update_option( 'pekifiken_company_slug',   $company_slug,   'no' );

        $valid_states = [ 'active', 'pending', 'none' ];
        $state_norm   = in_array( $state_from_srv, $valid_states, true ) ? $state_from_srv : 'pending';
        if ( $state_norm !== 'active' ) {
            $state_norm = 'pending';
        }

        update_option( 'pekifiken_subscription_state', $state_norm, true );
        update_option( 'pekifiken_has_active_subscription', $state_norm === 'active' ? '1' : '0', true );

        update_option( 'pekifiken_sync_last', wp_json_encode( [
            'time'  => current_time( 'mysql' ),
            'note'  => 'admin-post-callback',
            'state' => $state_norm,
        ] ), true );

        wp_safe_redirect( add_query_arg( [ 'page' => 'fiken_innstillinger', 'connected' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * AJAX: Refresh subscription status from central server and return state.
     */
    public function ajax_refresh_status() {
        if ( ! ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }
        check_ajax_referer( 'pekifiken_refresh_status', 'nonce' );

        Admin_State::refresh_subscription_state_from_server();

        $state = (string) get_option( 'pekifiken_subscription_state', 'pending' );
        $dbg   = json_decode( (string) get_option( 'pekifiken_sync_last', '' ), true );

        $out = [ 'state' => $state ];
        if ( is_array( $dbg ) ) {
            $out['time']  = isset( $dbg['time'] ) ? (string) $dbg['time'] : '';
            $out['http']  = isset( $dbg['http'] ) ? (int) $dbg['http'] : null;
            $out['error'] = isset( $dbg['error'] ) ? (string) $dbg['error'] : '';
            $out['note']  = isset( $dbg['note'] ) ? (string) $dbg['note'] : '';
        }

        wp_send_json_success( $out );
    }
}
