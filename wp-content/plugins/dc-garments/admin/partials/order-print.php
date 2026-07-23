<?php
/**
 * Production print sheet for B2B or B2C order.
 *
 * @package DC_Product_Manager
 * @var array|null $print_order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $print_order ) ) {
	echo '<p>' . esc_html__( 'Order not found.', 'dc-product-manager' ) . '</p>';
	return;
}

$channel_label = 'b2b' === $print_order['channel']
	? __( 'B2B Wholesale', 'dc-product-manager' )
	: __( 'B2C Retail', 'dc-product-manager' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( sprintf( __( 'Production · Order %s', 'dc-product-manager' ), $print_order['number'] ) ); ?></title>
	<style>
		body { font-family: Georgia, "Times New Roman", serif; color: #111; margin: 0; background: #f4f4f4; }
		.sheet { max-width: 900px; margin: 24px auto; background: #fff; padding: 32px 36px; box-shadow: 0 1px 6px rgba(0,0,0,.08); }
		.toolbar { display: flex; gap: 12px; justify-content: flex-end; margin-bottom: 20px; }
		.toolbar button, .toolbar a {
			font-family: system-ui, sans-serif; font-size: 14px; padding: 8px 14px;
			border: 1px solid #ccc; background: #fff; border-radius: 6px; cursor: pointer; text-decoration: none; color: #111;
		}
		.toolbar .primary { background: #1f4e79; color: #fff; border-color: #1f4e79; }
		h1 { font-size: 28px; margin: 0 0 6px; }
		.meta { font-family: system-ui, sans-serif; font-size: 13px; color: #444; line-height: 1.5; }
		.badge {
			display: inline-block; font-family: system-ui, sans-serif; font-size: 11px; font-weight: 700;
			letter-spacing: .04em; padding: 4px 10px; border-radius: 999px; margin-bottom: 12px;
		}
		.badge-b2b { background: #1f4e79; color: #fff; }
		.badge-b2c { background: #166534; color: #fff; }
		hr { border: 0; border-top: 1px solid #ddd; margin: 20px 0; }
		h2 { font-size: 18px; margin: 0 0 10px; }
		.item { border: 1px solid #e5e5e5; border-radius: 8px; padding: 14px 16px; margin-bottom: 12px; }
		.item h3 { margin: 0 0 8px; font-size: 16px; }
		.item ul { margin: 0; padding-left: 18px; font-family: system-ui, sans-serif; font-size: 13px; }
		.note {
			margin-top: 24px; padding: 14px 16px; background: #fff8e7; border: 1px solid #f0d78c;
			font-family: system-ui, sans-serif; font-size: 13px;
		}
		@media print {
			body { background: #fff; }
			.sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
			.toolbar { display: none !important; }
		}
	</style>
</head>
<body>
	<div class="sheet">
		<div class="toolbar">
			<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'orders', 'channel' => $print_order['channel'] ), home_url( '/crm/' ) ) ); ?>">
				<?php esc_html_e( 'Back to portal', 'dc-product-manager' ); ?>
			</a>
			<button type="button" class="primary" onclick="window.print()"><?php esc_html_e( 'Print for production', 'dc-product-manager' ); ?></button>
		</div>

		<span class="badge badge-<?php echo esc_attr( $print_order['channel'] ); ?>"><?php echo esc_html( $channel_label ); ?></span>
		<h1><?php echo esc_html( sprintf( __( 'Order #%s', 'dc-product-manager' ), $print_order['number'] ) ); ?></h1>
		<div class="meta">
			<div><strong><?php esc_html_e( 'Date:', 'dc-product-manager' ); ?></strong> <?php echo esc_html( $print_order['date'] ); ?></div>
			<div><strong><?php esc_html_e( 'Customer:', 'dc-product-manager' ); ?></strong> <?php echo esc_html( $print_order['customer'] ); ?></div>
			<?php if ( ! empty( $print_order['company'] ) ) : ?>
				<div><strong><?php esc_html_e( 'Company:', 'dc-product-manager' ); ?></strong> <?php echo esc_html( $print_order['company'] ); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $print_order['email'] ) ) : ?>
				<div><strong><?php esc_html_e( 'Email:', 'dc-product-manager' ); ?></strong> <?php echo esc_html( $print_order['email'] ); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $print_order['customer_raw']['deliveryaddress'] ) ) : ?>
				<div><strong><?php esc_html_e( 'Delivery:', 'dc-product-manager' ); ?></strong> <?php echo esc_html( $print_order['customer_raw']['deliveryaddress'] ); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $print_order['customer_raw']['mobilenumber'] ) ) : ?>
				<div><strong><?php esc_html_e( 'Phone:', 'dc-product-manager' ); ?></strong> <?php echo esc_html( $print_order['customer_raw']['mobilenumber'] ); ?></div>
			<?php endif; ?>
			<div><strong><?php esc_html_e( 'Status:', 'dc-product-manager' ); ?></strong> <?php echo esc_html( $print_order['status'] ); ?></div>
			<div><strong><?php esc_html_e( 'Total:', 'dc-product-manager' ); ?></strong> <?php echo wp_kses_post( $print_order['total'] ); ?></div>
		</div>

		<hr>

		<h2><?php esc_html_e( 'Production instructions', 'dc-product-manager' ); ?></h2>
		<?php foreach ( $print_order['items'] as $item ) : ?>
			<div class="item">
				<h3>
					<?php echo esc_html( $item['name'] ); ?>
					— <?php echo esc_html( sprintf( _n( '%d unit', '%d units', (int) $item['qty'], 'dc-product-manager' ), (int) $item['qty'] ) ); ?>
					<?php if ( ! empty( $item['sku'] ) ) : ?>
						<span style="font-weight:400;font-size:13px;">(SKU: <?php echo esc_html( $item['sku'] ); ?>)</span>
					<?php endif; ?>
				</h3>
				<?php if ( ! empty( $item['meta'] ) ) : ?>
					<ul>
						<?php foreach ( $item['meta'] as $meta ) : ?>
							<li><strong><?php echo esc_html( $meta['label'] ); ?>:</strong> <?php echo esc_html( $meta['value'] ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="meta"><?php esc_html_e( 'No custom options recorded.', 'dc-product-manager' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="note">
			<strong><?php esc_html_e( 'For production manager:', 'dc-product-manager' ); ?></strong>
			<?php esc_html_e( 'Please produce exactly as specified above (fit, size, collar, cuff, and size breakdowns). Mark this sheet when cutting and sewing are complete.', 'dc-product-manager' ); ?>
		</div>
	</div>
	<script>
		window.addEventListener('load', function () {
			if (window.location.search.indexOf('autoprint=1') !== -1) {
				window.print();
			}
		});
	</script>
</body>
</html>
<?php
// Stop theme chrome around print view.
exit;
