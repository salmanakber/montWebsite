<?php
/**
 * Product archives / category pages.
 * Category tabs are rendered from content-product.php (once per page).
 *
 * @package Montenapoleone
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );
?>

<div class="main-shop pd120">
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<header class="woocommerce-products-header">
					<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
						<?php if ( is_product_category( 'trousers' ) ) : ?>
							<h1 class="woocommerce-products-header__title page-title"><?php esc_html_e( 'Coming Soon', 'montenapoleone' ); ?></h1>
						<?php else : ?>
							<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
						<?php endif; ?>
					<?php endif; ?>

					<?php do_action( 'woocommerce_archive_description' ); ?>
				</header>

				<?php
				if ( woocommerce_product_loop() ) {
					do_action( 'woocommerce_before_shop_loop' );
					woocommerce_product_loop_start();

					if ( wc_get_loop_prop( 'total' ) ) {
						while ( have_posts() ) {
							the_post();
							do_action( 'woocommerce_shop_loop' );
							wc_get_template_part( 'content', 'product' );
						}
					}

					woocommerce_product_loop_end();
					do_action( 'woocommerce_after_shop_loop' );
				} else {
					// Still show category tabs + empty grid when no products match loop.
					wc_get_template_part( 'content', 'product' );
					do_action( 'woocommerce_no_products_found' );
				}

				do_action( 'woocommerce_after_main_content' );
				do_action( 'woocommerce_sidebar' );
				?>
			</div>
		</div>
	</div>
</div>

<?php
get_footer( 'shop' );
