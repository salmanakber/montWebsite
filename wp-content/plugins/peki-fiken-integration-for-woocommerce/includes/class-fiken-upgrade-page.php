<?php
/**
 * Upgrade / Subscription admin screen.
 *
 * File: includes/class-fiken-upgrade-page.php
 */

namespace FikenBilag;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( '\FikenBilag\Fiken_Upgrade_Page', false ) ) :

class Fiken_Upgrade_Page {

    /** External service endpoints (document these in readme.txt). */
    private const STATUS_ENDPOINT   = 'https://peki.no/stripe-connect/fiken/status.php';
    private const CHECKOUT_STARTER  = 'https://peki.no/stripe-connect/fiken/checkout-fiken-starter.php';
    private const CHECKOUT_GROWTH   = 'https://peki.no/stripe-connect/fiken/checkout-fiken-growth.php';
    private const CHECKOUT_PRO      = 'https://peki.no/stripe-connect/fiken/checkout-fiken-pro.php';
    private const PORTAL_ENDPOINT   = 'https://peki.no/stripe-connect/fiken/customer-portal.php';

    /** Local default free quota (server may override via remote). */
    private const FREE_QUOTA_DEFAULT = 15;

    /** Get first non-empty option value from a list of keys. */
    private static function get_option_fallback( array $keys, $default = '' ) {
        foreach ( $keys as $k ) {
            $v = get_option( $k, '' );
            if ( '' !== $v && null !== $v ) {
                return $v;
            }
        }
        return $default;
    }

    /** Extract int from array using multiple possible keys. */
    private static function array_int( array $src, array $keys, ?int $fallback = null ): ?int {
        foreach ( $keys as $k ) {
            if ( array_key_exists( $k, $src ) && $src[ $k ] !== null && $src[ $k ] !== '' ) {
                return (int) $src[ $k ];
            }
        }
        return $fallback;
    }

    /**
     * Render the page.
     */
    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            echo '<div class="notice notice-error"><p>' .
                esc_html__( 'You do not have permission to access these settings.', 'peki-fiken-integration-for-woocommerce' ) .
            '</p></div>';
            return;
        }

        // Read options (prefer fiken_* keys, fallback to legacy wfb_* keys).
        $status        = self::get_option_fallback( array( 'fiken_has_paid_plan', 'wfb_has_paid_plan' ), '' );
        $renewal_raw   = self::get_option_fallback( array( 'fiken_next_renewal', 'wfb_next_renewal' ), '' );
        $connection_id = self::get_option_fallback( array( 'fiken_connection_id', 'wfb_connection_id' ), '' );
        $company_slug  = self::get_option_fallback( array( 'pekifiken_company_slug', 'fiken_company_slug', 'wfb_company_slug' ), '' );

        // Defaults before server status.
        $transfers_used = 0;
        $max_transfers  = self::FREE_QUOTA_DEFAULT;

        // --- Build status URL (manual; avoid double-encoding pitfalls) ---
        $site_home = home_url();
        $shop_qs   = rawurlencode( $site_home );

        // Send both "shop" and "shop_url" for maximum compatibility with server handlers.
        $status_url = self::STATUS_ENDPOINT . '?shop=' . $shop_qs . '&shop_url=' . $shop_qs . '&v=3';
        if ( $connection_id !== '' ) {
            $status_url .= '&connection_id=' . rawurlencode( (string) $connection_id );
        }

        // --- Fetch status ---
        $last_error = '';
        $body       = array();

        $res = wp_remote_get( $status_url, array( 'timeout' => 10 ) );
        if ( is_wp_error( $res ) ) {
            $last_error = $res->get_error_message();
        } else {
            $json   = wp_remote_retrieve_body( $res );
            $parsed = json_decode( $json, true );

            if ( is_array( $parsed ) ) {
                // Handle server errors keyed as 'error' or 'feil'
                if ( isset( $parsed['error'] ) || isset( $parsed['feil'] ) ) {
                    $last_error = (string) ( $parsed['error'] ?? $parsed['feil'] );
                } else {
                    $body = $parsed;

                    // Normalize status
                    if ( isset( $body['status'] ) && $body['status'] !== '' ) {
                        $status = strtolower( (string) $body['status'] ); // 'active' | 'cancelled' | 'none' | ''
                    }

                    // Prefer 'renewal', fallback to 'next_renewal'
                    if ( ! empty( $body['renewal'] ) ) {
                        $renewal_raw = (string) $body['renewal'];
                    } elseif ( ! empty( $body['next_renewal'] ) ) {
                        $renewal_raw = (string) $body['next_renewal'];
                    }

                    // --- TRUST remote usage if provided (robust keys) ---
                    $remote_used = self::array_int( $body, array( 'bilag_used', 'used', 'transfers_used', 'free_used' ) );
                    if ( $remote_used !== null ) {
                        $transfers_used = $remote_used;
                    }

                    // Remote max/free quota (robust keys)
                    $remote_max = self::array_int( $body, array( 'max_free', 'free_quota', 'quota', 'max_transfers', 'free_limit' ) );
                    if ( $remote_max !== null ) {
                        $max_transfers = max( 1, $remote_max );
                    }
                }
            } else {
                $last_error = 'Invalid JSON from status endpoint.';
            }
        }

        // If still no remote usage at all, fall back to local option.
        if ( $transfers_used === 0 && empty( $body ) ) {
            $transfers_used = (int) self::get_option_fallback( array( 'fiken_bilag_teller', 'wfb_bilag_teller' ), 0 );
        }

        // Calculations.
        $value_attr = max( 0, min( $max_transfers, (int) $transfers_used ) );

        // Checkout/portal links (manual build; include robust identifiers)
        $checkout_starter = self::CHECKOUT_STARTER . '?shop=' . $shop_qs . '&shop_url=' . $shop_qs . '&v=3';
        $checkout_growth  = self::CHECKOUT_GROWTH  . '?shop=' . $shop_qs . '&shop_url=' . $shop_qs . '&v=3';
        $checkout_pro     = self::CHECKOUT_PRO     . '?shop=' . $shop_qs . '&shop_url=' . $shop_qs . '&v=3';
        // Include connection_id and shop_url for robust portal routing
        $portal_url       = self::PORTAL_ENDPOINT  . '?shop=' . $shop_qs . '&shop_url=' . $shop_qs . '&v=3';
        if ( $company_slug !== '' ) {
            $portal_url       .= '&company_slug=' . rawurlencode( (string) $company_slug );
            $checkout_starter .= '&company_slug=' . rawurlencode( (string) $company_slug );
            $checkout_growth  .= '&company_slug=' . rawurlencode( (string) $company_slug );
            $checkout_pro     .= '&company_slug=' . rawurlencode( (string) $company_slug );
        }
        if ( $connection_id !== '' ) {
            $portal_url      .= '&connection_id=' . rawurlencode( (string) $connection_id );
            $checkout_starter .= '&connection_id=' . rawurlencode( (string) $connection_id ) . '&intent=upgrade';
            $checkout_growth  .= '&connection_id=' . rawurlencode( (string) $connection_id ) . '&intent=upgrade';
            $checkout_pro     .= '&connection_id=' . rawurlencode( (string) $connection_id ) . '&intent=upgrade';
        }

        // Renewal text (not shown anymore, but kept prepared if needed later).
        $renewal_text = '';
        if ( ! empty( $renewal_raw ) ) {
            $ts = strtotime( $renewal_raw );
            if ( $ts ) {
                $renewal_text = sprintf(
                    // translators: %s: renewal date, formatted using site date settings.
                    esc_html__( 'Active until %s', 'peki-fiken-integration-for-woocommerce' ),
                    esc_html( date_i18n( get_option( 'date_format' ), $ts ) )
                );
            }
        }

        echo '<div class="wrap">';

        // === TITLE (exact as requested) ===
        echo '<h1>' . esc_html__( 'Subscription & Usage', 'peki-fiken-integration-for-woocommerce' ) . '</h1>';

        // Determine if the site currently "has subscription".
        $has_subscription = ( 'active' === $status || 'cancelled' === $status );

        $plan = '';
        if ( isset( $body['plan'] ) && is_string( $body['plan'] ) ) {
            $plan = strtoupper( (string) $body['plan'] );
        }
        $reset = isset( $body['reset'] ) ? (string) $body['reset'] : '';
        $cancel_at_period_end = ! empty( $body['cancel_at_period_end'] );

        // === HERO ===
        echo '<div class="pf-hero">';
        if ( ! $has_subscription ) {
            echo '<h2>' . esc_html__( 'Free plan — 15 free transfers/month', 'peki-fiken-integration-for-woocommerce' ) . '</h2>';
            echo '<p>' . esc_html__( 'Upgrade to unlock higher monthly limits and priority support.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        } else {
            // translators: %s: current subscription plan name.
            echo '<h2>' . sprintf( esc_html__( 'You are on the %s plan', 'peki-fiken-integration-for-woocommerce' ), esc_html( $plan ?: 'PRO' ) ) . '</h2>';
            if ( $cancel_at_period_end ) {
                echo '<p>' . esc_html__( 'Your subscription will end at period end. You can renew or change plan anytime.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
            } else {
                echo '<p>' . esc_html__( 'Manage your subscription, payment method and invoices in the customer portal.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
            }
        }
        echo '</div>';

        // === AUTO-UPGRADE TOGGLE (simple form posts to server endpoint) ===
        // Show toggle only when a subscription exists
        if ( $has_subscription ) {
            $auto_enabled = isset( $body['auto_upgrade_enabled'] ) ? (int) $body['auto_upgrade_enabled'] : 0;
            $post_url = admin_url( 'admin-post.php' );
            $nonce    = wp_create_nonce( 'pekifiken_toggle_auto_upgrade' );
            echo '<div class="pf-setting" id="pf-auto-upgrade" data-url="' . esc_attr( $post_url ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
            echo '<div class="pf-setting__text">';
            echo '<strong>' . esc_html__( 'Auto-upgrade to prevent export stops', 'peki-fiken-integration-for-woocommerce' ) . '</strong>';
            echo '<span class="pf-small">' . esc_html__( 'When enabled, we will automatically upgrade your plan if you hit the monthly limit, so your transfers never stop.', 'peki-fiken-integration-for-woocommerce' ) . '</span>';
            echo '</div>';
            echo '<label class="pf-switch">';
            echo '<input type="checkbox" id="pf-auto-upgrade-toggle" ' . checked( 1, $auto_enabled, false ) . ' />';
            echo '<span class="pf-switch__slider"></span>';
            echo '</label>';
            echo '</div>';
            // Inline JS to auto-save on toggle
            echo '<script>(function(){
                var box=document.getElementById("pf-auto-upgrade-toggle");
                var wrap=document.getElementById("pf-auto-upgrade");
                if(!box||!wrap) return;
                var url=wrap.getAttribute("data-url");
                var nonce=wrap.getAttribute("data-nonce");
                box.addEventListener("change",function(){
                    var fd=new FormData();
                    fd.append("action","pekifiken_toggle_auto_upgrade");
                    fd.append("pekifiken_toggle_auto_upgrade_nonce",nonce);
                    if(box.checked){ fd.append("enabled","1"); }
                    fetch(url,{method:"POST",credentials:"same-origin",body:fd});
                });
            })();</script>';
        }

        // === PLAN SECTION (fully rethought) ===
        echo '<h2 class="title" style="display:flex;align-items:center;gap:8px;">' . esc_html__( 'Plans', 'peki-fiken-integration-for-woocommerce' );
        $badge_label = $has_subscription ? ( $plan ?: 'PRO' ) : 'Free';
        $badge_class = $has_subscription ? 'pekifiken-badge -active' : 'pekifiken-badge -free';
        if ( $has_subscription && $cancel_at_period_end ) { $badge_class = 'pekifiken-badge -cancel'; $badge_label = 'Cancels at period end'; }
        echo ' <span class="' . esc_attr( $badge_class ) . '">' . esc_html( strtoupper( $badge_label ) ) . '</span>';
        echo '</h2>';

        // Define plans with rank for upgrade/downgrade logic
        $plan_levels = array( 'STARTER' => 1, 'GROWTH' => 2, 'PRO' => 3 );
        $current_level = isset( $plan_levels[ $plan ] ) ? (int) $plan_levels[ $plan ] : 0;
        $plans = array(
            array( 'key' => 'STARTER', 'name' => 'Starter', 'price' => '119 NOK / month (ex. VAT)', 'limit' => 100,  'url' => $checkout_starter ),
            array( 'key' => 'GROWTH',  'name' => 'Growth',  'price' => '319 NOK / month (ex. VAT)', 'limit' => 1000, 'url' => $checkout_growth  ),
            array( 'key' => 'PRO',     'name' => 'Pro',     'price' => '639 NOK / month (ex. VAT)', 'limit' => 5000, 'url' => $checkout_pro     ),
        );

        echo '<div class="pf-grid">';
        foreach ( $plans as $pl ) {
            $target_level = isset( $plan_levels[ $pl['key'] ] ) ? (int) $plan_levels[ $pl['key'] ] : 0;
            $is_current   = $has_subscription && ( $pl['key'] === $plan );
            $is_downgrade = $has_subscription && ( $target_level < $current_level );

            $card_class = 'pf-card';
            if ( $is_current ) { $card_class .= ' pf-card--current'; }
            if ( $is_downgrade ) { $card_class .= ' pf-card--disabled'; }

            echo '<div class="' . esc_attr( $card_class ) . '">';
            echo '<h3>' . esc_html( $pl['name'] ) . '</h3>';
            echo '<div class="pf-price">' . esc_html( $pl['price'] ) . '</div>';
            // translators: %s: monthly transfer limit for the plan.
            echo '<div class="pf-meta">' . sprintf( esc_html__( 'Up to %s transfers / month', 'peki-fiken-integration-for-woocommerce' ), esc_html( number_format_i18n( $pl['limit'] ) ) ) . '</div>';
            // Plan benefits
            echo '<ul class="pf-list" style="margin:8px 0 14px 18px;list-style:disc;">';
            if ( $pl['key'] === 'STARTER' ) {
                echo '<li>' . esc_html__( 'Everything in Free', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
                echo '<li>' . esc_html__( 'Higher monthly transfer limit', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
                echo '<li>' . esc_html__( 'Email support', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
            } elseif ( $pl['key'] === 'GROWTH' ) {
                echo '<li>' . esc_html__( 'Everything in Starter', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
                echo '<li>' . esc_html__( 'Automatically save invoice PDFs to Media Library', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
                echo '<li>' . esc_html__( 'Per-payment document type overrides (Invoice vs CashSale)', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
                echo '<li>' . esc_html__( 'Higher monthly transfer limit', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
            } elseif ( $pl['key'] === 'PRO' ) {
                echo '<li>' . esc_html__( 'Everything in Growth', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
                echo '<li>' . esc_html__( 'Highest monthly transfer limit', 'peki-fiken-integration-for-woocommerce' ) . '</li>';
            }
            echo '</ul>';

            echo '<div class="pf-cta">';
            if ( $is_current ) {
                echo '<button class="button" disabled>' . esc_html__( 'Current plan', 'peki-fiken-integration-for-woocommerce' ) . '</button>';
            } elseif ( $is_downgrade ) {
                echo '<button class="button" disabled title="' . esc_attr__( 'Downgrades are disabled. Use the portal to change your plan.', 'peki-fiken-integration-for-woocommerce' ) . '">' . esc_html__( 'Not available', 'peki-fiken-integration-for-woocommerce' ) . '</button>';
            } else {
                // If there is already a subscription, route to portal to avoid creating duplicates.
                if ( $has_subscription ) {
                    $target = $portal_url . '&action=upgrade&target=' . rawurlencode( (string) $pl['key'] );
                    echo '<a class="button button-primary" href="' . esc_url( $target ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Choose plan', 'peki-fiken-integration-for-woocommerce' ) . '</a>';
                } else {
                    echo '<a class="button button-primary" href="' . esc_url( $pl['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Choose plan', 'peki-fiken-integration-for-woocommerce' ) . '</a>';
                }
            }
            echo '</div>';

            echo '</div>';
        }
        echo '</div>';

        // Portal CTA for active/cancelled
        if ( $has_subscription ) {
            echo '<p class="pf-cta"><a class="button button-primary" href="' . esc_url( $portal_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open customer portal', 'peki-fiken-integration-for-woocommerce' ) . '</a> ';
            if ( ! empty( $renewal_text ) ) { echo '<span class="pf-small">' . esc_html( $renewal_text ) . '</span>'; }
            echo '</p>';
        } else {
            echo '<p class="pf-small"><a class="button-link" href="/wp-admin/admin.php?page=fiken_innstillinger&tab=support" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Need help? Contact support', 'peki-fiken-integration-for-woocommerce' ) . '</a></p>';
        }

        // === USAGE CARD (both states) ===
        $used  = isset( $body['used'] ) ? (int) $body['used'] : (int) $transfers_used;
        $limit = isset( $body['limit'] ) ? (int) $body['limit'] : (int) $max_transfers;
        $val   = max( 0, min( $limit, $used ) );

        echo '<h2 class="title">' . esc_html__( 'Usage', 'peki-fiken-integration-for-woocommerce' ) . '</h2>';
        echo '<div class="card">';
        echo '<div class="pf-usage-row">';
        echo '<div>' . esc_html__( 'Monthly usage', 'peki-fiken-integration-for-woocommerce' ) . '</div>';
        echo '<div class="pf-small">' . esc_html( number_format_i18n( $used ) ) . ' / ' . esc_html( number_format_i18n( $limit ) ) . '</div>';
        echo '</div>';
        $pct = $limit > 0 ? min(100, max(0, (int) round(($used / $limit) * 100))) : 0;
        echo '<div class="pf-progress"><div class="pf-progress__bar" style="width:' . esc_attr( (string) $pct ) . '%"></div></div>';
        if ( $reset ) {
            // translators: %s: date when the monthly quota resets.
            echo '<p class="description">' . sprintf( esc_html__( 'Resets on %s', 'peki-fiken-integration-for-woocommerce' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $reset ) ) ) ) . '</p>';
        }
        echo '</div>';

        // Subtle cancel notice when relevant
        if ( $has_subscription && $cancel_at_period_end ) {
            echo '<p class="description">' . esc_html__( 'Your subscription is set to cancel at the end of the current period.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        }

        // Subtle notes
        echo '<p class="description">' . esc_html__( 'Payments are securely handled by Stripe.', 'peki-fiken-integration-for-woocommerce' ) . '</p>';
        echo '<p class="description">' .
            esc_html__( 'To display your usage and plan status, this screen may contact the integration server using your site URL and connection ID. No personal customer data is transmitted.', 'peki-fiken-integration-for-woocommerce' ) .
        '</p>';

        echo '</div>'; // .wrap
    }
}

endif;
