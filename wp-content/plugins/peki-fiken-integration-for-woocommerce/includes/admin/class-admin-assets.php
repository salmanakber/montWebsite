<?php
namespace FikenBilag\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin_Assets {
    public function enqueue_admin_assets( $hook ) {
        // Detect our admin pages
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $our_pages = [ 'fiken_innstillinger', 'pekifiken_manage_subscription' ];
        $ver       = defined( 'PEKIFIKEN_VERSION' ) ? PEKIFIKEN_VERSION : '1.0.0';
        // Cache-bust admin.css when changed
        if ( defined( 'PEKIFIKEN_FILE' ) ) {
            $css_path = plugin_dir_path( PEKIFIKEN_FILE ) . 'assets/css/admin.css';
            if ( is_file( $css_path ) ) {
                $mtime = (int) @filemtime( $css_path );
                if ( $mtime > 0 ) { $ver = $ver . '-' . $mtime; }
            }
        }

        // Optional: also allow WooCommerce admin screens (safe to keep; remove if not needed)
        $is_wc_screen = false;
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( $screen && ! empty( $screen->id ) && is_string( $screen->id ) ) {
                $id          = $screen->id;
                $is_wc_screen = ( strpos( $id, 'woocommerce' ) !== false )
                    || $id === 'edit-shop_order'
                    || $id === 'woocommerce_page_wc-orders';
            }
        }

        // Only load assets where they are useful
        if ( ! in_array( $page, $our_pages, true ) && ! $is_wc_screen ) {
            return;
        }

        // CSS
        if ( defined( 'PEKIFIKEN_FILE' ) ) {
            $style_url = plugins_url( 'assets/css/admin.css', PEKIFIKEN_FILE );
            wp_enqueue_style( 'pekifiken_admin_css', $style_url, [], $ver );
        }

        // Notice dismiss JS
        if ( defined( 'PEKIFIKEN_FILE' ) ) {
            $notice_js_url = plugins_url( 'assets/js/notice-dismiss.js', PEKIFIKEN_FILE );
            wp_enqueue_script( 'pekifiken-notice-dismiss', $notice_js_url, [ 'jquery' ], $ver, true );
            wp_localize_script( 'pekifiken-notice-dismiss', 'fikenData', [
                'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
                'dismissAction'        => 'pekifiken_dismiss_notice',
                'security'             => wp_create_nonce( 'pekifiken_dismiss_notice' ),
                'legacyDismissAction'  => 'pwtf_dismiss_notice',
                'legacySecurity'       => wp_create_nonce( 'pwtf_dismiss_notice' ),
                'debug'                => defined( 'WP_DEBUG' ) && WP_DEBUG,
            ] );
        }

        // Live status updater (polls AJAX and paints small status fields if present)
        $enable_live = apply_filters( 'pekifiken_enable_live_status', true );
        if ( $enable_live ) {
            $nonce       = wp_create_nonce( 'pekifiken_refresh_status' );
            $interval_s  = defined( 'PEKIFIKEN_STATE_THROTTLE_SEC' )
                ? max( 5, (int) PEKIFIKEN_STATE_THROTTLE_SEC )
                : 600; // 10 minutes
            $interval_ms = (int) $interval_s * 1000;

            // Register a "virtual" handle (src=false) so inline JS prints cleanly without empty <script src="">
            wp_register_script( 'pekifiken-admin-inline', false, [ 'jquery' ], $ver, true );
            wp_enqueue_script( 'pekifiken-admin-inline' );

            $js = "(function(w,d,$){
                var iv = {$interval_ms};
                function paint(res){
                    if(!res || !res.success || !res.data) return;
                    var data = res.data;
                    var st = d.getElementById('pekifiken-substate');
                    if(st){
                        var s = (data.state||'pending');
                        st.textContent = s.charAt(0).toUpperCase()+s.slice(1);
                    }
                    var ts = d.getElementById('pekifiken-last-sync');
                    if(ts && data.time){ ts.textContent = data.time; }
                    var httpEl = d.getElementById('pekifiken-last-http');
                    if(httpEl && typeof data.http !== 'undefined' && data.http !== null){ httpEl.textContent = data.http; }
                    var errEl = d.getElementById('pekifiken-last-error');
                    if(errEl){ errEl.textContent = data.error ? data.error : ''; }
                }
                function tick(){
                    $.post(ajaxurl, { action:'pekifiken_refresh_status', nonce:'{$nonce}' })
                     .done(function(res){ paint(res); })
                     .fail(function(xhr){
                         var httpEl = d.getElementById('pekifiken-last-http');
                         if(httpEl){ httpEl.textContent = xhr.status; }
                         var errEl = d.getElementById('pekifiken-last-error');
                         if(errEl){ errEl.textContent = 'AJAX ' + xhr.status; }
                     });
                }
                $(function(){ tick(); setInterval(tick, iv); });
            })(window, document, jQuery);";

            wp_add_inline_script( 'pekifiken-admin-inline', $js );
        }
    }
}
