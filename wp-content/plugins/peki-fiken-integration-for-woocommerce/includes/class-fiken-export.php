<?php
namespace FikenBilag;

require_once plugin_dir_path( __FILE__ ) . 'class-fiken-api.php';

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Fiken_Export {

    /* ===================== Public API ===================== */

    /**
     * Export a WooCommerce order to Fiken via Peki service.
     *
     * @param int $order_id
     * @return array{success:bool,error?:string,code?:int,body?:mixed}
     */
    public static function eksporter_ordren( $order_id ) {
        self::log('info', 'export start', ['stage'=>'begin','order_id'=>(int)$order_id]);

        if ( ! class_exists( 'WC_Order' ) ) {
            return ['success'=>false,'error'=>__('WooCommerce is not active.','peki-fiken-integration-for-woocommerce')];
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return ['success'=>false,'error'=>__('Order not found.','peki-fiken-integration-for-woocommerce')];
        }

        // Guard: avoid sending normal invoice when this order has a refund
        if ( $order->get_status() === 'refunded' || floatval( $order->get_total_refunded() ) > 0 ) {
            return ['success'=>false,'error'=>__('Order has refund(s); use refund export.','peki-fiken-integration-for-woocommerce')];
        }

        // Block export if company is not VAT registered (cached flag set by settings page)
        $blocked = self::bool_option('fiken_blocked_non_vat_company', false);
        if ( $blocked ) {
            self::log('warning','blocked locally due to non-VAT company flag',['stage'=>'blocked']);
            return ['success'=>false,'error'=>__('Company is not VAT registered. Integration is disabled.','peki-fiken-integration-for-woocommerce')];
        }

        // Billing (inkl. company)
        // NOTE: Keep keys stable; backend may rely on them. Additive fields are safe.
        $billing = [
            'first_name' => (string) $order->get_billing_first_name(),
            'last_name'  => (string) $order->get_billing_last_name(),
            'company'    => (string) $order->get_billing_company(),
            'address_1'  => (string) $order->get_billing_address_1(),
            'address_2'  => (string) $order->get_billing_address_2(),
            'city'       => (string) $order->get_billing_city(),
            'postcode'   => (string) $order->get_billing_postcode(),
            'country'    => (string) $order->get_billing_country(),
            'state'      => (string) $order->get_billing_state(),
            'email'      => (string) $order->get_billing_email(),
            'phone'      => (string) $order->get_billing_phone(),
        ];

        // Fallback: if billing address is missing, use shipping address (common for some checkouts).
        if ( $billing['address_1'] === '' && method_exists( $order, 'get_shipping_address_1' ) ) {
            $ship_addr1 = (string) $order->get_shipping_address_1();
            if ( $ship_addr1 !== '' ) {
                $billing['address_1'] = $ship_addr1;
                $billing['address_2'] = (string) $order->get_shipping_address_2();
                $billing['city']      = (string) $order->get_shipping_city();
                $billing['postcode']  = (string) $order->get_shipping_postcode();
                $billing['country']   = (string) $order->get_shipping_country();
                $billing['state']     = (string) $order->get_shipping_state();
                if ( $billing['company'] === '' && method_exists( $order, 'get_shipping_company' ) ) {
                    $billing['company'] = (string) $order->get_shipping_company();
                }
            }
        }

        // Sikker kontakt – aldri tomt navn
        $billing['full_name'] = self::derive_contact_name($order, $billing);
        self::log('info','billing contact computed',[
            'stage'=>'contact_precheck',
            'has_first'=> $billing['first_name'] !== '',
            'has_last' => $billing['last_name'] !== '',
            'has_company'=> $billing['company'] !== '',
            'full_name'=> $billing['full_name'],
        ]);
        if ($billing['full_name'] === '') {
            self::log('error','Abort: empty contact name after precheck',['stage'=>'contact_precheck_fail','order_id'=>(int)$order->get_id()]);
            return ['success'=>false,'error'=>__('Cannot export: missing customer name (billing/shipping).','peki-fiken-integration-for-woocommerce')];
        }

        // Items
        $line_items = self::build_line_items_from_items( $order->get_items(), false );
        $shipping_line = self::build_shipping_line_item( $order );
        if ( $shipping_line ) {
            $line_items[] = $shipping_line;
        } else {
            self::log('info','no shipping line (<=0)',['stage'=>'shipping_line']);
        }

        $before_count = count($line_items);
        $line_items = self::aggregate_lines( $line_items );
        $after_count  = count($line_items);

        $summary = self::summarize_items($line_items);
        self::log('info','aggregate lines result',[
            'stage'=>'aggregate','before'=>$before_count,'after'=>$after_count,'summary'=>$summary
        ]);

        // Idempotency
        $transfer_id = self::generate_uuid_v4();
        self::log('info','transfer id generated',['stage'=>'idempotency','transfer_id'=>$transfer_id]);

        // Force no VAT flag
        $force_no_vat = self::bool_option('fiken_force_no_vat', false);
        if ( $force_no_vat ) {
            foreach ( $line_items as &$li ) {
                $li['vatType'] = 'NONE';
            }
            unset($li);
            self::log('info','force_no_vat enabled → set all vatType=NONE',['stage'=>'vat_flag']);
        }

        // Token
        $employee_token = self::get_employee_token();
        if ( $employee_token === '' ) {
            self::log('warning','abort, missing employee token',['stage'=>'token_missing']);
            return ['success'=>false,'error'=>__('Missing employee token.','peki-fiken-integration-for-woocommerce')];
        }
        self::log('info','token loaded',['stage'=>'token','from'=>self::$token_source,'masked'=>self::mask($employee_token)]);

        // companySlug
        $companySlug = (string) get_option( 'pekifiken_company_slug', (string) get_option( 'fiken_company_slug', '' ) );
        self::log('info','company slug resolved',['stage'=>'company','companySlug'=>$companySlug]);

        // HPOS state (informativt)
        $hpos = self::is_hpos_enabled();
        self::log('info','order HPOS status',['stage'=>'hpos','enabled'=>$hpos]);

        // Payment / cash
        $payment_method = self::resolve_payment_method($order); // <- robust metodelesing
        $bank_account_map  = get_option( 'pekifiken_bank_account_map', [] );
        // Rens bort tomme verdier i mapping
        if (is_array($bank_account_map)) {
            $bank_account_map = array_filter(array_map('strval', $bank_account_map), 'strlen');
        } else {
            $bank_account_map = [];
        }
        // Per-gateway document type overrides: ALWAYS honor explicit 'false' (Invoice) if set
        $cash_behavior_map = get_option( 'fiken_gateway_cash_map', [] );
        $map_val = is_array( $cash_behavior_map ) && isset( $cash_behavior_map[ $payment_method ] ) ? (string) $cash_behavior_map[ $payment_method ] : null;
        $cash = ! ( $map_val === 'false' ); // 'false' => Invoice (unpaid)
        // Allow last-resort override via filter
        $cash = (bool) apply_filters( 'pekifiken_cash_flag', $cash, $payment_method, $cash_behavior_map, $order );

        // Finn valgt kontokode for gateway (kan være "1960", "1960:1000X" eller en lang "1960:10617239079")
        $resolved_full_code = '';
        if ( $cash ) {
            $resolved_full_code = self::pick_resolved_bank_code($bank_account_map, $payment_method);
        }

        // Normaliser ALLTID til "short:1000X" (ikke send lange haler videre)
        $normalized = self::normalize_to_1000x($resolved_full_code, $payment_method);
        $resolved_short = $normalized['short'];     // f.eks. "1960"
        $resolved_1000x = $normalized['full_1000']; // f.eks. "1960:10002" (ALDRI lang hale)

        // Minor unit factor from Woo settings (e.g., 10^decimals). Default 100 (øre).
        $decimals = function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 2;
        $minor_factor = (int) max(1, pow(10, max(0, $decimals)));

        $data = [
            'order_id'      => (int) $order->get_id(),
            'order_key'     => (string) $order->get_order_key(),
            'order_date'    => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '',
            'currency'      => (string) $order->get_currency(),
            'total'         => (float) $order->get_total(),
            'reference'     => 'Woo Order #' . $order->get_id(),
            'billing'       => $billing,
            'line_items'    => $line_items,
            'customer_note' => (string) $order->get_customer_note(),
            'site_url'      => get_site_url(),
            'transfer_id'   => $transfer_id,
            'companySlug'   => $companySlug,
            'cash'          => $cash,
            'payment_gateway' => (string) $payment_method, // hjelper i server-logg
            'minor_unit_factor' => $minor_factor,
        ];
        if ( $force_no_vat ) {
            $data['force_no_vat'] = true;
        }

        // Send med KORT + 1000X-varianten – aldri lang kode
        if ( $resolved_short !== '' && $resolved_1000x !== '' ) {
            $data['payment_account']  = $resolved_short;  // "1960"
            $data['bank_account_code'] = $resolved_1000x; // "1960:1000X"
        }

        self::log('info','payment resolved',[
            'stage'=>'payment',
            'payment_method'=>$payment_method,
            'cash'=>$cash,
            'cash_override_map_val'=>$map_val,
            'mapping_keys'=> array_keys($bank_account_map),
            'resolved_short'=> $resolved_short,
            'bank_account_code_1000x' => $resolved_1000x,
        ]);
        self::log('info','items built & aggregated',[
            'stage'=>'items_ready','elapsed_ms'=> self::since_ms('begin'),
            'summary'=> $summary,
            'verbose_items' => self::bool_option('fiken_debug_verbose', false) ? $line_items : '(set option fiken_debug_verbose=1 to log items)'
        ]);
        self::log('info','sending to Peki',[
            'stage'=>'api_send_pre',
            'transfer_id'=>$transfer_id,
            'order_id'=>(int)$order->get_id(),
            'companySlug'=>$companySlug,
            'line_items_summary'=>$summary,
            'cash'=>$cash,
            'has_payment_account'=> isset($data['payment_account']) || isset($data['bank_account_code'])
        ]);

        // API call
        $api      = new Fiken_API( $employee_token );
        $resultat = $api->send_bilag( $data );

        if ( is_wp_error( $resultat ) ) {
            self::log('error','send_bilag error',[
                'stage'=>'api_error',
                'message'=>$resultat->get_error_message(),
                'order_id'=>(int)$order->get_id()
            ]);
            $order->add_order_note( sprintf(
                /* translators: %s: error message from the export service */
                __( 'Fiken: Export failed - %s', 'peki-fiken-integration-for-woocommerce' ),
                $resultat->get_error_message()
            ) );
            return ['success'=>false,'error'=>$resultat->get_error_message()];
        }

        $code = isset($resultat['code']) ? (int)$resultat['code'] : 200;
        $body = (isset($resultat['body']) && is_array($resultat['body'])) ? $resultat['body'] : [];

        self::log('info','api response received',['stage'=>'api_resp','transfer_id'=>$transfer_id,'code'=>$code,'body'=>$body]);

        // Behandle "logisk feil" i body selv om HTTP 200
        if (!empty($body['error'])) {
            self::log('error','api logical error in body',[
                'stage'=>'api_resp_error',
                'code'=>$code,
                'error'=>$body['error'],
                'order_id'=>(int)$order->get_id()
            ]);
            $order->add_order_note( sprintf(
                /* translators: %s: logical error message returned in response body */
                __( 'Fiken: Export error - %s', 'peki-fiken-integration-for-woocommerce' ),
                $body['error']
            ) );
            return ['success'=>false,'error'=>'Fiken/Peki: '.$body['error'],'code'=>$code];
        }

        // Plukk ut invoice id
        $iid = '';
        if (!empty($body['invoice_id'])) {
            $iid = (string)$body['invoice_id'];
        } elseif (!empty($body['invoiceId'])) {
            $iid = (string)$body['invoiceId'];
        } elseif (!empty($body['resource']) && preg_match('#/invoices/(\d+)#',$body['resource'],$m)) {
            $iid = (string)$m[1];
        }

        if ($iid === '') {
            self::log('error','no invoice id/resource returned',[
                'stage'=>'api_resp_missing_invoice',
                'code'=>$code,
                'body'=>$body,
                'order_id'=>(int)$order->get_id()
            ]);
            $order->add_order_note( __( 'Fiken: Export failed - no invoice ID returned', 'peki-fiken-integration-for-woocommerce' ) );
            return ['success'=>false,'error'=>__('Export failed: Fiken did not return an invoice id. See logs for details.','peki-fiken-integration-for-woocommerce'),'code'=>$code,'body'=>$body];
        }

        // Invoice number (kan mangle)
        $invno = '';
        if (!empty($body['invoice_number'])) {
            $invno = (string)$body['invoice_number'];
        } elseif (!empty($body['invoiceNumber'])) {
            $invno = (string)$body['invoiceNumber'];
        }

        // HPOS-sikker lagring + verifikasjon
        $save_mode = self::store_invoice_meta($order, (int)$iid, $invno);
        $verify = [
            'wc__fiken_invoice_id' => (string)$order->get_meta('_fiken_invoice_id', true),
            'wc_fiken_invoice_id'  => (string)$order->get_meta('fiken_invoice_id', true),
            'pm__fiken_invoice_id' => (string)get_post_meta( $order_id, '_fiken_invoice_id', true ),
            'pm_fiken_invoice_id'  => (string)get_post_meta( $order_id, 'fiken_invoice_id', true ),
        ];
        $ok = ((int)($verify['wc__fiken_invoice_id'] ?: 0) === (int)$iid) || ((int)($verify['pm__fiken_invoice_id'] ?: 0) === (int)$iid) || ((int)($verify['wc_fiken_invoice_id'] ?: 0) === (int)$iid) || ((int)($verify['pm_fiken_invoice_id'] ?: 0) === (int)$iid);

        self::log( $ok ? 'info':'error',
            $ok ? 'invoice_id saved to meta (verified)' : 'invoice_id save verification failed',
            ['stage'=>'save_meta','order_id'=>(int)$order_id,'invoice_id'=>(int)$iid,'mode'=>$save_mode,'verify'=>$verify]
        );

        if ($invno !== '') {
            self::log('info','invoice_number saved to meta',['stage'=>'save_meta','order_id'=>(int)$order_id,'invoice_number'=>$invno,'mode'=>$save_mode]);
        } else {
            self::log('notice','invoice_number not provided',['stage'=>'save_meta_notice','order_id'=>(int)$order_id]);
        }

        // Persist quota/status if server returned it
        if ( isset( $resultat['body'] ) && is_array( $resultat['body'] ) ) {
            $body = $resultat['body'];
            if ( isset( $body['quota'] ) && is_array( $body['quota'] ) && isset( $body['quota']['used'] ) ) {
                update_option( 'fiken_used_count', (int) $body['quota']['used'], false );
            }
            if ( class_exists( '\FikenBilag\\Fiken_Status' ) && isset( $body['plan'] ) ) {
                \FikenBilag\Fiken_Status::update_cache_from_array( $body, 300 );
            }
        }

        self::log('info','export done',['stage'=>'end','transfer_id'=>$transfer_id,'code'=>$code,'elapsed_ms_total'=>self::since_ms('begin')]);

        // Add order note
        $order->add_order_note( sprintf(
            /* translators: 1: invoice id, 2: optional formatted invoice number in parentheses */
            __( 'Fiken: Exported successfully. Invoice ID: %1$d%2$s', 'peki-fiken-integration-for-woocommerce' ),
            $iid,
            $invno ? ' (' . $invno . ')' : ''
        ) );

		// Attempt to fetch and store invoice PDF locally (if enabled)
		if ( (bool) get_option( 'fiken_auto_save_invoice_pdf', false ) ) {
			try {
				self::maybe_store_invoice_pdf( $order, (int) $iid, $invno );
			} catch ( \Throwable $e ) {
				self::log('warning','store pdf failed',['stage'=>'pdf','err'=>$e->getMessage()]);
			}
		}

        return ['success'=>true,'code'=>$code,'body'=> isset($resultat['body']) ? $resultat['body'] : null ];
    }

    /**
     * Export refund (credit note) for an order.
     *
     * @param int $order_id
     * @param int $refund_id
     * @return array{success:bool,error?:string,code?:int,body?:mixed}
     */
    public static function eksporter_refund( $order_id, $refund_id ) {

        self::log('info','refund start',['stage'=>'begin','order_id'=>(int)$order_id,'refund_id'=>(int)$refund_id]);

        if ( ! class_exists( 'WC_Order' ) ) {
            return ['success'=>false,'error'=>__('WooCommerce is not active.','peki-fiken-integration-for-woocommerce')];
        }

        $order  = wc_get_order( $order_id );
        $refund = wc_get_order( $refund_id ); // WC_Order_Refund

        if ( ! $order || ! $refund || ! is_a( $refund, \WC_Order_Refund::class ) ) {
            return ['success'=>false,'error'=>__('Refund not found.','peki-fiken-integration-for-woocommerce')];
        }

        // Refund items (absolute)
        $line_items = self::build_line_items_from_items( $refund->get_items(), true );
        $summary_refund = self::summarize_items($line_items, true);

        // Idempotency
        $transfer_id = self::generate_uuid_v4();
        self::log('info','refund transfer id generated',['stage'=>'idempotency','transfer_id'=>$transfer_id]);

        // Force no VAT flag
        $force_no_vat = self::bool_option('fiken_force_no_vat', false);
        if ( $force_no_vat ) {
            self::log('info','refund force_no_vat enabled',['stage'=>'vat_flag']);
        }

        // Token
        $employee_token = self::get_employee_token();
        if ( $employee_token === '' ) {
            return ['success'=>false,'error'=>__('Missing employee token.','peki-fiken-integration-for-woocommerce')];
        }
        self::log('info','refund token loaded',['stage'=>'token','from'=>self::$token_source,'masked'=>self::mask($employee_token)]);

        // companySlug
        $companySlug = (string) get_option( 'pekifiken_company_slug', (string) get_option( 'fiken_company_slug', '' ) );
        self::log('info','refund company slug resolved',['stage'=>'company','companySlug'=>$companySlug]);

        // HPOS
        $hpos = self::is_hpos_enabled();
        self::log('info','refund HPOS status',['stage'=>'hpos','enabled'=>$hpos]);

        // Finn original invoice id – prøv WC CRUD og post_meta
        $raw_wc = [];
        foreach ( ['_fiken_invoice_id','fiken_invoice_id','_peki_fiken_invoice_id','peki_fiken_invoice_id','_wfb_invoice_id','wfb_invoice_id','_fiken_invoice','fiken_invoice'] as $k ) {
            $raw_wc[$k] = (string) $order->get_meta( $k, true );
        }
        self::log('info','refund invoice meta read (WC CRUD)',['stage'=>'orig_invoice_meta_wc','order_id'=>(int)$order->get_id(),'raw'=>$raw_wc]);

        $raw_pm = [];
        foreach ( array_keys($raw_wc) as $k ) {
            $raw_pm[$k] = (string) get_post_meta( $order->get_id(), $k, true );
        }
        self::log('info','refund invoice meta read (post_meta fallback)',['stage'=>'orig_invoice_meta_post','order_id'=>(int)$order->get_id(),'raw'=>$raw_pm]);

        $orig_invoice_id = 0;
        foreach ( ['_fiken_invoice_id','fiken_invoice_id','_peki_fiken_invoice_id','peki_fiken_invoice_id','_wfb_invoice_id','wfb_invoice_id','_fiken_invoice','fiken_invoice'] as $k ) {
            $v = (int) ( $raw_wc[$k] ?? 0 );
            if ( $v <= 0 ) $v = (int) ( $raw_pm[$k] ?? 0 );
            if ( $v > 0 ) { $orig_invoice_id = $v; break; }
        }

        if ( $orig_invoice_id <= 0 ) {
            self::log('error','STOP: missing original invoice id on order',['stage'=>'orig_invoice_missing','order_id'=>(int)$order->get_id()]);
            return [
                'success'=>false,
                'error'=>__('Missing original invoice id on the order. Create the invoice first, then retry the refund.','peki-fiken-integration-for-woocommerce'),
                'code'=>400
            ];
        }

        // Bygg payload
        $data = [
            'order_id'      => (int) $order->get_id(),
            'order_key'     => (string) $order->get_order_key(),
            'refund_id'     => (int) $refund->get_id(),
            'refund_number' => (string) $refund->get_id(),
            'refund_date'   => $refund->get_date_created() ? $refund->get_date_created()->date( 'Y-m-d' ) : '',
            'currency'      => (string) $order->get_currency(),
            'total_refund'  => abs( (float) $refund->get_amount() ),
            'reference'     => 'Woo Refund #' . $refund->get_id() . ' for Order #' . $order->get_id(),
            'line_items'    => $line_items,
            'site_url'      => get_site_url(),
            'type'          => 'credit',
            'original'      => [
                'order_id'    => (int) $order->get_id(),
                'order_key'   => (string) $order->get_order_key(),
                'invoice_id'  => (int) $orig_invoice_id,
            ],
            'transfer_id'   => $transfer_id,
            'companySlug'   => $companySlug,
        ];
        if ( $force_no_vat ) {
            $data['force_no_vat'] = true;
        }

        self::log('info','refund sending to Peki',[
            'stage'=>'api_send_pre_refund',
            'transfer_id'=>$transfer_id,
            'order_id'=>(int)$order->get_id(),
            'refund_id'=>(int) $refund->get_id(),
            'companySlug'=>$companySlug,
            'orig_invoice_id'=>(int)$orig_invoice_id,
            'line_items_summary'=>$summary_refund,
            'verbose_items' => self::bool_option('fiken_debug_verbose', false) ? $line_items : '(set option fiken_debug_verbose=1 to log items)'
        ]);

        // Call API as credit note
        $api      = new Fiken_API( $employee_token );
        $resultat = $api->send_credit( $data );

        if ( is_wp_error( $resultat ) ) {
            self::log('error','send_credit error',[
                'stage'=>'api_error_refund',
                'message'=>$resultat->get_error_message(),
                'order_id'=>(int)$order->get_id(),
                'refund_id'=>(int)$refund->get_id()
            ]);
            return ['success'=>false,'error'=>$resultat->get_error_message()];
        }

        $code = isset($resultat['code']) ? (int)$resultat['code'] : 200;
        $body = (isset($resultat['body']) && is_array($resultat['body'])) ? $resultat['body'] : [];
        self::log('info','refund api response received',['stage'=>'api_resp_refund','transfer_id'=>$transfer_id,'code'=>$code,'body'=>$body]);

        if (!empty($body['error'])) {
            self::log('error','refund api logical error',[
                'stage'=>'api_resp_error_refund',
                'code'=>$code,
                'error'=>$body['error'],
                'order_id'=>(int)$order->get_id(),
                'refund_id'=>(int)$refund->get_id()
            ]);
            $order->add_order_note( sprintf(
                /* translators: %s: logical error message returned in response body */
                __( 'Fiken: Refund export error - %s', 'peki-fiken-integration-for-woocommerce' ),
                $body['error']
            ) );
            return ['success'=>false,'error'=>'Fiken/Peki: '.$body['error'],'code'=>$code];
        }

        // Persist quota/status if server returned it (refund)
        if ( isset( $body['quota'] ) && is_array( $body['quota'] ) && isset( $body['quota']['used'] ) ) {
            update_option( 'fiken_used_count', (int) $body['quota']['used'], false );
        }
        if ( class_exists( '\\FikenBilag\\Fiken_Status' ) && isset( $body['plan'] ) ) {
            \FikenBilag\Fiken_Status::update_cache_from_array( $body, 300 );
        }

        self::log('info','refund done',['stage'=>'end_refund','transfer_id'=>$transfer_id,'code'=>$code,'elapsed_ms_total'=>self::since_ms('begin')]);

        // Add order note
        $order->add_order_note( sprintf(
            /* translators: %d: refund id */
            __( 'Fiken: Refund #%d exported successfully', 'peki-fiken-integration-for-woocommerce' ),
            $refund_id
        ) );

        return ['success'=>true,'code'=>$code,'body'=> isset($resultat['body']) ? $resultat['body'] : null ];
    }

    /* ===================== Helpers (business) ===================== */

    /**
     * Robust lesing av betalingsmetode fra ordren.
     */
    private static function resolve_payment_method( \WC_Order $order ): string {
        $pm = (string) $order->get_payment_method();
        if ($pm !== '') {
            return $pm;
        }
        // Fallback til lagret meta hvis tom (vanlig ved manuelt opprettede ordre)
        $meta_pm = (string) $order->get_meta('_payment_method', true);
        if ($meta_pm !== '') {
            self::log('notice','payment method empty on order object, using _payment_method meta',[
                'stage'=>'payment_method_fallback','meta_pm'=>$meta_pm
            ]);
            return sanitize_key($meta_pm);
        }
        return '';
    }

    /**
     * Velg bankkonto-kode (full) for gitt gateway ut fra mappingen.
     * Regler:
     * 1) Direkte treff på gateway-id
     * 2) '__default' hvis satt
     * 3) Hvis nøyaktig én unik verdi i mapping → bruk den
     */
    private static function pick_resolved_bank_code( array $map, string $gateway ): string {
        if ($gateway !== '' && !empty($map[$gateway])) {
            return (string) $map[$gateway];
        }
        if (!empty($map['__default'])) {
            return (string) $map['__default'];
        }
        $vals = array_values($map);
        $vals = array_values(array_filter($vals, 'strlen'));
        $unique = array_unique($vals);
        if (count($unique) === 1) {
            return (string) $unique[0];
        }
        return '';
    }

    /**
     * NORMALISER til "short:1000X" uansett input.
     * - Hvis input er "1960:1000X" → behold
     * - Hvis input er "1960" → lag "1960:1000X" via slot-override
     * - Hvis input er "1960:<lang hale>" → fold til "1960:1000X" (ikke send lang hale)
     * Slot X hentes fra option 'pekifiken_bank_slot_map' (array), med nøkler pr gateway og '__default'.
     */
    private static function normalize_to_1000x(string $raw, string $gateway): array {
        $raw = trim($raw);
        $short = '';
        $full1000 = '';

        // 1) Allerede på riktig format?
        if (preg_match('/^(\d{4}):1000([1-9])$/', $raw, $m)) {
            $short = $m[1];
            $full1000 = $raw;
            self::log('info','bank code already 1000X format',['stage'=>'bank_norm','input'=>$raw,'short'=>$short,'full'=>$full1000]);
            return ['short'=>$short,'full_1000'=>$full1000];
        }

        // 2) Kun kortkode?
        if (preg_match('/^(\d{4})$/', $raw, $m)) {
            $short = $m[1];
            $slot = self::pick_slot_for_gateway($gateway);
            $full1000 = $short . ':1000' . $slot;
            self::log('info','bank code short-only → built 1000X',['stage'=>'bank_norm','input'=>$raw,'gateway'=>$gateway,'slot'=>$slot,'full'=>$full1000]);
            return ['short'=>$short,'full_1000'=>$full1000];
        }

        // 3) Lang hale? ^\d{4}:\d{6,}$
        if (preg_match('/^(\d{4}):(\d{6,})$/', $raw, $m)) {
            $short = $m[1];
            $slot = self::pick_slot_for_gateway($gateway);
            $full1000 = $short . ':1000' . $slot;
            self::log('info','bank code long-tail → folded to 1000X',['stage'=>'bank_norm','input_masked'=>self::mask($raw, 4, 3),'gateway'=>$gateway,'slot'=>$slot,'full'=>$full1000]);
            return ['short'=>$short,'full_1000'=>$full1000];
        }

        // 4) Recognize "short:other" and force 1000X
        if (preg_match('/^(\d{4}):(\d{1,5})$/', $raw, $m)) {
            $short = $m[1];
            if (preg_match('/^1000[1-9]$/', $m[2])) {
                $full1000 = $raw;
            } else {
                $slot = self::pick_slot_for_gateway($gateway);
                $full1000 = $short . ':1000' . $slot;
            }
            self::log('info','bank code other format → coerced to 1000X',['stage'=>'bank_norm','input'=>$raw,'gateway'=>$gateway,'full'=>$full1000]);
            return ['short'=>$short,'full_1000'=>$full1000];
        }

        // 5) Ingenting gyldig – fall tilbake til standard 1920:10001 hvis vi kan gjette kort
        $slot = self::pick_slot_for_gateway($gateway);
        $short = '1920';
        $full1000 = '1920:1000' . $slot;
        self::log('warning','bank code unexpected → defaulted to 1920:1000X',['stage'=>'bank_norm','input'=>$raw,'gateway'=>$gateway,'slot'=>$slot,'full'=>$full1000]);
        return ['short'=>$short,'full_1000'=>$full1000];
    }

    /**
     * Velg slot X (1..9) for gateway fra WP option.
     * Option: pekifiken_bank_slot_map = ['klarna_payments'=>'1','stripe'=>'2','__default'=>'1']
     */
    private static function pick_slot_for_gateway(string $gateway): int {
        $map = get_option('pekifiken_bank_slot_map', []);
        $slot = null;
        if (is_array($map)) {
            if ($gateway !== '' && isset($map[$gateway])) {
                $slot = (int) $map[$gateway];
            } elseif (isset($map['__default'])) {
                $slot = (int) $map['__default'];
            }
        }
        if (!is_int($slot) || $slot < 1 || $slot > 9) {
            $slot = 1; // sane default
        }
        return $slot;
    }

    /**
     * Parse "1960:9481159097" -> ['short'=>'1960','full'=>'1960:9481159097'].
     * (Beholdt for bakoverkomp., men vi bruker nå normalize_to_1000x() videre i flyten.)
     */
    private static function parse_bank_account_code( string $code ): array {
        $code = trim($code);
        if (preg_match('/^(\d{4}):(\d{1,})$/', $code, $m)) {
            return ['short'=>$m[1], 'full'=>$code];
        }
        if (preg_match('/^\d{4}$/', $code)) {
            return ['short'=>$code, 'full'=>$code];
        }
        self::log('warning','bank account code has unexpected format',['stage'=>'bank_code_format','code'=>$code]);
        return ['short'=>'', 'full'=>$code];
    }

    /**
     * Build line_items from WC order/refund items.
     * For refund: quantities/totals are read as absolute; server will treat them as credits.
     *
     * @param \WC_Order_Item_Product[]|\WC_Order_Refund_Item[] $items
     * @param bool $is_refund
     * @return array
     */
    private static function build_line_items_from_items( $items, $is_refund ) : array {
        $line_items = [];

        foreach ( $items as $item ) {
            $qty          = max( 1, absint( $item->get_quantity() ) );
            $force_no_vat = self::bool_option('fiken_force_no_vat', false);

            if ( $force_no_vat ) {
                $subtotal_ex    = abs( (float) $item->get_subtotal() );
                $subtotal_tax   = method_exists( $item, 'get_subtotal_tax' ) ? abs( (float) $item->get_subtotal_tax() ) : 0.0;
                $line_total_ex  = abs( (float) $item->get_total() );
                $line_total_tax = method_exists( $item, 'get_total_tax' ) ? abs( (float) $item->get_total_tax() ) : 0.0;

                $subtotal_gross   = $subtotal_ex + $subtotal_tax;
                $line_total_gross = $line_total_ex + $line_total_tax;
                $discount_total   = max( 0.0, $subtotal_gross - $line_total_gross );

                $unit_price    = $qty > 0 ? ( $subtotal_gross / $qty ) : 0.0; // gross per unit
                $discount_each = $qty > 0 ? ( $discount_total / $qty ) : 0.0;
            } else {
                $subtotal       = abs( (float) $item->get_subtotal() ); // before discount (ex VAT)
                $line_total     = abs( (float) $item->get_total() );    // after discount (ex VAT)
                $discount_total = max( 0, $subtotal - $line_total );

                $unit_price    = $qty > 0 ? ( $subtotal / $qty ) : 0.0; // ex VAT per unit
                $discount_each = $qty > 0 ? ( $discount_total / $qty ) : 0.0;
            }

            // VAT type based on actual tax rate
            $vat_type = self::determine_vat_type( $item );

            // Product identifiers
            $product_id   = method_exists( $item, 'get_product_id' ) ? (int) $item->get_product_id() : 0;
            $variation_id = method_exists( $item, 'get_variation_id' ) ? (int) $item->get_variation_id() : 0;
            $sku          = '';
            if ( method_exists( $item, 'get_product' ) ) {
                $product = $item->get_product();
                if ( $product && is_object( $product ) && method_exists( $product, 'get_sku' ) ) {
                    $sku = (string) $product->get_sku();
                }
            }

            // Build one aggregated-ready line entry
            $line_items[] = [
                'name'         => (string) $item->get_name(),
                'quantity'     => (int) $qty,
                // Scale to Woo minor units (10^decimals); server will normalize to øre
                'unit_price'   => (int) round( $unit_price * (int) max(1, pow(10, max(0, function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 2 ))) ),
                'discount'     => (int) round( max( 0, $discount_each ) * (int) max(1, pow(10, max(0, function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 2 ))) ) * (int) $qty,  // total line discount
                'vatType'      => $vat_type,
                'product_id'   => $product_id,
                'variation_id' => $variation_id,
                'sku'          => $sku,
            ];
        }

        return $line_items;
    }

    /**
     * Build shipping line item if shipping total > 0.
     *
     * @param \WC_Order $order
     * @return array|null
     */
    private static function build_shipping_line_item( $order ): ?array {
        $force_no_vat   = self::bool_option('fiken_force_no_vat', false);
        $shipping_total = (float) $order->get_shipping_total();
        if ( $force_no_vat ) {
            $shipping_total += (float) ( method_exists( $order, 'get_shipping_tax' ) ? $order->get_shipping_tax() : 0.0 );
        }

        if ( $shipping_total <= 0 ) {
            return null;
        }

        // Get shipping method name
        $shipping_methods = $order->get_shipping_methods();
        $shipping_name    = 'Shipping';
        if ( ! empty( $shipping_methods ) ) {
            $shipping_method = reset( $shipping_methods );
            $method_title    = $shipping_method->get_method_title();
            $instance_name   = $shipping_method->get_name();
            $shipping_name   = $instance_name ?: ( $method_title ?: 'Shipping' );
        }

        // VAT type for shipping
        $shipping_vat_type = self::determine_shipping_vat_type( $order );

        // Income account setting
        $shipping_income_account = get_option( 'fiken_shipping_income_account', '' );

        $line_item = [
            'name'        => (string) $shipping_name,
            'quantity'    => 1,
            // Scale to Woo minor units (10^decimals); server will normalize to øre
            'unit_price'  => (int) round( $shipping_total * (int) max(1, pow(10, max(0, function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 2 ))) ),
            'discount'    => 0,
            'vatType'     => $shipping_vat_type,
            'type'        => 'shipping',
        ];

        if ( $shipping_income_account !== '' ) {
            $line_item['incomeAccount'] = $shipping_income_account;
        }

        return $line_item;
    }

    /**
     * Determine VAT type for shipping based on shipping taxes.
     *
     * @param \WC_Order $order
     * @return string
     */
    private static function determine_shipping_vat_type( $order ): string {
        $shipping_methods = $order->get_shipping_methods();
        if ( empty( $shipping_methods ) ) {
            return 'EXEMPT';
        }

        $max_rate_percent = 0;
        
        // Try to get rate from WooCommerce tax rate IDs (standard WooCommerce Tax)
        foreach ( $shipping_methods as $shipping_method ) {
            $taxes   = $shipping_method->get_taxes();
            $rate_ids = [];
            if ( ! empty( $taxes['total'] ) && is_array( $taxes['total'] ) ) {
                $rate_ids = array_merge( $rate_ids, array_keys( $taxes['total'] ) );
            }
            if ( ! empty( $taxes['subtotal'] ) && is_array( $taxes['subtotal'] ) ) {
                $rate_ids = array_merge( $rate_ids, array_keys( $taxes['subtotal'] ) );
            }
            $rate_ids = array_unique( array_filter( $rate_ids, 'strlen' ) );

            foreach ( $rate_ids as $rate_id ) {
                if ( class_exists( 'WC_Tax' ) && method_exists( 'WC_Tax', 'get_rate_percent' ) ) {
                    $rate_percent     = \WC_Tax::get_rate_percent( $rate_id );
                    $rate_percent     = (float) str_replace( '%', '', (string) $rate_percent );
                    $max_rate_percent = max( $max_rate_percent, $rate_percent );
                }
            }
        }

        // Fallback: Calculate from actual amounts (for Stripe Tax and other providers)
        if ( $max_rate_percent === 0 ) {
            $shipping_total = (float) $order->get_shipping_total();
            $shipping_tax   = method_exists( $order, 'get_shipping_tax' ) ? (float) $order->get_shipping_tax() : 0.0;
            
            if ( $shipping_total > 0 && $shipping_tax > 0 ) {
                // Calculate effective tax rate: (tax / total_ex_tax) * 100
                $max_rate_percent = ( $shipping_tax / $shipping_total ) * 100;
                self::log('info', 'calculated shipping vat from amounts', [
                    'stage'         => 'shipping_vat_calculation',
                    'shipping_total' => $shipping_total,
                    'shipping_tax'   => $shipping_tax,
                    'rate'           => round( $max_rate_percent, 2 )
                ]);
            }
        }

        // Map rate to Fiken VAT type
        if ( $max_rate_percent >= 24 ) {
            return 'HIGH';
        } elseif ( $max_rate_percent >= 14 ) {
            return 'LOW';
        } elseif ( $max_rate_percent >= 1 ) {
            // If there's any tax but less than 14%, still treat as taxable (LOW)
            return 'LOW';
        } else {
            return 'EXEMPT';
        }
    }

    /**
     * Determine VAT type based on tax rates applied to the item.
     *
     * @param \WC_Order_Item_Product $item
     * @return string VAT type: EXEMPT, LOW, HIGH, or NONE
     */
    private static function determine_vat_type( $item ): string {
        $taxes = $item->get_taxes();

        $rate_ids = [];
        if ( ! empty( $taxes['total'] ) && is_array( $taxes['total'] ) ) {
            $rate_ids = array_merge( $rate_ids, array_keys( $taxes['total'] ) );
        }
        if ( ! empty( $taxes['subtotal'] ) && is_array( $taxes['subtotal'] ) ) {
            $rate_ids = array_merge( $rate_ids, array_keys( $taxes['subtotal'] ) );
        }
        $rate_ids = array_unique( array_filter( $rate_ids, 'strlen' ) );

        $max_rate_percent = 0;

        // Try to get rate from WooCommerce tax rate IDs (standard WooCommerce Tax)
        if ( ! empty( $rate_ids ) ) {
            foreach ( $rate_ids as $rate_id ) {
                if ( class_exists( 'WC_Tax' ) && method_exists( 'WC_Tax', 'get_rate_percent' ) ) {
                    $rate_percent     = \WC_Tax::get_rate_percent( $rate_id );
                    $rate_percent     = (float) str_replace( '%', '', (string) $rate_percent );
                    $max_rate_percent = max( $max_rate_percent, $rate_percent );
                }
            }
        }

        // Fallback: Calculate from actual amounts (for Stripe Tax and other providers)
        if ( $max_rate_percent === 0 ) {
            $line_total = abs( (float) $item->get_total() );
            $line_tax   = method_exists( $item, 'get_total_tax' ) ? abs( (float) $item->get_total_tax() ) : 0.0;
            
            if ( $line_total > 0 && $line_tax > 0 ) {
                // Calculate effective tax rate: (tax / total_ex_tax) * 100
                $max_rate_percent = ( $line_tax / $line_total ) * 100;
                self::log('info', 'calculated vat from amounts', [
                    'stage'      => 'vat_calculation',
                    'item'       => $item->get_name(),
                    'line_total' => $line_total,
                    'line_tax'   => $line_tax,
                    'rate'       => round( $max_rate_percent, 2 )
                ]);
            }
        }

        // Map rate to Fiken VAT type
        if ( $max_rate_percent >= 24 ) {
            return 'HIGH';
        } elseif ( $max_rate_percent >= 14 ) {
            return 'LOW';
        } elseif ( $max_rate_percent >= 1 ) {
            // If there's any tax but less than 14%, still treat as taxable (LOW)
            return 'LOW';
        } else {
            return 'EXEMPT';
        }
    }

    /**
     * Aggregate identical line items by key.
     * Expects unit_price and discount in cents/øre.
     */
    private static function aggregate_lines( array $items ): array {
        $map = [];
        foreach ( $items as $li ) {
            $product_id   = isset( $li['product_id'] ) ? (int) $li['product_id'] : 0;
            $variation_id = isset( $li['variation_id'] ) ? (int) $li['variation_id'] : 0;
            $unit_price   = isset( $li['unit_price'] ) ? (int) $li['unit_price'] : 0;
            $vat_type     = isset( $li['vatType'] ) ? (string) $li['vatType'] : 'NONE';
            $sku          = isset( $li['sku'] ) ? (string) $li['sku'] : '';
            $type         = isset( $li['type'] ) ? (string) $li['type'] : '';
            $quantity     = isset( $li['quantity'] ) ? (float) $li['quantity'] : 1.0;
            $discount_tot = isset( $li['discount'] ) ? (int) $li['discount'] : 0;

            $per_unit_discount = ( $quantity > 0 ) ? (int) round( $discount_tot / $quantity ) : 0;

            $key = implode( '|', [
                (string) $product_id,
                (string) $variation_id,
                (string) $unit_price,
                (string) $vat_type,
                (string) $per_unit_discount,
                (string) $sku,
                (string) $type,
            ] );

            if ( ! isset( $map[ $key ] ) ) {
                $map[ $key ] = $li;
            } else {
                $map[ $key ]['quantity'] = (float) $map[ $key ]['quantity'] + (float) $quantity;
                $map[ $key ]['discount'] = (int) $map[ $key ]['discount'] + (int) $discount_tot;
            }
        }
        return array_values( $map );
    }

    /** Generate a UUID v4-like string without external deps */
    private static function generate_uuid_v4(): string {
        $data = random_bytes( 16 );
        $data[6] = chr( ( ord( $data[6] ) & 0x0f ) | 0x40 );
        $data[8] = chr( ( ord( $data[8] ) & 0x3f ) | 0x80 );
        $hex = bin2hex( $data );
        return sprintf( '%s-%s-%s-%s-%s',
            substr( $hex, 0, 8 ),
            substr( $hex, 8, 4 ),
            substr( $hex, 12, 4 ),
            substr( $hex, 16, 4 ),
            substr( $hex, 20, 12 )
        );
    }

    /* ===================== Helpers (logging, HPOS & utils) ===================== */

    /** @var float[] */
    private static $timers = [];
    /** @var string */
    private static $token_source = '';

    private static function is_hpos_enabled(): bool {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
            return (bool) \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    /**
     * Lagrer invoice-id / number HPOS-sikkert (Woo CRUD) + fallback til post_meta.
     * Returnerer "wc_crud_and_post_meta" eller "post_meta_only".
     */
    private static function store_invoice_meta( \WC_Order $order, int $invoice_id, string $invoice_number = '' ): string {
        $mode = 'post_meta_only';

        try {
            // Woo CRUD (HPOS-sikker)
            $order->update_meta_data('_fiken_invoice_id', $invoice_id);
            $order->update_meta_data('fiken_invoice_id',  $invoice_id);
            if ($invoice_number !== '') {
                $order->update_meta_data('_fiken_invoice_number', $invoice_number);
                $order->update_meta_data('fiken_invoice_number',  $invoice_number);
            }
            $order->save();
            $mode = 'wc_crud';
        } catch ( \Throwable $e ) {
            self::log('warning','WC CRUD save failed, fallback to post_meta',['stage'=>'meta_save_fallback','err'=>$e->getMessage()]);
        }

        // Fallback / legacy (kan hjelpe plugins som kun leser post_meta)
        update_post_meta( $order->get_id(), '_fiken_invoice_id', $invoice_id );
        update_post_meta( $order->get_id(), 'fiken_invoice_id',  $invoice_id );
        if ($invoice_number !== '') {
            update_post_meta( $order->get_id(), '_fiken_invoice_number', $invoice_number );
            update_post_meta( $order->get_id(), 'fiken_invoice_number',  $invoice_number );
        }

        return $mode === 'wc_crud' ? 'wc_crud_and_post_meta' : 'post_meta_only';
    }

    private static function derive_contact_name($order, array $billing): string {
        $name = trim( ($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '') );
        if ($name !== '') return $name;

        $ship = trim( (string)$order->get_shipping_first_name() . ' ' . (string)$order->get_shipping_last_name() );
        if ($ship !== '') return $ship;

        if (!empty($billing['company'])) return (string)$billing['company'];

        $email = (string) ($billing['email'] ?? '');
        if ($email !== '') return preg_replace('/@.*/','',$email);

        return 'Woo Customer #' . (int)$order->get_id();
    }

    private static function summarize_items(array $items, bool $is_refund=false): array {
        $count = count($items);
        $qty_sum = 0;
        $sum_amount = 0;
        $sum_discount = 0;
        $vat_types = [];
        $types = [];
        foreach ($items as $li) {
            $qty = (float)($li['quantity'] ?? 0);
            $qty_sum += $qty;
            $sum_amount += (int)($li['unit_price'] ?? 0) * $qty;
            $sum_discount += (int)($li['discount'] ?? 0);
            $vt = (string)($li['vatType'] ?? 'NONE');
            $vat_types[$vt] = ($vat_types[$vt] ?? 0) + 1;
            $t = (string)($li['type'] ?? '');
            if ($t !== '') $types[$t] = ($types[$t] ?? 0) + 1;
        }
        $sample = [];
        if ($count>0) {
            $first = $items[0];
            $sample[] = [
                'name' => (string)($first['name'] ?? ''),
                'qty'  => (float)($first['quantity'] ?? 0),
                'unit_price' => (int)($first['unit_price'] ?? 0),
                'discount'   => (int)($first['discount'] ?? 0),
                'vatType'    => (string)($first['vatType'] ?? 'NONE'),
                'type'       => (string)($first['type'] ?? ''),
                'sku'        => (string)($first['sku'] ?? ''),
            ];
        }
        return [
            'count'=>$count,
            'qty_sum'=>$qty_sum,
            'sum_amount_cents'=>$sum_amount,
            'sum_discount_cents'=>$sum_discount,
            'vat_types'=>$vat_types,
            'types'=>array_keys($types),
            'sample'=>$sample,
        ];
    }

    private static function get_employee_token(): string {
        $employee_token = (string) get_option( 'pekifiken_employee_token', '' );
        self::$token_source = 'pekifiken_employee_token';
        if ( '' === $employee_token ) {
            $employee_token = (string) get_option( 'fiken_employee_token', '' );
            self::$token_source = 'fiken_employee_token';
            if ( '' === $employee_token ) {
                $employee_token = (string) get_option( 'wfb_employee_token', '' );
                self::$token_source = 'wfb_employee_token';
            }
        }
        return $employee_token;
    }

	/**
	 * Fetch invoice PDF from backend and store as media attachment, linking to order.
	 *
	 * @param \WC_Order $order
	 * @param int       $invoice_id
	 * @param string    $invoice_number
	 * @return void
	 */
	private static function maybe_store_invoice_pdf( \WC_Order $order, int $invoice_id, string $invoice_number = '' ): void {
		// Guard: need token
		$employee_token = self::get_employee_token();
		if ( $employee_token === '' ) {
			return;
		}
		// Call API
		require_once plugin_dir_path( __FILE__ ) . 'class-fiken-api.php';
		$api  = new Fiken_API( $employee_token );
		$res  = $api->get_invoice_pdf( $invoice_id );
		if ( is_wp_error( $res ) ) {
			return;
		}
		$body = isset( $res['body'] ) && is_array( $res['body'] ) ? $res['body'] : array();
		if ( empty( $body['base64'] ) ) {
			return;
		}
		$filename = sanitize_file_name( (string) ( $body['filename'] ?? ( 'invoice_' . $invoice_id . '.pdf' ) ) );
		$mime     = (string) ( $body['mime'] ?? 'application/pdf' );
		$pdf      = base64_decode( (string) $body['base64'] );
		if ( ! is_string( $pdf ) || $pdf === '' ) {
			return;
		}

		// Store in uploads
		$upload = wp_upload_bits( $filename, null, $pdf );
		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return;
		}

		// Create attachment
		$filetype = wp_check_filetype( $upload['file'], null );
		$attachment = array(
			'post_mime_type' => $filetype['type'] ? $filetype['type'] : $mime,
			'post_title'     => $invoice_number !== '' ? ( 'Fiken Invoice ' . $invoice_number ) : ( 'Fiken Invoice ' . $invoice_id ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attach_id = wp_insert_attachment( $attachment, $upload['file'], $order->get_id() );
		if ( is_wp_error( $attach_id ) || ! $attach_id ) {
			return;
		}

		// Generate and save attachment metadata
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Link to order
		update_post_meta( $order->get_id(), '_fiken_invoice_pdf_id', (int) $attach_id );
		$url = wp_get_attachment_url( $attach_id );
		$note = sprintf(
			/* translators: 1: attachment id */
			__( 'Fiken: Invoice PDF saved to Media Library (attachment #%1$d).', 'peki-fiken-integration-for-woocommerce' ),
			$attach_id
		);
		if ( is_string( $url ) && $url !== '' ) {
			$note .= ' <a class="button button-small" href="' . esc_url( $url ) . '" download target="_blank" rel="noopener noreferrer">' . esc_html__( 'Download PDF', 'peki-fiken-integration-for-woocommerce' ) . '</a>';
		}
		$order->add_order_note( $note );
	}

    private static function mask(string $s, int $head=4, int $tail=3): string {
        $len = strlen($s);
        if ($len <= $head + $tail) return str_repeat('*',$len);
        return substr($s,0,$head) . '…' . substr($s,-$tail);
    }

    private static function bool_option(string $key, bool $default=false): bool {
        return (bool) get_option($key, $default);
    }

    /** Unified logger (Woo logger if mulig, ellers error_log) */
    private static function log(string $level, string $message, array $context = []): void {
        // Start timer ved første kall i en runde
        if (!isset(self::$timers['begin'])) {
            self::$timers['begin'] = microtime(true);
        }
        $json = $context ? ' | ' . ( function_exists('wp_json_encode') ? wp_json_encode($context) : json_encode($context) ) : '';
        if ( function_exists('wc_get_logger') ) {
            $logger = wc_get_logger();
            switch ($level) {
                case 'error':   $logger->error(   $message . $json, ['source'=>'peki-fiken']); break;
                case 'warning': $logger->warning( $message . $json, ['source'=>'peki-fiken']); break;
                case 'notice':  $logger->notice(  $message . $json, ['source'=>'peki-fiken']); break;
                default:        $logger->info(    $message . $json, ['source'=>'peki-fiken']); break;
            }
        } else {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( strtoupper($level) . ' ' . $message . $json );
        }

        if ( 'error' === $level && ! empty( $context['order_id'] ) && function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( (int) $context['order_id'] );
            if ( $order && method_exists( $order, 'add_order_note' ) ) {
                $note = sprintf(
                    /* translators: %s: error message */
                    __( 'Fiken error: %s', 'peki-fiken-integration-for-woocommerce' ),
                    $message
                );
                $order->add_order_note( $note );
            }
        }
    }

    private static function since_ms(string $key): int {
        if (!isset(self::$timers[$key])) return 0;
        return (int) round( (microtime(true) - self::$timers[$key]) * 1000 );
    }
}
