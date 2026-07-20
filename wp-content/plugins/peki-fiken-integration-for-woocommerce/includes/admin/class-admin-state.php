<?php
namespace FikenBilag\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin_State {

    public function maybe_refresh_subscription_state() {
        $ttl = defined( 'PEKIFIKEN_STATE_THROTTLE_SEC' ) ? (int) PEKIFIKEN_STATE_THROTTLE_SEC : ( 10 * MINUTE_IN_SECONDS );
        if ( get_transient( 'pekifiken_state_throttle' ) ) return;
        set_transient( 'pekifiken_state_throttle', 1, max( 5, $ttl ) );
        self::refresh_subscription_state_from_server();
    }

    public static function refresh_subscription_state_from_server() {
        $shop = site_url();

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

        if ( $secret === '' ) {
            update_option( 'pekifiken_subscription_state', 'pending', true );
            update_option( 'pekifiken_sync_last', wp_json_encode([
                'time' => current_time( 'mysql' ),
                'note' => 'No token available; state=pending',
            ]), true );
            return;
        }

        $ts    = (string) time();
        $nonce = wp_generate_password( 16, false, false );
        $payload = $shop . '|' . $ts . '|' . $nonce;

        $raw_sig = hash_hmac( 'sha256', $payload, $secret, true );
        $sig     = rtrim( strtr( base64_encode( $raw_sig ), '+/', '-_' ), '=' );

        $url = add_query_arg([
            'shop'  => $shop,
            'ts'    => $ts,
            'nonce' => $nonce,
            'sig'   => $sig,
        ], 'https://peki.no/fiken/status.php' );

        $resp = wp_remote_get( $url, [
            'timeout'    => 12,
            'user-agent' => 'PekiFikenPlugin/1.0; ' . $shop,
        ] );

        $info = [ 'time' => current_time( 'mysql' ) ];
        if ( is_wp_error( $resp ) ) {
            $info['error'] = $resp->get_error_message();
            update_option( 'pekifiken_sync_last', wp_json_encode( $info ), true );
            if ( ! get_option( 'pekifiken_subscription_state', false ) ) {
                update_option( 'pekifiken_subscription_state', 'pending', true );
            }
            return;
        }

        $code     = (int) wp_remote_retrieve_response_code( $resp );
        $body_raw = (string) wp_remote_retrieve_body( $resp );
        $data     = json_decode( $body_raw, true );

        $info['http']  = $code;
        $info['bytes'] = strlen( $body_raw );
        $info['peek']  = substr( $body_raw, 0, 160 );

        if ( $code !== 200 || ! is_array( $data ) ) {
            $info['error'] = 'Non-200 or invalid JSON';
            update_option( 'pekifiken_sync_last', wp_json_encode( $info ), true );
            if ( ! get_option( 'pekifiken_subscription_state', false ) ) {
                update_option( 'pekifiken_subscription_state', 'pending', true );
            }
            return;
        }

        $state = 'pending';
        if ( array_key_exists( 'is_pro', $data ) ) {
            $state = ! empty( $data['is_pro'] ) ? 'active' : 'pending';
        } elseif ( isset( $data['state'] ) ) {
            $sv = strtolower( trim( (string) $data['state'] ) );
            $state = ( $sv === 'active' ) ? 'active' : 'pending';
        } elseif ( isset( $data['status'] ) ) {
            $sv = strtolower( trim( (string) $data['status'] ) );
            $state = ( $sv === 'active' ) ? 'active' : 'pending';
        }

        update_option( 'pekifiken_subscription_state', $state, true );
        update_option( 'pekifiken_has_active_subscription', $state === 'active' ? '1' : '0', true );
        $info['state'] = $state;
        update_option( 'pekifiken_sync_last', wp_json_encode( $info ), true );

        // v3 fields: used/limit/remaining/reset/plan/cancel flag/next_renewal
        if ( isset( $data['used'] ) ) {
            update_option( 'pekifiken_transfers_used', (int) $data['used'], true );
        } elseif ( isset( $data['transfers_used'] ) ) {
            update_option( 'pekifiken_transfers_used', (int) $data['transfers_used'], true );
        } elseif ( isset( $data['quota_used'] ) ) {
            update_option( 'pekifiken_transfers_used', (int) $data['quota_used'], true );
        }

        if ( isset( $data['limit'] ) ) {
            update_option( 'pekifiken_transfers_limit', (int) $data['limit'], true );
        } elseif ( isset( $data['max_free'] ) ) {
            update_option( 'pekifiken_transfers_limit', (int) $data['max_free'], true );
        } elseif ( isset( $data['quota_total'] ) ) {
            update_option( 'pekifiken_transfers_limit', (int) $data['quota_total'], true );
        }

        if ( isset( $data['reset'] ) && is_string( $data['reset'] ) ) {
            update_option( 'pekifiken_transfers_reset', $data['reset'], true );
        }
        if ( isset( $data['plan'] ) && is_string( $data['plan'] ) ) {
            update_option( 'pekifiken_plan', strtoupper( $data['plan'] ), true );
        }
        if ( array_key_exists( 'cancel_at_period_end', $data ) ) {
            update_option( 'pekifiken_cancel_at_period_end', ! empty( $data['cancel_at_period_end'] ) ? '1' : '0', true );
        }
        if ( isset( $data['auto_upgrade_enabled'] ) ) {
            update_option( 'pekifiken_auto_upgrade_enabled', (string) ( ! empty( $data['auto_upgrade_enabled'] ) ? '1' : '0' ), true );
        }
        if ( isset( $data['next_renewal'] ) && is_string( $data['next_renewal'] ) ) {
            update_option( 'pekifiken_next_renewal', $data['next_renewal'], true );
        } elseif ( isset( $data['renewal'] ) && is_string( $data['renewal'] ) ) {
            update_option( 'pekifiken_next_renewal', $data['renewal'], true );
        }
    }

    /** Helper for notices */
    public function get_remaining_quota() : ?array {
        $used  = get_option( 'pekifiken_transfers_used', null );
        $limit = get_option( 'pekifiken_transfers_limit', null );
        if ( $used === null || $limit === null ) return null;
        $used = (int) $used; $limit = (int) $limit;
        return [
            'used'      => $used,
            'limit'     => $limit,
            'remaining' => max( 0, $limit - $used ),
        ];
    }
}
