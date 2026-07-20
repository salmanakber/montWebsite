<?php
namespace FikenBilag;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( '\FikenBilag\Fiken_Settings_Page', false ) ) :

class Fiken_Settings_Page {

    /**
     * Render settings page (admin).
     */
    public function render() {
        // Samme cap som menyen
        $has_cap = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
        if ( ! $has_cap ) {
            echo '<div class="notice notice-error"><p>' .
                esc_html__( 'You do not have permission to access these settings.', 'peki-fiken-integration-for-woocommerce' ) .
            '</p></div>';
            return;
        }

        // Handle form submissions
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            $this->handle_post_requests();
        }

        // Get current tab
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current_tab = sanitize_key( $_GET['tab'] ?? 'connection' );
        if ( ! in_array( $current_tab, array( 'connection', 'gateways', 'advanced', 'logs', 'support' ), true ) ) {
            $current_tab = 'connection';
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Fiken Integration Settings', 'peki-fiken-integration-for-woocommerce' ) . '</h1>';

        // Check VAT registration status from backend and cache a local flag
        $vat_blocked = false;
        $vat_status  = $this->get_vat_status();
        if ( is_array( $vat_status ) ) {
            if ( isset( $vat_status['vat_registered'] ) && ! $vat_status['vat_registered'] ) {
                $vat_blocked = true;
                update_option( 'fiken_blocked_non_vat_company', true, false );
                echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Integration disabled:', 'peki-fiken-integration-for-woocommerce' ) . '</strong> ' . esc_html__( 'Company is not VAT registered in Fiken. Please register for VAT to use this integration.', 'peki-fiken-integration-for-woocommerce' ) . '</p></div>';
            } else {
                update_option( 'fiken_blocked_non_vat_company', false, false );
            }
        }

        // Show notices
        $this->show_notices();

        // Tab navigation
        $this->render_tabs( $current_tab );

        // Tab content
        if ( $current_tab === 'connection' ) {
            $this->render_connection_tab( $vat_blocked );
        } elseif ( $current_tab === 'gateways' ) {
            $this->render_gateways_tab( $vat_blocked );
        } elseif ( $current_tab === 'advanced' ) {
            $this->render_advanced_tab();
        } elseif ( $current_tab === 'logs' ) {
            $this->render_logs_tab();
        } elseif ( $current_tab === 'support' ) {
            $this->render_support_tab();
        }

        echo '</div>'; // .wrap
    }

    /**
     * Handle POST requests for both tabs.
     */
    private function handle_post_requests() {
        // Handle disconnect
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( isset( $_POST['pekifiken_disconnect'] ) ) {
            check_admin_referer( 'pekifiken_disconnect', 'pekifiken_disconnect_nonce' );
            $this->do_disconnect();
            wp_safe_redirect( add_query_arg( array( 'page' => 'fiken_innstillinger', 'disconnected' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        // Handle gateways settings
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( isset( $_POST['save_gateways'] ) ) {
            check_admin_referer( 'pekifiken_gateways', 'pekifiken_gateways_nonce' );

            // Collect raw inputs (nonce verified above)
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values sanitized in save_gateways_settings()
            $payment_account_map_raw = isset( $_POST['pekifiken_bank_account_map'] ) ? (array) wp_unslash( $_POST['pekifiken_bank_account_map'] ) : array();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values sanitized in save_gateways_settings()
            $cash_behavior_map_raw   = isset( $_POST['cash_behavior_map'] ) ? (array) wp_unslash( $_POST['cash_behavior_map'] ) : array();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values sanitized in save_gateways_settings()
            $shipping_account_raw    = isset( $_POST['shipping_income_account'] ) ? (string) wp_unslash( $_POST['shipping_income_account'] ) : '';

            $this->save_gateways_settings( $payment_account_map_raw, $cash_behavior_map_raw, $shipping_account_raw );
            wp_safe_redirect( add_query_arg( array( 'page' => 'fiken_innstillinger', 'tab' => 'gateways', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        // Handle Advanced tab save (Force no VAT)
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( isset( $_POST['save_advanced'] ) ) {
            check_admin_referer( 'pekifiken_advanced', 'pekifiken_advanced_nonce' );

			// Growth gating: fetch live status to reflect upgrades immediately; fallback to cached option
			$eligible = false;
			if ( class_exists( '\FikenBilag\Fiken_Status' ) ) {
				$live = \FikenBilag\Fiken_Status::fetch_cached_or_live( 120 );
				if ( is_array( $live ) ) {
					\FikenBilag\Fiken_Status::update_cache_from_array( $live, 120 );
					$plan = isset( $live['plan'] ) ? strtoupper( (string) $live['plan'] ) : '';
					$eligible = ( $plan === 'GROWTH' );
				}
			}
			if ( ! $eligible ) {
				$status = get_option( 'fiken_last_status', array() );
				$plan   = is_array( $status ) && isset( $status['plan'] ) ? strtoupper( (string) $status['plan'] ) : '';
				$eligible = ( $plan === 'GROWTH' );
			}

            $force_no_vat = isset( $_POST['fiken_force_no_vat'] ) ? '1' : '0';
            update_option( 'fiken_force_no_vat', $force_no_vat === '1', false );

			// Enable auto-save of invoice PDFs
			$auto_pdf = isset( $_POST['fiken_auto_save_invoice_pdf'] ) ? '1' : '0';
			update_option( 'fiken_auto_save_invoice_pdf', $eligible && $auto_pdf === '1', false );

            // Enable/disable per-gateway overrides
			$cash_behavior_enabled = isset( $_POST['fiken_cash_behavior_enabled'] ) ? '1' : '0';
			update_option( 'fiken_cash_behavior_enabled', $eligible && $cash_behavior_enabled === '1', false );

            // Save per-gateway document type overrides (cash behavior) posted from Advanced tab
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values sanitized below
            $cash_behavior_map_raw = isset( $_POST['cash_behavior_map'] ) ? (array) wp_unslash( $_POST['cash_behavior_map'] ) : array();
			if ( is_array( $cash_behavior_map_raw ) && $eligible ) {
                $sanitized_cash_map = array();
                foreach ( $cash_behavior_map_raw as $gateway_id => $behavior ) {
                    $gateway_id = sanitize_key( $gateway_id );
                    $behavior   = sanitize_text_field( $behavior );
                    if ( $gateway_id && $behavior === 'false' ) {
                        // Only store explicit overrides to false (force Invoice)
                        $sanitized_cash_map[ $gateway_id ] = 'false';
                    }
                }
                update_option( 'fiken_gateway_cash_map', $sanitized_cash_map );
            }

            wp_safe_redirect( add_query_arg( array( 'page' => 'fiken_innstillinger', 'tab' => 'advanced', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        // Handle support form submit
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( isset( $_POST['pekifiken_support_send'] ) ) {
            check_admin_referer( 'pekifiken_support', 'pekifiken_support_nonce' );

            $from_email = isset( $_POST['support_email'] ) ? sanitize_email( wp_unslash( $_POST['support_email'] ) ) : '';
            $subject_in = isset( $_POST['support_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['support_subject'] ) ) : '';
            $message_in = isset( $_POST['support_message'] ) ? wp_kses_post( wp_unslash( $_POST['support_message'] ) ) : '';

            $site     = site_url();
            $user     = wp_get_current_user();
            $user_str = ( $user && $user->ID ) ? ( $user->display_name . ' <' . $user->user_email . '>' ) : '';

            $to       = 'petter@peki.no';
            $subject  = ( $subject_in !== '' ) ? $subject_in : 'Woo → Fiken support request';
            $subject  = '[Peki Fiken] ' . $subject;

            $lines = array();
            $lines[] = 'Site: ' . $site;
            if ( $user_str !== '' ) { $lines[] = 'User: ' . $user_str; }
            if ( defined( 'PEKIFIKEN_VERSION' ) ) { $lines[] = 'Plugin: ' . PEKIFIKEN_VERSION; }
            $lines[] = 'WordPress: ' . get_bloginfo( 'version' );
            $lines[] = 'WooCommerce: ' . ( defined( 'WC_VERSION' ) ? WC_VERSION : 'n/a' );
            $lines[] = str_repeat( '-', 32 );
            if ( $message_in !== '' ) { $lines[] = wp_strip_all_tags( $message_in ); }

            $body    = implode( "\n", $lines );
            $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
            if ( is_email( $from_email ) ) {
                $headers[] = 'Reply-To: ' . $from_email;
            }

            $sent = wp_mail( $to, $subject, $body, $headers );
            $flag = $sent ? '1' : '0';
            $nonce = wp_create_nonce( 'pekifiken_support_result' );
            $url = add_query_arg( array( 'page' => 'fiken_innstillinger', 'tab' => 'support', 'support' => $flag, 'support_nonce' => $nonce ), admin_url( 'admin.php' ) );
            wp_safe_redirect( $url );
            exit;
        }
    }

    /**
     * Show admin notices.
     */
    private function show_notices() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['connected'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__( 'Connected to Fiken.', 'peki-fiken-integration-for-woocommerce' ) .
            '</p></div>';
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['disconnected'] ) ) {
            echo '<div class="notice notice-success is-dismissible" style="border-left-color: #00a32a;"><p>' .
                esc_html__( 'Disconnected from Fiken. Local credentials removed.', 'peki-fiken-integration-for-woocommerce' ) .
            '</p></div>';
            // Clear the parameter to prevent re-display on refresh
            echo '<script>if (window.history && window.history.replaceState) { var url = new URL(window.location); url.searchParams.delete("disconnected"); window.history.replaceState({}, "", url); }</script>';
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__( 'Settings saved.', 'peki-fiken-integration-for-woocommerce' ) .
            '</p></div>';
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['error'] ) && $_GET['error'] === 'token' ) {
            echo '<div class="notice notice-error is-dismissible"><p>' .
                esc_html__( 'Connect failed: missing token from server.', 'peki-fiken-integration-for-woocommerce' ) .
            '</p></div>';
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['error'] ) && $_GET['error'] === 'state' ) {
            echo '<div class="notice notice-error is-dismissible"><p>' .
                esc_html__( 'Connect failed: state verification failed. Please try again.', 'peki-fiken-integration-for-woocommerce' ) .
            '</p></div>';
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['support'] ) ) {
            $nonce = isset( $_GET['support_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['support_nonce'] ) ) : '';
            if ( $nonce && wp_verify_nonce( $nonce, 'pekifiken_support_result' ) ) {
                $ok = sanitize_text_field( wp_unslash( $_GET['support'] ) );
                if ( $ok === '1' ) {
                    echo '<div class="notice notice-success is-dismissible"><p>' .
                        esc_html__( 'Support request sent. We will get back to you shortly.', 'peki-fiken-integration-for-woocommerce' ) .
                    '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' .
                        esc_html__( 'Failed to send support request. Please try again later.', 'peki-fiken-integration-for-woocommerce' ) .
                    '</p></div>';
                }
            }
        }
    }

    /**
     * Render tab navigation.
     */
    private function render_tabs( string $current_tab ) {
        $tabs = array(
            'connection' => __( 'Connection', 'peki-fiken-integration-for-woocommerce' ),
            'gateways'   => __( 'Bank Account Mapping', 'peki-fiken-integration-for-woocommerce' ),
            'advanced'   => __( 'Advanced', 'peki-fiken-integration-for-woocommerce' ),
            'logs'       => __( 'Logs', 'peki-fiken-integration-for-woocommerce' ),
            'support'    => __( 'Support', 'peki-fiken-integration-for-woocommerce' ),
        );

        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $tab_key => $tab_label ) {
            $active_class = ( $current_tab === $tab_key ) ? ' nav-tab-active' : '';
            $url = add_query_arg( array( 'page' => 'fiken_innstillinger', 'tab' => $tab_key ), admin_url( 'admin.php' ) );
            echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . esc_attr( $active_class ) . '">' . esc_html( $tab_label ) . '</a>';
        }
        echo '</h2>';
    }

    /**
     * Render connection tab content.
     */
    private function render_connection_tab( $vat_blocked = false ) {
        // Connection status data
        $refresh_tok = (string) get_option( 'pekifiken_refresh_token', '' );
        $employee_tok = (string) get_option( 'pekifiken_employee_token', '' );
        if ( $employee_tok === '' ) {
            $employee_tok = (string) get_option( 'fiken_employee_token', '' );
            if ( $employee_tok === '' ) {
                $employee_tok = (string) get_option( 'wfb_employee_token', '' );
            }
        }

        $is_connected  = ( $refresh_tok !== '' || $employee_tok !== '' );
        $connection_id = (string) get_option( 'pekifiken_connection_id', '' );

        // Start flex container for main content and side panel
        echo '<div class="fiken-sidewrap" style="display:flex;gap:20px;align-items:flex-start;">';
        
        // Main connection card
        echo '<div class="card" style="flex:1;max-width:880px;padding:20px;margin-top:20px;">';
        echo '<h2 style="margin-top:0;">' . esc_html__( 'Fiken Connection', 'peki-fiken-integration-for-woocommerce' ) . '</h2>';

        echo '<table class="form-table">';
        // VAT status row
        echo '<tr>';
        echo '<th scope="row"><label>' . esc_html__( 'VAT registration status', 'peki-fiken-integration-for-woocommerce' ) . '</label></th>';
        echo '<td>' . ( $vat_blocked ? '<span style="color:#b32d2e;">' . esc_html__( 'Not registered – integration disabled', 'peki-fiken-integration-for-woocommerce' ) . '</span>' : '<span class="dashicons dashicons-yes" aria-hidden="true" style="color:green;"></span> ' . esc_html__( 'Registered', 'peki-fiken-integration-for-woocommerce' ) ) . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label>' . esc_html__( 'Status', 'peki-fiken-integration-for-woocommerce' ) . '</label></th>';
        echo '<td>';
        if ( $is_connected ) {
            echo '<span class="dashicons dashicons-yes" style="color:green;" aria-hidden="true"></span> ';
            echo esc_html__( 'Connected', 'peki-fiken-integration-for-woocommerce' );
        } else {
            echo '<span class="dashicons dashicons-warning" style="color:orange;" aria-hidden="true"></span> ';
            echo esc_html__( 'Not connected', 'peki-fiken-integration-for-woocommerce' );
        }
        echo '</td>';
        echo '</tr>';

        if ( $connection_id !== '' ) {
            echo '<tr>';
            echo '<th scope="row"><label>' . esc_html__( 'Connection ID', 'peki-fiken-integration-for-woocommerce' ) . '</label></th>';
            echo '<td>' . esc_html( $connection_id ) . '</td>';
            echo '</tr>';
        }

        // Show masked token
        $token_to_show = $refresh_tok !== '' ? $refresh_tok : $employee_tok;
        if ( $token_to_show !== '' ) {
            $masked = $this->mask_token( $token_to_show );
            echo '<tr>';
            echo '<th scope="row"><label>' . esc_html__( 'Token', 'peki-fiken-integration-for-woocommerce' ) . '</label></th>';
            echo '<td>' . esc_html( $masked ) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Connection actions only (moved advanced settings to Advanced tab)

        // Connect button
        echo '<p class="submit" style="margin-top:10px;">';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:10px;">';
        wp_nonce_field( 'pekifiken_start_connect', 'pekifiken_start_connect_nonce' );
        echo '<input type="hidden" name="action" value="pekifiken_start_connect" />';
        echo '<button type="submit" class="button button-primary">';
        echo esc_html__( 'Connect to Fiken', 'peki-fiken-integration-for-woocommerce' );
        echo '</button>';
        echo '</form>';

        // Disconnect button
        echo '<form method="post" action="" style="display:inline-block;">';
        wp_nonce_field( 'pekifiken_disconnect', 'pekifiken_disconnect_nonce' );
        echo '<button type="submit" name="pekifiken_disconnect" class="button button-secondary"';
        echo $is_connected ? '' : ' disabled';
        echo ' onclick="return confirm(\'' . esc_js( __( 'This will remove local connection data. Continue?', 'peki-fiken-integration-for-woocommerce' ) ) . '\');">';
        echo esc_html__( 'Disconnect from Fiken', 'peki-fiken-integration-for-woocommerce' );
        echo '</button>';
        echo '</form>';
        echo '</p>';

        echo '</div>'; // .card (main connection card)
        
        // Render the side panel
        $this->render_connect_sidecard();
        
        echo '</div>'; // .fiken-sidewrap
    }

    /**
     * Render the connection side panel with setup tips.
     */
    private function render_connect_sidecard() {
        ?>
        <div class="fiken-sidecard">
            <style>
                .fiken-sidewrap {
                    display: flex;
                    gap: 20px;
                    align-items: flex-start;
                }
                .fiken-sidecard {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                    padding: 20px;
                    margin-top: 20px;
                    max-width: 420px;
                    flex-shrink: 0;
                }
                .fiken-sidecard h3 {
                    margin-top: 0;
                    margin-bottom: 15px;
                    font-size: 16px;
                    line-height: 1.4;
                }
                .fiken-sidecard ul {
                    margin: 10px 0;
                    padding-left: 20px;
                }
                .fiken-sidecard li {
                    margin-bottom: 8px;
                    line-height: 1.4;
                }
                .fiken-sidecard .notice {
                    margin: 15px 0 0 0;
                    padding: 12px;
                }
                @media (max-width: 782px) {
                    .fiken-sidewrap {
                        display: block;
                    }
                    .fiken-sidecard {
                        max-width: none;
                    }
                }
            </style>
            
            <h3><?php esc_html_e( 'Before you connect to Fiken', 'peki-fiken-integration-for-woocommerce' ); ?></h3>
            
          
            <ul>
                <li><?php esc_html_e( 'Enable the Fiken API add-on: Fiken → Foretak → Tillegstjenester → API (NOK 99/month).', 'peki-fiken-integration-for-woocommerce' ); ?></li>
                <li><?php esc_html_e( 'Issue your first invoice: If your account has never issued an invoice, create and send one to initialize the invoice counter.', 'peki-fiken-integration-for-woocommerce' ); ?></li>
            </ul>
            
            
            <div class="notice notice-info">
                <p><?php esc_html_e( 'Need help?', 'peki-fiken-integration-for-woocommerce' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=fiken_innstillinger&tab=support' ) ); ?>"><?php esc_html_e( 'Contact support', 'peki-fiken-integration-for-woocommerce' ); ?></a></p>
            </div>
        </div>
        <?php
    }

    /** Support tab content */
    private function render_support_tab() {
        echo '<div class="card" style="max-width:880px;padding:20px;margin-top:20px;">';
        echo '<h2 style="margin-top:0;">' . esc_html__( 'Support', 'peki-fiken-integration-for-woocommerce' ) . '</h2>';
        echo '<p class="description">' . esc_html__( 'For support, please contact us by email:', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        echo '<p><a href="mailto:petter@peki.no">petter@peki.no</a></p>';
        echo '</div>';
    }

    /**
     * Logs tab content.
     */
    private function render_logs_tab() {
        echo '<div class="card" style="max-width:880px;padding:20px;margin-top:20px;">';
        echo '<h2 style="margin-top:0;">' . esc_html__( 'Logs', 'peki-fiken-integration-for-woocommerce' ) . '</h2>';
        echo '<p class="description">' . esc_html__( 'Shows the latest error entries from the WooCommerce logger (source: peki-fiken).', 'peki-fiken-integration-for-woocommerce' ) . '</p>';

        $log = $this->get_recent_log_tail( 200 );

        if ( is_wp_error( $log ) ) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html( $log->get_error_message() ) . '</p></div>';
        } elseif ( empty( $log['lines'] ) ) {
            echo '<p>' . esc_html__( 'No error entries found yet. Trigger an export to generate logs.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        } else {
            if ( ! empty( $log['file'] ) ) {
                echo '<p><code>' . esc_html( basename( $log['file'] ) ) . '</code></p>';
            }
            echo '<textarea readonly class="widefat" rows="18" style="font-family:monospace;">' . esc_textarea( implode( "\n", $log['lines'] ) ) . '</textarea>';
            echo '<p class="description">' . esc_html__( 'Showing the last 200 error lines. Use WP-CLI or FTP to download the full log.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        }

        echo '</div>';
    }

    /** Advanced tab content */
    private function render_advanced_tab() {
        echo '<form method="post" action="">';
        wp_nonce_field( 'pekifiken_advanced', 'pekifiken_advanced_nonce' );

        echo '<div class="card" style="max-width:880px;padding:20px;margin-top:20px;">';
        echo '<h2 style="margin-top:0;">' . esc_html__( 'Advanced', 'peki-fiken-integration-for-woocommerce' ) . '</h2>';

		echo '<div class="pekifiken-advanced">';

		// -------- Section: Export rules --------
		echo '<h3 class="pekifiken-section-title" style="margin:14px 0 8px;">' . esc_html__( 'Export rules', 'peki-fiken-integration-for-woocommerce' ) . '</h3>';
		echo '<p class="description" style="margin-top:0;">' . esc_html__( 'Control how exports are posted in Fiken.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';

		echo '<table class="form-table">';
		$force_no_vat = (bool) get_option( 'fiken_force_no_vat', false );
		$auto_pdf     = (bool) get_option( 'fiken_auto_save_invoice_pdf', false );
		// Eligibility (fetch live status first to unlock immediately after upgrade; PDF also enforced server-side)
		$eligible = false;
		$plan = '';
		if ( class_exists( '\FikenBilag\Fiken_Status' ) ) {
			$live = \FikenBilag\Fiken_Status::fetch_cached_or_live( 120 );
			if ( is_array( $live ) ) {
				\FikenBilag\Fiken_Status::update_cache_from_array( $live, 120 );
				$plan = isset( $live['plan'] ) ? strtoupper( (string) $live['plan'] ) : '';
				$eligible = ( $plan === 'GROWTH' );
			}
		}
		if ( ! $eligible ) {
			$status = get_option( 'fiken_last_status', array() );
			$plan   = is_array( $status ) && isset( $status['plan'] ) ? strtoupper( (string) $status['plan'] ) : '';
			$eligible = ( $plan === 'GROWTH' );
		}
		$cash_behavior_enabled = (bool) get_option( 'fiken_cash_behavior_enabled', false );
		echo '<tr>';
		echo '<th scope="row"><label for="fiken_force_no_vat">' . esc_html__( 'Export of goods and services (VAT code 52)', 'peki-fiken-integration-for-woocommerce' ) . '</label></th>';
		echo '<td>';
		echo '<label><input class="pekifiken-checkbox-lg" type="checkbox" id="fiken_force_no_vat" name="fiken_force_no_vat" value="1" ' . checked( true, $force_no_vat, false ) . ' /> ';
		echo '<span class="description">' . esc_html__( 'Sets Fiken vatType=EXEMPT_IMPORT_EXPORT with VAT code 52 (export). Revenue typically posted to 3100. No VAT.', 'peki-fiken-integration-for-woocommerce' ) . '</span></label>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';

		// -------- Section: Growth features --------
		echo '<h3 class="pekifiken-section-title" style="margin:18px 0 8px;display:flex;align-items:center;gap:8px;">' . esc_html__( 'Growth features', 'peki-fiken-integration-for-woocommerce' )
			. ' <span class="pekifiken-badge -growth" title="' . esc_attr__( 'Requires Growth subscription. Upgrade in the customer portal.', 'peki-fiken-integration-for-woocommerce' ) . '">GROWTH</span>'
			. '</h3>';
		if ( ! $eligible ) {
			$portal = admin_url( 'admin.php?page=pekifiken_manage_subscription' );
			echo '<p class="description" style="margin-top:0;">' . sprintf(
				/* translators: %s: link to Manage Subscription page */
				esc_html__( 'These features require a Growth subscription. %s', 'peki-fiken-integration-for-woocommerce' ),
				'<a href="' . esc_url( $portal ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open customer portal', 'peki-fiken-integration-for-woocommerce' ) . '</a>'
			) . '</p>';
		}
		echo '<table class="form-table">';
		echo '<tr>';
		echo '<th scope="row"><label for="fiken_auto_save_invoice_pdf">' . esc_html__( 'Automatically save invoice PDFs to Media Library', 'peki-fiken-integration-for-woocommerce' ) . '</label> ';
		echo '<span class="pekifiken-badge -growth" title="' . esc_attr__( 'Requires Growth subscription. Upgrade in the customer portal.', 'peki-fiken-integration-for-woocommerce' ) . '">GROWTH</span>';
		echo '</th>';
		echo '<td>';
		echo '<label><input class="pekifiken-checkbox-lg" type="checkbox" id="fiken_auto_save_invoice_pdf" name="fiken_auto_save_invoice_pdf" value="1" ' . checked( true, $auto_pdf, false ) . ( $eligible ? '' : ' disabled' ) . ' ';
		if ( ! $eligible ) { echo ' title="' . esc_attr__( 'Requires Growth subscription. Upgrade in the customer portal.', 'peki-fiken-integration-for-woocommerce' ) . '"'; }
		echo ' /> ';
		echo '<span class="description">' . esc_html__( 'After a successful export, download the Fiken invoice PDF and attach it to the order.', 'peki-fiken-integration-for-woocommerce' ) . '</span></label>';
		echo '</td>';
		echo '</tr>';
        echo '<tr>';
		echo '<th scope="row"><label for="fiken_cash_behavior_enabled">' . esc_html__( 'Enable per-payment document type overrides', 'peki-fiken-integration-for-woocommerce' ) . '</label> ';
		echo '<span class="pekifiken-badge -growth" title="' . esc_attr__( 'Requires Growth subscription. Upgrade in the customer portal.', 'peki-fiken-integration-for-woocommerce' ) . '">GROWTH</span>';
		echo '</th>';
        echo '<td>';
		echo '<label><input class="pekifiken-checkbox-lg" type="checkbox" id="fiken_cash_behavior_enabled" name="fiken_cash_behavior_enabled" value="1" ' . checked( true, $cash_behavior_enabled, false ) . ( $eligible ? '' : ' disabled' ) . ' ';
		if ( ! $eligible ) { echo ' title="' . esc_attr__( 'Requires Growth subscription. Upgrade in the customer portal.', 'peki-fiken-integration-for-woocommerce' ) . '"'; }
		echo ' /></label>';
        echo '</td>';
        echo '</tr>';
		echo '</table>';
		echo '</div>'; // .pekifiken-advanced

		// Per-gateway document type behavior (Invoice vs CashSale)
        $this->render_cash_behavior();
        // Live-enable the dropdowns when master checkbox toggles (no save required). Honor Growth gating.
        echo '<script>(function(){var m=document.getElementById("fiken_cash_behavior_enabled");var eligible=' . ( $eligible ? 'true' : 'false' ) . ';function t(){var s=document.querySelectorAll(".pekifiken-cash-behavior-select");if(!s) return;for(var i=0;i<s.length;i++){s[i].disabled=!eligible||!m||!m.checked;}}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",t);}else{t();}if(m){m.addEventListener("change",t);}})();</script>';

        echo '<p class="submit" style="margin-top:10px;">';
        echo '<button type="submit" name="save_advanced" class="button button-primary">' . esc_html__( 'Save Settings', 'peki-fiken-integration-for-woocommerce' ) . '</button>';
        echo '</p>';

        echo '</div>';
        echo '</form>';
    }

    /**
     * Render gateways tab content.
     */
    private function render_gateways_tab( $vat_blocked = false ) {
        echo '<form method="post" action="">';
        wp_nonce_field( 'pekifiken_gateways', 'pekifiken_gateways_nonce' );

        echo '<div class="card" style="max-width:880px;padding:20px;margin-top:20px;">';
        echo '<h2 style="margin-top:0;">' . esc_html__( 'Bank Account Mapping', 'peki-fiken-integration-for-woocommerce' ) . '</h2>';

        if ( $vat_blocked ) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Company is not VAT registered. The integration is disabled and settings are read-only.', 'peki-fiken-integration-for-woocommerce' ) . '</p></div>';
            echo '<div style="position:relative;">';
            echo '<div style="position:absolute;inset:0;background:rgba(255,255,255,0.6);z-index:2;"></div>';
        }

        // Payment method to bank account mapping section
        $this->render_payment_bank_account_mapping();

        // Shipping income account section (removed)

        if ( ! $vat_blocked ) {
            echo '<p class="submit">';
            echo '<button type="submit" name="save_gateways" class="button button-primary">';
            echo esc_html__( 'Save Settings', 'peki-fiken-integration-for-woocommerce' );
            echo '</button>';
            echo '</p>';
        }

        echo '</div>'; // .card
        if ( $vat_blocked ) { echo '</div>'; }
        echo '</form>';
    }

    /**
     * Render payment method to bank account mapping section.
     */
    private function render_payment_bank_account_mapping() {
        echo '<p class="description">' . esc_html__( 'Select a registered bank account from Fiken to use for this payment method. This ensures voucher payments reconcile to the correct bank account in Fiken.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';

        // Get available payment gateways
        $available_gateways = array();
        if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
            $payment_gateways = WC()->payment_gateways();
            if ( $payment_gateways ) {
                $available_gateways = $payment_gateways->get_available_payment_gateways();
            }
        }

        // Get current mapping (bank accounts)
        $payment_account_map = get_option( 'pekifiken_bank_account_map', array() );

        // Get Fiken bank accounts
        $accounts = $this->get_fiken_bank_accounts();
        $accounts_by_code = array();
        if ( ! is_wp_error( $accounts ) && is_array( $accounts ) ) {
            foreach ( $accounts as $acc ) {
                $code = isset( $acc['code'] ) ? (string) $acc['code'] : '';
                if ( $code !== '' ) {
                    $accounts_by_code[ $code ] = $acc;
                }
            }
        }

        echo '<table class="form-table">';
        
        if ( empty( $available_gateways ) ) {
            echo '<tr>';
            echo '<td colspan="2">';
            echo '<div class="notice notice-warning inline"><p>';
            echo esc_html__( 'WooCommerce is not active or no payment gateways are available.', 'peki-fiken-integration-for-woocommerce' );
            echo '</p></div>';
            echo '</td>';
            echo '</tr>';
        } else {
            foreach ( $available_gateways as $gateway_id => $gateway ) {
                $gateway_title = $gateway->get_title();
                $current_account = $payment_account_map[ $gateway_id ] ?? '';

                echo '<tr>';
                echo '<th scope="row">';
                echo '<label for="payment_account_' . esc_attr( $gateway_id ) . '">';
                echo esc_html( $gateway_title ) . ' <code>(' . esc_html( $gateway_id ) . ')</code>';
                echo '</label>';
                echo '</th>';
                echo '<td>';
                
                if ( is_wp_error( $accounts ) ) {
                    // Fallback to text input if accounts fetch failed
                    echo '<input type="text" id="payment_account_' . esc_attr( $gateway_id ) . '" ';
                    echo 'name="pekifiken_bank_account_map[' . esc_attr( $gateway_id ) . ']" ';
                    echo 'value="' . esc_attr( $current_account ) . '" ';
                    echo 'placeholder="1920:10001" class="regular-text" />';
                    $err = $accounts->get_error_message();
                    if ( ! $err ) { $err = __( 'Unknown error.', 'peki-fiken-integration-for-woocommerce' ); }
                    echo '<p class="description">' . esc_html__( 'Enter account code manually (e.g., 1920:10001). Account list could not be fetched.', 'peki-fiken-integration-for-woocommerce' ) . ' ' . esc_html__( 'Reason:', 'peki-fiken-integration-for-woocommerce' ) . ' ' . esc_html( $err ) . '</p>';
                } else {
                    // Dropdown with accounts
                    echo '<select id="payment_account_' . esc_attr( $gateway_id ) . '" ';
                    echo 'name="pekifiken_bank_account_map[' . esc_attr( $gateway_id ) . ']" class="regular-text">';
                    echo '<option value="">' . esc_html__( 'Default (1920:10001)', 'peki-fiken-integration-for-woocommerce' ) . '</option>';
                    
                    foreach ( $accounts as $account ) {
                        $account_code   = isset( $account['code'] ) ? (string) $account['code'] : '';
                        $account_name   = isset( $account['name'] ) ? (string) $account['name'] : '';
                        $account_number = isset( $account['bankAccountNumber'] ) ? (string) $account['bankAccountNumber'] : ( isset( $account['accountNumber'] ) ? (string) $account['accountNumber'] : '' );
                        $foreign_service = isset( $account['foreignService'] ) ? (string) $account['foreignService'] : '';
                        $account_type   = isset( $account['type'] ) ? (string) $account['type'] : '';
                        $reconciled_balance = null;
                        if ( isset( $account['reconciledBalance'] ) && is_numeric( $account['reconciledBalance'] ) ) {
                            $reconciled_balance = (float) $account['reconciledBalance'] / 100.0; // convert øre to NOK
                        }
                        $reconciled_date = isset( $account['reconciledDate'] ) ? (string) $account['reconciledDate'] : '';

                        if ( $account_code ) {
                            $parts = array();
                            $parts[] = $account_code;
                            if ( $account_name !== '' ) { $parts[] = $account_name; }
                            if ( $account_number !== '' ) { $parts[] = $account_number; }
                            $service_or_type = $foreign_service !== '' ? $foreign_service : ( $account_type !== '' ? $account_type : 'normal' );
                            $parts[] = $service_or_type;
                            if ( $reconciled_balance !== null ) {
                                $rec_text = sprintf( /* translators: 1: reconciled amount, 2: date */ __( 'Reconciled: %1$.2f', 'peki-fiken-integration-for-woocommerce' ), $reconciled_balance );
                                if ( $reconciled_date !== '' ) {
                                    $rec_text .= ' ' . __( 'on', 'peki-fiken-integration-for-woocommerce' ) . ' ' . $reconciled_date;
                                }
                                $parts[] = $rec_text;
                            }

                            $display_name = implode( ' — ', array_map( 'wp_strip_all_tags', $parts ) );
                            echo '<option value="' . esc_attr( $account_code ) . '"' . selected( $current_account, $account_code, false ) . '>';
                            echo esc_html( $display_name );
                            echo '</option>';
                        }
                    }
                    echo '</select>';
                }
                
                echo '</td>';
                echo '</tr>';
            }
        }
        
        echo '</table>';
    }

    /**
     * Render cash behavior section.
     */
    private function render_cash_behavior() {
        echo '<h3 style="margin-top:24px;">' . esc_html__( 'Document Type per Payment Method', 'peki-fiken-integration-for-woocommerce' ) . '</h3>';
        echo '<p class="description">' . esc_html__( 'Choose whether each payment method should create a normal Fiken invoice (unpaid, with KID/PDF) or default to a cash sale/receipt. If not set, the default is cash sale (receipt).', 'peki-fiken-integration-for-woocommerce' ) . '</p>';

        // Get available payment gateways
        $available_gateways = array();
        if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
            $payment_gateways = WC()->payment_gateways();
            if ( $payment_gateways ) {
                $available_gateways = $payment_gateways->get_available_payment_gateways();
            }
        }

        // Current overrides (only stores explicit 'false' = force invoice)
        $cash_behavior_map = get_option( 'fiken_gateway_cash_map', array() );
        if ( ! is_array( $cash_behavior_map ) ) {
            $cash_behavior_map = array();
        }
        $enabled = (bool) get_option( 'fiken_cash_behavior_enabled', false );

        echo '<table class="form-table">';
        if ( empty( $available_gateways ) ) {
            echo '<tr>';
            echo '<td colspan="2">';
            echo '<div class="notice notice-warning inline"><p>';
            echo esc_html__( 'WooCommerce is not active or no payment gateways are available.', 'peki-fiken-integration-for-woocommerce' );
            echo '</p></div>';
            echo '</td>';
            echo '</tr>';
        } else {
            foreach ( $available_gateways as $gateway_id => $gateway ) {
                $gateway_title = $gateway->get_title();
                $forced_invoice = ( isset( $cash_behavior_map[ $gateway_id ] ) && $cash_behavior_map[ $gateway_id ] === 'false' );

                echo '<tr>';
                echo '<th scope="row">';
                echo '<label>' . esc_html( $gateway_title ) . ' <code>(' . esc_html( $gateway_id ) . ')</code></label>';
                echo '</th>';
                echo '<td>';

                // Dropdown: default (empty) or force invoice ('false')
                $select_id = 'cash_behavior_' . $gateway_id;
                echo '<select id="' . esc_attr( $select_id ) . '" name="cash_behavior_map[' . esc_attr( $gateway_id ) . ']" class="regular-text pekifiken-cash-behavior-select"' . disabled( $enabled, false, false ) . '>';
                echo '<option value="">' . esc_html__( 'Default (CashSale / Receipt)', 'peki-fiken-integration-for-woocommerce' ) . '</option>';
                echo '<option value="false"' . selected( true, $forced_invoice, false ) . '>' . esc_html__( 'Invoice (unpaid, with KID/PDF)', 'peki-fiken-integration-for-woocommerce' ) . '</option>';
                echo '</select>';

                echo '</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
    }

    /**
     * Render shipping income account section.
     */
    private function render_shipping_account() {
        echo '<h3>' . esc_html__( 'Shipping Income Account', 'peki-fiken-integration-for-woocommerce' ) . '</h3>';
        echo '<p class="description">' . esc_html__( 'Specify the income account used for shipping line items. Defaults to 3000 (VAT registered) or 3100 (not VAT registered).', 'peki-fiken-integration-for-woocommerce' ) . '</p>';

        $shipping_account = get_option( 'fiken_shipping_income_account', '' );
        $accounts = $this->get_fiken_accounts();

        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row">';
        echo '<label for="shipping_income_account">' . esc_html__( 'Shipping Account', 'peki-fiken-integration-for-woocommerce' ) . '</label>';
        echo '</th>';
        echo '<td>';
        
        if ( is_wp_error( $accounts ) ) {
            // Fallback to text input
            echo '<input type="text" id="shipping_income_account" name="shipping_income_account" ';
            echo 'value="' . esc_attr( $shipping_account ) . '" ';
            echo 'placeholder="3000" class="regular-text" />';
            echo '<p class="description">' . esc_html__( 'Enter account code manually (e.g., 3000). Account list could not be fetched.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        } else {
            // Dropdown with accounts
            echo '<select id="shipping_income_account" name="shipping_income_account" class="regular-text">';
            echo '<option value="">' . esc_html__( 'Default (auto-select based on VAT registration)', 'peki-fiken-integration-for-woocommerce' ) . '</option>';
            
            foreach ( $accounts as $account ) {
                $account_code = $account['code'] ?? '';
                $account_name = $account['name'] ?? '';
                if ( $account_code ) {
                    $display_name = $account_code . ( $account_name ? ' – ' . $account_name : '' );
                    echo '<option value="' . esc_attr( $account_code ) . '"' . selected( $shipping_account, $account_code, false ) . '>';
                    echo esc_html( $display_name );
                    echo '</option>';
                }
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__( 'Leave empty to use default account (3000 for VAT registered companies, 3100 for non-VAT registered).', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        }
        
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }

    /**
     * Read the latest WooCommerce log entries (peki-fiken) and return the tail of error lines.
     *
     * @param int $max_lines Max lines from end of file to scan.
     * @return array{file:string,lines:array<int,string>}|\WP_Error
     */
    private function get_recent_log_tail( int $max_lines = 200 ) {
        if ( ! defined( 'WC_LOG_DIR' ) ) {
            return new \WP_Error(
                'pekifiken_no_wc_logs',
                __( 'WooCommerce logging is not available on this site.', 'peki-fiken-integration-for-woocommerce' )
            );
        }

        $pattern = trailingslashit( WC_LOG_DIR ) . 'peki-fiken-*.log';
        $files   = glob( $pattern );
        if ( empty( $files ) ) {
            return array( 'file' => '', 'lines' => array() );
        }

        rsort( $files );
        $file = $files[0];

        $lines = @file( $file, FILE_IGNORE_NEW_LINES );
        if ( false === $lines ) {
            return new \WP_Error(
                'pekifiken_log_read',
                __( 'Unable to read the WooCommerce log file. Check file permissions.', 'peki-fiken-integration-for-woocommerce' )
            );
        }

        $slice  = array_slice( $lines, -absint( $max_lines ) );
        $errors = array();
        foreach ( $slice as $line ) {
            if ( stripos( $line, 'error' ) !== false ) {
                $errors[] = $line;
            }
        }

        return array(
            'file'  => $file,
            'lines' => $errors,
        );
    }

    /**
     * Get Fiken bank accounts via API.
     */
    private function get_fiken_bank_accounts() {
        $employee_token = (string) get_option( 'pekifiken_employee_token', '' );
        if ( $employee_token === '' ) {
            $employee_token = (string) get_option( 'fiken_employee_token', '' );
            if ( $employee_token === '' ) {
                $employee_token = (string) get_option( 'wfb_employee_token', '' );
            }
        }
        if ( $employee_token === '' ) {
            return new \WP_Error( 'no_token', __( 'No employee token available. Please connect to Fiken first.', 'peki-fiken-integration-for-woocommerce' ) );
        }

        require_once plugin_dir_path( __FILE__ ) . 'class-fiken-api.php';
        $api    = new Fiken_API( $employee_token );
        $result = $api->get_bank_accounts();

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( isset( $result['body'] ) ) {
            $body = $result['body'];
            if ( is_string( $body ) ) {
                $data = json_decode( $body, true );
            } elseif ( is_array( $body ) ) {
                $data = $body;
            } else {
                $data = null;
            }
            if ( is_array( $data ) ) {
                return $data; // full list from backend
            }
        }

        return new \WP_Error( 'invalid_response', __( 'Invalid response from bank accounts API.', 'peki-fiken-integration-for-woocommerce' ) );
    }

    /**
     * Check VAT status via backend and return array { vat_registered: bool, vatType?: string } or null on failure.
     */
    private function get_vat_status() {
        // Get employee token
        $employee_token = (string) get_option( 'pekifiken_employee_token', '' );
        if ( $employee_token === '' ) {
            $employee_token = (string) get_option( 'fiken_employee_token', '' );
            if ( $employee_token === '' ) {
                $employee_token = (string) get_option( 'wfb_employee_token', '' );
            }
        }
        if ( $employee_token === '' ) {
            return null;
        }

        require_once plugin_dir_path( __FILE__ ) . 'class-fiken-api.php';
        $api = new Fiken_API( $employee_token );
        $res = $api->check_vat();
        if ( is_wp_error( $res ) ) {
            return null;
        }
        if ( isset( $res['body'] ) && is_array( $res['body'] ) ) {
            return $res['body'];
        }
        return null;
    }

    /**
     * Save gateways settings.
     */
    private function save_gateways_settings( array $payment_account_map = array(), array $cash_behavior_map = array(), string $shipping_account = '' ) {
        // Save bank account mapping per gateway
        if ( is_array( $payment_account_map ) ) {
            $sanitized_map = array();
            foreach ( $payment_account_map as $gateway_id => $account ) {
                $gateway_id = sanitize_key( $gateway_id );
                $account = sanitize_text_field( $account );
                if ( $gateway_id && $account ) {
                    $sanitized_map[ $gateway_id ] = $account;
                }
            }
            update_option( 'pekifiken_bank_account_map', $sanitized_map );
        }

        // Save cash behavior mapping (only store overrides).
        // IMPORTANT: Do not wipe existing overrides when this tab is saved without cash fields present.
        if ( is_array( $cash_behavior_map ) ) {
            $sanitized_cash_map = array();
            foreach ( $cash_behavior_map as $gateway_id => $behavior ) {
                $gateway_id = sanitize_key( $gateway_id );
                $behavior = sanitize_text_field( $behavior );
                if ( $gateway_id && $behavior === 'false' ) {
                    // Only store explicit overrides to false
                    $sanitized_cash_map[ $gateway_id ] = 'false';
                }
            }
            if ( ! empty( $sanitized_cash_map ) ) {
                update_option( 'fiken_gateway_cash_map', $sanitized_cash_map );
            }
            // If empty array was posted (or no field existed), keep current option intact.
        }

        // Save shipping income account
        update_option( 'fiken_shipping_income_account', sanitize_text_field( $shipping_account ) );
    }

    /** Masker token for visning */
    private function mask_token( string $token ) : string {
        $len = strlen( $token );
        if ( $len <= 8 ) {
            return str_repeat( '•', max( 0, $len - 2 ) ) . substr( $token, -2 );
        }
        return substr( $token, 0, 4 ) . str_repeat( '•', $len - 8 ) . substr( $token, -4 );
    }

    /**
     * Disconnect:
     * 1) Kaller serverens unlink.php (HMAC over "shop|ts|nonce") for å fjerne KUN Fiken-tilknytning i DB.
     * 2) Sletter lokale nøkler for Fiken (refresh/employee/connection/company_slug).
     * 3) Setter lokal abonnementsstate til 'pending' (abonnement i DB forblir urørt).
     */
    private function do_disconnect() : void {
        $shop   = site_url();

        // Finn hemmelighet å signere med (foretrekk refresh_token)
        $secret = (string) get_option( 'pekifiken_refresh_token', '' );
        if ( $secret === '' ) {
            $secret = (string) get_option( 'pekifiken_employee_token', '' );
            if ( $secret === '' ) {
                $secret = (string) get_option( 'fiken_employee_token', '' );
                if ( $secret === '' ) {
                    $secret = (string) get_option( 'wfb_employee_token', '' );
                }
            }
        }

        // 1) Kall server for å unlinke Fiken-koblingen (best effort)
        if ( $secret !== '' ) {
            $ts    = (string) time();
            $nonce = wp_generate_password( 16, false, false ); // [A-Za-z0-9_-] iht. server-validering

            $payload = $shop . '|' . $ts . '|' . $nonce;
            $raw_sig = hash_hmac( 'sha256', $payload, $secret, true );
            $sig     = rtrim( strtr( base64_encode( $raw_sig ), '+/', '-_' ), '=' );

            $url = add_query_arg(
                array(
                    'shop'  => $shop,
                    'ts'    => $ts,
                    'nonce' => $nonce,
                    'sig'   => $sig,
                    'alg'   => 'HMAC-SHA256',
                    'v'     => '2',
                ),
                'https://peki.no/fiken/unlink.php'
            );

            // Vi ignorerer ev. feil her for ikke å låse UI (best effort)
            wp_remote_get( $url, array(
                'timeout'    => 10,
                'user-agent' => 'PekiFikenPlugin/1.0; ' . $shop,
            ) );
        }

        // 2) Slett lokale Fiken-nøkler (MEN ikke rør Stripe/abonnement)
        delete_option( 'pekifiken_refresh_token' );
        delete_option( 'pekifiken_employee_token' );
        delete_option( 'fiken_employee_token' );
        delete_option( 'wfb_employee_token' );

        delete_option( 'pekifiken_connection_id' );
        delete_option( 'fiken_company_slug' );
        delete_option( 'wfb_company_slug' );
        delete_option( 'pekifiken_company_slug' );

        // 3) Sett lokal state til pending (så meny viser "Upgrade" inntil ny tilkobling)
        update_option( 'pekifiken_subscription_state', 'pending', true );
        update_option( 'pekifiken_has_active_subscription', '0', true );
    }
}

endif;
