<?php
namespace FikenBilag\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin {
    private static $instance = null;

    /** @var \FikenBilag\Admin\Admin_Menu */
    public $menu;
    /** @var \FikenBilag\Admin\Admin_Assets */
    public $assets;
    /** @var \FikenBilag\Admin\Admin_State */
    public $state;
    /** @var \FikenBilag\Admin\Admin_Notices */
    public $notices;
    /** @var \FikenBilag\Admin\Admin_Connect */
    public $connect;

    public static function instance() {
        if ( ! self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Load submodules
        $this->state   = new Admin_State();
        $this->menu    = new Admin_Menu();
        $this->assets  = new Admin_Assets();
        $this->notices = new Admin_Notices( $this->state );
        $this->connect = new Admin_Connect( $this->state );

        // Hooks that depend on modules
        add_action( 'admin_menu',          [ $this->menu,    'register_admin_menu' ], 20 );
        add_action( 'admin_enqueue_scripts',[ $this->assets,  'enqueue_admin_assets' ] );
        add_action( 'admin_head',          [ $this->menu,    'adjust_menu_labels' ], 99 );

        // Throttled background refresh of subscription state
        add_action( 'admin_init', [ $this->state, 'maybe_refresh_subscription_state' ] );

        // Handle auto-upgrade toggle POST
        add_action( 'admin_post_pekifiken_toggle_auto_upgrade', [ $this, 'handle_toggle_auto_upgrade' ] );
    }

    public function handle_toggle_auto_upgrade() {
        if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) ) {
            wp_die( esc_html__( 'You do not have permission to change this setting.', 'peki-fiken-integration-for-woocommerce' ) );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $nonce = isset( $_POST['pekifiken_toggle_auto_upgrade_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pekifiken_toggle_auto_upgrade_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'pekifiken_toggle_auto_upgrade' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'peki-fiken-integration-for-woocommerce' ) );
        }

        $enabled = isset( $_POST['enabled'] ) ? 1 : 0;
        // Discover company slug (prefer pekifiken_ with fallbacks)
        $company_slug = (string) get_option( 'pekifiken_company_slug', (string) get_option( 'fiken_company_slug', (string) get_option( 'wfb_company_slug', '' ) ) );
        // Call server to persist (HMAC not required here)
        $url = apply_filters( 'pekifiken_api_url', 'https://peki.no/fiken/index.php' );
        $payload = [
            'action' => 'set_auto_upgrade',
            'data'   => [
                'enabled'     => $enabled,
                'companySlug' => $company_slug,
            ],
            'site'   => home_url(),
            'plugin' => 'peki-fiken-integration-for-woocommerce',
        ];

        $resp = wp_remote_post( $url, [
            'timeout' => 12,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $payload ),
        ] );

        wp_safe_redirect( admin_url( 'admin.php?page=pekifiken_manage_subscription&auto=' . ( $enabled ? '1' : '0' ) ) );
        exit;
    }
}
