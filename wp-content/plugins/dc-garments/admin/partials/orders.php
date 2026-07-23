<?php
/**
 * CRM Orders portal — B2C + B2B tabs, badges, production print.
 *
 * @package DC_Product_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! \DC_Product_Manager\Order_Portal::can_access() ) {
	echo '<p>' . esc_html__( 'You do not have permission to view orders.', 'dc-product-manager' ) . '</p>';
	return;
}

$portal  = new \DC_Product_Manager\Order_Portal();
$channel = isset( $_GET['channel'] ) ? sanitize_text_field( wp_unslash( $_GET['channel'] ) ) : 'all';
$orders  = $portal->get_orders( $channel, 120 );
$base    = home_url( '/crm/' );
?>
<div class="dc-orders-portal">
	<div class="dc-orders-portal__header">
		<div>
			<h2><?php esc_html_e( 'Order Portal', 'dc-product-manager' ); ?></h2>
			<p class="dc-orders-portal__intro">
				<?php esc_html_e( 'All shop (B2C) and wholesale (B2B) orders in one place. Print a production sheet for the factory floor.', 'dc-product-manager' ); ?>
			</p>
		</div>
	</div>

	<nav class="dc-orders-tabs" aria-label="<?php esc_attr_e( 'Order channels', 'dc-product-manager' ); ?>">
		<a class="dc-orders-tab <?php echo 'all' === $channel ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'orders', 'channel' => 'all' ), $base ) ); ?>">
			<?php esc_html_e( 'All', 'dc-product-manager' ); ?>
		</a>
		<a class="dc-orders-tab <?php echo 'b2c' === $channel ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'orders', 'channel' => 'b2c' ), $base ) ); ?>">
			<span class="dc-channel-badge dc-channel-badge--b2c">B2C</span>
			<?php esc_html_e( 'Retail shop', 'dc-product-manager' ); ?>
		</a>
		<a class="dc-orders-tab <?php echo 'b2b' === $channel ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'orders', 'channel' => 'b2b' ), $base ) ); ?>">
			<span class="dc-channel-badge dc-channel-badge--b2b">B2B</span>
			<?php esc_html_e( 'Wholesale', 'dc-product-manager' ); ?>
		</a>
	</nav>

	<?php if ( empty( $orders ) ) : ?>
		<div class="dc-orders-empty">
			<p><?php esc_html_e( 'No orders found for this tab yet.', 'dc-product-manager' ); ?></p>
			<?php if ( 'b2b' === $channel || 'all' === $channel ) : ?>
				<p class="dc-orders-empty-hint"><?php esc_html_e( 'New B2B orders placed on the Monte B2B portal are saved here automatically.', 'dc-product-manager' ); ?></p>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="dc-orders-table-wrap">
			<table class="dc-orders-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order', 'dc-product-manager' ); ?></th>
						<th><?php esc_html_e( 'Channel', 'dc-product-manager' ); ?></th>
						<th><?php esc_html_e( 'Customer', 'dc-product-manager' ); ?></th>
						<th><?php esc_html_e( 'Date', 'dc-product-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'dc-product-manager' ); ?></th>
						<th><?php esc_html_e( 'Total', 'dc-product-manager' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'dc-product-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $orders as $row ) : ?>
						<tr>
							<td>
								<strong>#<?php echo esc_html( $row['number'] ); ?></strong>
							</td>
							<td>
								<span class="dc-channel-badge dc-channel-badge--<?php echo esc_attr( $row['channel'] ); ?>">
									<?php echo esc_html( strtoupper( $row['channel'] ) ); ?>
								</span>
							</td>
							<td>
								<?php echo esc_html( $row['customer'] ); ?>
								<?php if ( ! empty( $row['company'] ) ) : ?>
									<br><span class="dc-orders-muted"><?php echo esc_html( $row['company'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $row['email'] ) ) : ?>
									<br><span class="dc-orders-muted"><?php echo esc_html( $row['email'] ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $row['date'] ); ?></td>
							<td><?php echo esc_html( $row['status'] ); ?></td>
							<td><?php echo wp_kses_post( $row['total'] ); ?></td>
							<td class="dc-orders-actions">
								<?php if ( ! empty( $row['view_url'] ) ) : ?>
									<a class="dc-orders-details-btn" href="<?php echo esc_url( $row['view_url'] ); ?>" target="_blank" rel="noopener">
										<?php esc_html_e( 'Order details', 'dc-product-manager' ); ?>
									</a>
								<?php endif; ?>
								<a class="dc-orders-print-btn" href="<?php echo esc_url( $row['print_url'] ); ?>" target="_blank" rel="noopener">
									<?php esc_html_e( 'Print sheet', 'dc-product-manager' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
