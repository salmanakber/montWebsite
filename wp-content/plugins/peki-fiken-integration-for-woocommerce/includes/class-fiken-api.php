<?php
namespace FikenBilag;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Fiken_API {

    /** Endpoints (overridable via filters) */
    private const DEFAULT_API_URL     = 'https://peki.no/fiken/index.php';
    private const DEFAULT_TIMEOUT_SEC = 30;
    private const DEFAULT_RETRIES     = 2; // extra attempts for transient network errors

    /** @var string */
    protected $employee_token;

    /** @var string */
    protected $api_url;

    public function __construct( $employee_token ) {
        $this->employee_token = sanitize_text_field( (string) $employee_token );
        // Allow override if needed (new, unique filter prefix).
        $this->api_url = apply_filters( 'pekifiken_api_url', self::DEFAULT_API_URL );
    }

    /**
     * Send normal invoice (voucher).
     *
     * @param array $data
     * @return array|\WP_Error
     */
    public function send_bilag( $data ) {
        $payload = $this->build_payload( 'send_bilag', $data, null ); // type=null => normal invoice
        return $this->transport( $payload );
    }

    /**
     * Send credit note (refund). Uses action=send_bilag with type=credit.
     *
     * @param array $data
     * @return array|\WP_Error
     */
    public function send_credit( $data ) {
        $payload = $this->build_payload( 'send_bilag', $data, 'credit' );
        return $this->transport( $payload );
    }

    /**
     * Get Fiken accounts for the connected company.
     *
     * @return array|\WP_Error
     */
    public function get_accounts() {
        // Get company slug
        $company_slug = (string) get_option( 'pekifiken_company_slug', (string) get_option( 'fiken_company_slug', '' ) );
        $data = array( 'companySlug' => $company_slug );
        $payload = $this->build_payload( 'list_accounts', $data, null );
        return $this->transport( $payload );
    }

    /**
     * Check VAT registration status for the connected company.
     *
     * @return array|\WP_Error
     */
    public function check_vat() {
        $company_slug = (string) get_option( 'pekifiken_company_slug', (string) get_option( 'fiken_company_slug', '' ) );
        $data = array( 'companySlug' => $company_slug );
        $payload = $this->build_payload( 'check_vat', $data, null );
        return $this->transport( $payload );
    }

	/**
	 * Download invoice PDF via backend (returns filename, mime, base64).
	 *
	 * @param int $invoice_id
	 * @return array|\WP_Error
	 */
	public function get_invoice_pdf( int $invoice_id ) {
		$company_slug = (string) get_option( 'pekifiken_company_slug', (string) get_option( 'fiken_company_slug', '' ) );
		$data = array(
			'companySlug' => $company_slug,
			'invoice_id'  => (int) $invoice_id,
		);
		$payload = $this->build_payload( 'get_invoice_pdf', $data, null );
		return $this->transport( $payload );
	}

    public function get_bank_accounts() {
    $company_slug  = (string) get_option( 'pekifiken_company_slug', (string) get_option( 'fiken_company_slug', '' ) );
    $connection_id = (string) get_option( 'pekifiken_connection_id', '' );

    $candidates = array(
        array_filter( array( 'companySlug' => $company_slug, 'connectionId' => $connection_id ) ),
        array_filter( array( 'connectionId' => $connection_id ) ),
        array(), // la server utlede fra token
    );

    // Prøv begge action-navn: nytt først, deretter ev. legacy
    $actions = array( 'list_bank_accounts', 'bank_accounts' );

    foreach ( $candidates as $data ) {
        foreach ( $actions as $action ) {
            $payload = $this->build_payload( $action, $data, null );
            $res     = $this->transport( $payload );

            if ( is_wp_error( $res ) ) {
                // Ved 400/404 kan neste action/candidate forsøkes
                $code = (int) ( $res->get_error_data()['status'] ?? 0 );
                if ( in_array( $code, array( 400, 404 ), true ) ) {
                    continue;
                }
                // Andre feil: returner med en gang
                return $res;
            }

            // Fikk noe svar; returner videre til settings-siden
            return $res;
        }
    }

    // Falt helt gjennom
    return new \WP_Error( 'pekifiken_empty_bank_accounts', __( 'Backend returned no bank accounts for this company/token.', 'peki-fiken-integration-for-woocommerce' ) );
}


    /* ===================== Internal helpers ===================== */

    /**
     * Build standard payload. $type may be 'credit' for credit note.
     *
     * @param string      $action
     * @param array|mixed $data
     * @param string|null $type
     * @return array
     */
    private function build_payload( string $action, $data, ?string $type ): array {
        // Token: prefer ctor value, else fall back to stored options (legacy supported).
        $employee_token = $this->employee_token;
        if ( '' === $employee_token ) {
            $employee_token = (string) get_option( 'pekifiken_employee_token', '' );
            if ( '' === $employee_token ) {
                $employee_token = (string) get_option( 'fiken_employee_token', '' );
                if ( '' === $employee_token ) {
                    $employee_token = (string) get_option( 'wfb_employee_token', '' );
                }
            }
            $employee_token = sanitize_text_field( $employee_token );
        }

        // Sanitize values, keep keys.
        $data = is_array( $data ) ? $this->deep_sanitize_values( $data ) : array();

        $payload = array(
            'action'         => $action,
            'employee_token' => $employee_token,
            // ⚠️ Removed local plan/kvote flag (is_pro). Server decides.
            'data'           => $data,
            'site'           => home_url(),
            'wc_version'     => defined( 'WC_VERSION' ) ? WC_VERSION : null,
            'wp_version'     => get_bloginfo( 'version' ),
            'plugin'         => 'peki-fiken-integration-for-woocommerce',
        );

        if ( $type ) {
            $payload['type'] = $type; // e.g. 'credit'
        }

        return $payload;
    }

    /**
     * Perform POST with retry and filterable headers/args.
     *
     * @param array $payload
     * @return array|\WP_Error
     */
    private function transport( array $payload ) {
        if ( empty( $payload['employee_token'] ) ) {
            return new \WP_Error(
                'pekifiken_missing_token',
                __( 'Missing employee token for Fiken export.', 'peki-fiken-integration-for-woocommerce' )
            );
        }

        $version = defined( 'PEKIFIKEN_VERSION' ) ? PEKIFIKEN_VERSION : '1.0.0';
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent'   => 'Peki-WooToFiken/' . $version . '; ' . home_url(),
        );
        $headers = apply_filters( 'pekifiken_http_headers', $headers, $payload );

        $args = array(
            'method'      => 'POST',
            'headers'     => $headers,
            'body'        => wp_json_encode( $payload ),
            'timeout'     => apply_filters( 'pekifiken_http_timeout', self::DEFAULT_TIMEOUT_SEC, $payload ),
            // Avoid premature cURL error 28 (~5s) on slow networks by increasing connect timeout.
            'connect_timeout' => 20,
            'redirection' => 3,
            'blocking'    => true,
            'data_format' => 'body',
        );
        $args = apply_filters( 'pekifiken_http_args', $args, $payload );

        $attempts = 0;
        $max      = max( 0, (int) apply_filters( 'pekifiken_http_retries', self::DEFAULT_RETRIES, $payload ) );

        do {
            $attempts++;
            $response = wp_remote_post( $this->api_url, $args );

            if ( ! is_wp_error( $response ) ) {
                $code     = (int) wp_remote_retrieve_response_code( $response );
                $body_raw = wp_remote_retrieve_body( $response );
                $body     = json_decode( $body_raw, true );
                if ( JSON_ERROR_NONE !== json_last_error() ) {
                    $body = null;
                }

                // Success:
                if ( 200 === $code ) {
                    // If server returns a logical error payload, handle gracefully too.
                    if ( is_array( $body ) && isset( $body['error'] ) && $body['error'] === 'limit_reached' ) {
                        $this->record_remote_error_notice();
                        return new \WP_Error( 'limit_reached', 'Limit reached', array( 'status' => 200, 'body' => $body ) );
                    }
                    return array( 'code' => $code, 'body' => $body );
                }

                // Explicit monthly limit from server:
                if ( 429 === $code ) {
                    $this->record_remote_error_notice();
                    return new \WP_Error( 'limit_reached', 'Limit reached', array( 'status' => $code, 'body' => $body ) );
                }

                // No retry for logical client errors (4xx except 408/429).
                if ( $code >= 400 && $code < 500 && ! in_array( $code, array( 408, 429 ), true ) ) {
                    /* translators: 1: HTTP status code, 2: Raw response body */
                    $pattern = __( 'Export service returned HTTP %1$s. Response: %2$s', 'peki-fiken-integration-for-woocommerce' );

                    return new \WP_Error(
                        'pekifiken_bad_status',
                        sprintf(
                            $pattern,
                            (string) $code,
                            is_scalar( $body_raw ) ? wp_strip_all_tags( (string) $body_raw ) : ''
                        ),
                        array( 'status' => $code, 'body' => $body )
                    );
                }
            }

            // WP_Error or retryable codes → small backoff.
            if ( $attempts <= $max ) {
                usleep( 200000 * $attempts ); // 0.2s, 0.4s, 0.6s ...
            }

        } while ( $attempts <= $max );

        if ( is_wp_error( $response ) ) {
            /* translators: %s: WP_Error message from HTTP request */
            $pattern = __( 'Failed to contact export service: %s', 'peki-fiken-integration-for-woocommerce' );

            return new \WP_Error(
                'pekifiken_http_error',
                sprintf(
                    $pattern,
                    $response->get_error_message()
                )
            );
        }

        $code     = (int) wp_remote_retrieve_response_code( $response );
        $body_raw = wp_remote_retrieve_body( $response );

        /* translators: 1: HTTP status code, 2: Raw response body */
        $pattern = __( 'Export service returned HTTP %1$s. Response: %2$s', 'peki-fiken-integration-for-woocommerce' );

        return new \WP_Error(
            'pekifiken_bad_status',
            sprintf(
                $pattern,
                (string) $code,
                is_scalar( $body_raw ) ? wp_strip_all_tags( (string) $body_raw ) : ''
            ),
            array( 'status' => $code )
        );
    }

    /**
     * Store a one-time admin notice saying the remote service reports the monthly limit is reached.
     */
    private function record_remote_error_notice(): void {
        set_transient(
            'pekifiken_last_remote_error',
            __( 'The remote service reports your monthly transfer limit is reached. Please open the subscription portal for details.', 'peki-fiken-integration-for-woocommerce' ),
            2 * MINUTE_IN_SECONDS
        );
    }

    /**
     * Deep sanitize values (preserve keys).
     *
     * @param mixed $value
     * @return mixed
     */
    private function deep_sanitize_values( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $k => $v ) {
                $value[ $k ] = $this->deep_sanitize_values( $v );
            }
            return $value;
        }

        if ( is_string( $value ) ) {
            // Strip unsafe tags, keep content.
            return wp_kses_post( $value );
        }

        if ( is_scalar( $value ) || is_null( $value ) ) {
            return $value;
        }

        return (string) maybe_serialize( $value );
    }
}
