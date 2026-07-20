<?php
/**
 * Admin new order email - Master Design (Thin & Wide)
 *
 * @package WooCommerce\Templates\Emails\HTML
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

// 1. SAFETY CHECKS
if ( ! is_object( $order ) ) { return; }

// 2. MASTER PALETTE
$c_navy    = '#1b3359'; // Brand Color
$c_green   = '#77a464'; // Accent / Success
$c_text    = '#444444'; // Soft Black (Not harsh #000)
$c_label   = '#888888'; // Thin Gray Labels
$c_bg      = '#f2f4f7'; // Premium Dashboard Background
$c_white   = '#ffffff';
$c_divider = '#eaeaea';

// 3. DATA
$order_date = $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : date_i18n( get_option( 'date_format' ), strtotime( $order->get_date_created() ) );
?>

<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: <?php echo $c_bg; ?>; }
        table { border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        /* Typography Helper */
        .thin-text { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-weight: 300; }
        .med-text  { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-weight: 500; }
    </style>
</head>
<body style="background-color: <?php echo $c_bg; ?>; margin: 0; padding: 0;">

    <!-- OUTER WRAPPER -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: <?php echo $c_bg; ?>; padding: 40px 0;">
        <tr>
            <td align="center">
                
                <!-- MAIN CARD (800px Width) -->
                <table border="0" cellpadding="0" cellspacing="0" width="800" style="background-color: <?php echo $c_white; ?>; max-width: 800px; width: 800px; margin: 0 auto; box-shadow: 0 10px 25px rgba(27, 51, 89, 0.05); border-radius: 4px; overflow: hidden;">
                    
                    <!-- HEADER BAR -->
                    <tr>
                        <td style="background-color: <?php echo $c_navy; ?>; padding: 30px 50px;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left">
                                        <h1 style="color: <?php echo $c_white; ?>; margin: 0; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 300; font-size: 24px; letter-spacing: 0.5px;">
                                            New Order Received
                                        </h1>
                                    </td>
                                    <td align="right">
                                        <span style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 700; font-size: 18px; color: <?php echo $c_green; ?>; background: rgba(255,255,255,0.05); padding: 5px 15px; border-radius: 4px;">
                                            #<?php echo $order->get_order_number(); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- MAIN CONTENT -->
                    <tr>
                        <td style="padding: 50px;">

                            <!-- INTRO -->
                            <div style="margin-bottom: 40px; border-bottom: 1px solid <?php echo $c_divider; ?>; padding-bottom: 20px;">
                                <table width="100%">
                                    <tr>
                                        <td style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 300; font-size: 15px; color: <?php echo $c_text; ?>;">
                                            Order placed by <strong><?php echo $order->get_formatted_billing_full_name(); ?></strong> on <?php echo esc_html( $order_date ); ?>.
                                        </td>
                                        <td align="right">
                                            <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ); ?>" 
                                               style="background-color: <?php echo $c_navy; ?>; color: #ffffff; text-decoration: none; padding: 8px 20px; font-size: 12px; border-radius: 3px; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 500;">
                                                ADMIN ACCESS
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- PRODUCTS TABLE HEAD -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th align="left" style="padding-bottom: 10px; border-bottom: 1px solid <?php echo $c_navy; ?>; font-family: 'Helvetica Neue', Helvetica, sans-serif; color: <?php echo $c_navy; ?>; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;">Product Specification</th>
                                        <th align="center" style="padding-bottom: 10px; border-bottom: 1px solid <?php echo $c_navy; ?>; font-family: 'Helvetica Neue', Helvetica, sans-serif; color: <?php echo $c_navy; ?>; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600; width: 80px;">Qty</th>
                                        <th align="right" style="padding-bottom: 10px; border-bottom: 1px solid <?php echo $c_navy; ?>; font-family: 'Helvetica Neue', Helvetica, sans-serif; color: <?php echo $c_navy; ?>; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600; width: 120px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $items = $order->get_items(); 
                                    if ( $items ) :
                                        foreach ( $items as $item_id => $item ) : 
                                            ?>
                                            <tr>
                                                <td style="padding: 25px 0; border-bottom: 1px solid <?php echo $c_divider; ?>; vertical-align: top;">
                                                    
                                                    <!-- PRODUCT NAME -->
                                                    <span style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 16px; color: <?php echo $c_navy; ?>; font-weight: 400;">
                                                        <?php echo $item->get_name(); ?>
                                                    </span>

                                                    <!-- META DATA (The Clean Master Design) -->
                                                    <?php
                                                    if ( $meta_data = $item->get_formatted_meta_data( '' ) ) {
                                                        ?>
                                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 12px;">
                                                        <?php
                                                        foreach ( $meta_data as $meta_id => $meta ) {
                                                            $key = wp_kses_post( $meta->display_key );
                                                            $val = wp_kses_post( strip_tags( $meta->display_value ) );
                                                            ?>
                                                            <tr>
                                                                <td width="15" valign="top" style="padding: 3px 0;">
                                                                    <span style="color: <?php echo $c_green; ?>; font-size: 14px;">&rsaquo;</span>
                                                                </td>
                                                                <td width="120" valign="top" style="padding: 3px 0;">
                                                                    <span style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 13px; color: <?php echo $c_label; ?>; font-weight: 300; text-transform: uppercase; letter-spacing: 1px;">
                                                                        <?php echo $key; ?>
                                                                    </span>
                                                                </td>
                                                                <td valign="top" style="padding: 3px 0;">
                                                                    <span style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 13px; color: <?php echo $c_text; ?>; font-weight: 400;">
                                                                        <?php echo $val; ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                        ?>
                                                        </table>
                                                        <?php
                                                    }
                                                    ?>
                                                </td>

                                                <!-- QTY -->
                                                <td align="center" style="padding: 25px 0; border-bottom: 1px solid <?php echo $c_divider; ?>; vertical-align: top; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 15px; color: <?php echo $c_text; ?>; font-weight: 300;">
                                                    <?php echo $item->get_quantity(); ?>
                                                </td>

                                                <!-- PRICE -->
                                                <td align="right" style="padding: 25px 0; border-bottom: 1px solid <?php echo $c_divider; ?>; vertical-align: top; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 15px; color: <?php echo $c_text; ?>; font-weight: 300;">
                                                    <?php echo $order->get_formatted_line_subtotal( $item ); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; 
                                    endif; 
                                    ?>
                                </tbody>
                            </table>

                            <!-- TOTALS SECTION (Right Aligned) -->
                            <div style="margin-top: 20px; padding-left: 50%;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <?php
                                    if ( method_exists( $order, 'get_order_item_totals' ) ) {
                                        $totals = $order->get_order_item_totals();
                                        if ( $totals ) {
                                            foreach ( $totals as $total_key => $total ) {
                                                $is_total = ( $total_key === 'order_total' || $total_key === 'total' );
                                                
                                                if ( $is_total ) {
                                                    // GRAND TOTAL STYLE
                                                    ?>
                                                    <tr>
                                                        <td colspan="2" style="border-bottom: 2px solid <?php echo $c_navy; ?>; padding-bottom: 10px; padding-top: 15px;"></td>
                                                    </tr>
                                                    <tr>
                                                        <td align="left" style="padding-top: 15px; font-family: 'Helvetica Neue', Helvetica, sans-serif; color: <?php echo $c_navy; ?>; font-size: 16px; font-weight: 300;">
                                                            Total
                                                        </td>
                                                        <td align="right" style="padding-top: 15px; font-family: 'Helvetica Neue', Helvetica, sans-serif; color: <?php echo $c_green; ?>; font-size: 24px; font-weight: 700;">
                                                            <?php echo $total['value']; ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                } else {
                                                    // NORMAL TOTALS STYLE
                                                    ?>
                                                    <tr>
                                                        <td align="left" style="padding: 5px 0; font-family: 'Helvetica Neue', Helvetica, sans-serif; color: <?php echo $c_label; ?>; font-size: 13px; font-weight: 300;">
                                                            <?php echo $total['label']; ?>
                                                        </td>
                                                        <td align="right" style="padding: 5px 0; font-family: 'Helvetica Neue', Helvetica, sans-serif; color: <?php echo $c_text; ?>; font-size: 13px; font-weight: 400;">
                                                            <?php echo $total['value']; ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </table>
                            </div>

                            <!-- CUSTOMER NOTE (Thin Box) -->
                            <?php if ( $order->get_customer_note() ) : ?>
                                <div style="margin-top: 40px; border-left: 2px solid <?php echo $c_navy; ?>; background-color: #fafafa; padding: 20px;">
                                    <h4 style="margin: 0 0 5px 0; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: <?php echo $c_navy; ?>; font-weight: 600;">Customer Note</h4>
                                    <p style="margin: 0; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 14px; font-weight: 300; color: <?php echo $c_text; ?>; font-style: italic;">
                                        "<?php echo wp_kses_post( $order->get_customer_note() ); ?>"
                                    </p>
                                </div>
                            <?php endif; ?>

                        </td>
                    </tr>
                    
                    <!-- FOOTER / ADDRESS (Gray Area) -->
                    <tr>
                        <td style="background-color: #fcfcfc; padding: 40px 50px; border-top: 1px solid <?php echo $c_divider; ?>;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <!-- Billing -->
                                    <td valign="top" width="50%" style="padding-right: 20px;">
                                        <div style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: <?php echo $c_label; ?>; margin-bottom: 10px;">Billing Address</div>
                                        <div style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 14px; font-weight: 300; color: <?php echo $c_text; ?>; line-height: 1.6;">
                                            <?php echo $order->get_formatted_billing_address() ? $order->get_formatted_billing_address() : 'No address set.'; ?>
                                        </div>
                                        
                                        <div style="margin-top: 15px;">
                                            <?php if ( $order->get_billing_email() ) : ?>
                                                <div style="margin-bottom: 5px;">
                                                    <a href="mailto:<?php echo $order->get_billing_email(); ?>" style="color: <?php echo $c_navy; ?>; text-decoration: none; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 300; border-bottom: 1px dotted <?php echo $c_navy; ?>;">
                                                        <?php echo $order->get_billing_email(); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ( $order->get_billing_phone() ) : ?>
                                                <div style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 300; color: <?php echo $c_label; ?>;">
                                                    <?php echo $order->get_billing_phone(); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Shipping -->
                                    <?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() ) : ?>
                                    <td valign="top" width="50%" style="padding-left: 20px; border-left: 1px solid <?php echo $c_divider; ?>;">
                                        <div style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: <?php echo $c_label; ?>; margin-bottom: 10px;">Shipping Address</div>
                                        <div style="font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 14px; font-weight: 300; color: <?php echo $c_text; ?>; line-height: 1.6;">
                                            <?php echo $order->get_formatted_shipping_address() ? $order->get_formatted_shipping_address() : 'Same as billing'; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
                <!-- END MAIN CARD -->

                <!-- COPYRIGHT -->
                <div style="padding-top: 20px; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 11px; color: <?php echo $c_label; ?>; font-weight: 300;">
                    &copy; <?php echo date('Y'); ?> <?php echo get_option( 'blogname' ); ?>
                </div>

            </td>
        </tr>
    </table>

</body>
</html>