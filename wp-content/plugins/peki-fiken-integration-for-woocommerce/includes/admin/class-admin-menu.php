<?php
namespace FikenBilag\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin_Menu {

    public function register_admin_menu() {
        $cap = current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options';

        add_menu_page(
            'Fiken',
            'Fiken',
            $cap,
            'fiken_innstillinger',
            [ $this, 'render_settings_page' ],
            'dashicons-media-spreadsheet',
            56
        );

        add_submenu_page(
            'fiken_innstillinger',
            esc_html__( 'Settings', 'peki-fiken-integration-for-woocommerce' ),
            esc_html__( 'Settings', 'peki-fiken-integration-for-woocommerce' ),
            $cap,
            'fiken_innstillinger',
            [ $this, 'render_settings_page' ]
        );

        add_submenu_page(
            'fiken_innstillinger',
            $this->get_subscription_menu_label(),
            $this->get_subscription_menu_label(),
            $cap,
            'pekifiken_manage_subscription',
            [ $this, 'render_upgrade_router' ]
        );
    }

    public function adjust_menu_labels() {
        global $submenu;
        if ( ! isset( $submenu['fiken_innstillinger'] ) || ! is_array( $submenu['fiken_innstillinger'] ) ) return;
        $desired = $this->get_subscription_menu_label();
        foreach ( $submenu['fiken_innstillinger'] as &$item ) {
            if ( isset( $item[2] ) && $item[2] === 'pekifiken_manage_subscription' ) {
                $item[0] = $desired;
                break;
            }
        }
    }

    private function get_subscription_menu_label() : string {
        $state = (string) get_option( 'pekifiken_subscription_state', 'pending' );
        return ( $state === 'active' )
            ? esc_html__( 'Account', 'peki-fiken-integration-for-woocommerce' )
            : esc_html__( 'Upgrade', 'peki-fiken-integration-for-woocommerce' );
    }

    public function render_settings_page() {
        // Optional: bruk eksisterende settings-side om den finnes
        if ( class_exists( '\FikenBilag\Fiken_Settings_Page' ) ) {
            $page = new \FikenBilag\Fiken_Settings_Page();
            if ( method_exists( $page, 'render' ) ) {
                $page->render();
                return;
            }
        }
        echo '<div class="wrap"><h1>Fiken</h1><p>' .
            esc_html__( 'Settings page class is missing.', 'peki-fiken-integration-for-woocommerce' ) .
        '</p></div>';
    }

    public function render_upgrade_router() {
        // ✅ IKKE statisk kall – instansier og kall metoden
        if ( class_exists( '\FikenBilag\Fiken_Upgrade_Page' ) ) {
            try {
                $page = new \FikenBilag\Fiken_Upgrade_Page();
                if ( method_exists( $page, 'render' ) ) {
                    $page->render();
                    return;
                }
            } catch ( \Throwable $e ) {
                // Faller gjennom til ekstern portal hvis noe feiler
            }
        }

        // Fallback: ekstern portal
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'peki-fiken-integration-for-woocommerce' ) );
        }
        $shop = site_url();
        $url  = add_query_arg( [ 'shop' => $shop ], 'https://peki.no/fiken/upgrade.php' );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html( $this->get_subscription_menu_label() ) . '</h1>';
        echo '<p>' . esc_html__( 'Open the external portal to manage billing and subscription.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' .
            esc_html__( 'Open portal', 'peki-fiken-integration-for-woocommerce' ) . '</a></p>';
        echo '</div>';
    }
}
