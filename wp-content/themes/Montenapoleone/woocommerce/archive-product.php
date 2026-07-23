<?php
/**
 * Product archives / category pages.
 *
 * @package Montenapoleone
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Build category tab terms for the current archive.
 *
 * Prefers child categories; otherwise siblings under the same parent.
 *
 * @param WP_Term $current Current term.
 * @return WP_Term[]
 */
function mont_archive_category_tabs( $current ) {
	if ( ! $current || empty( $current->term_id ) ) {
		return array();
	}

	$children = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => (int) $current->term_id,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
		return $children;
	}

	$parent_id = (int) $current->parent;
	if ( $parent_id > 0 ) {
		$siblings = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent_id,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( ! is_wp_error( $siblings ) && ! empty( $siblings ) ) {
			return $siblings;
		}
	}

	// Fallback: known shirt parents (covers /product-category/skjorter-herre/ etc).
	$known_parents = array(
		'skjorter-herre',
		'herre-skjorter',
		'herre-fritids-skjorter',
		'herre-formelle-skjorter',
		'linskjorter',
		'flanell-skjorter',
		'oxford-skjorter',
	);

	foreach ( $known_parents as $slug ) {
		$parent = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			continue;
		}
		$kids = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => (int) $parent->term_id,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( ! is_wp_error( $kids ) && ! empty( $kids ) ) {
			// If current is this parent or one of its children, use these tabs.
			if ( (int) $current->term_id === (int) $parent->term_id || (int) $current->parent === (int) $parent->term_id ) {
				return $kids;
			}
		}
	}

	return array();
}

do_action( 'woocommerce_before_main_content' );

$current_cat = is_product_category() ? get_queried_object() : null;
$tab_terms   = $current_cat ? mont_archive_category_tabs( $current_cat ) : array();
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

					<?php if ( ! empty( $tab_terms ) ) : ?>
						<nav class="mont-cat-tabs" aria-label="<?php esc_attr_e( 'Product categories', 'montenapoleone' ); ?>">
							<div class="mont-cat-tabs__scroller">
								<ul class="mont-cat-tabs__list">
									<?php foreach ( $tab_terms as $term ) : ?>
										<?php
										$is_active = $current_cat && (int) $current_cat->term_id === (int) $term->term_id;
										$link      = get_term_link( $term );
										if ( is_wp_error( $link ) ) {
											continue;
										}
										?>
										<li class="mont-cat-tabs__item <?php echo $is_active ? 'is-active' : ''; ?>">
											<a class="mont-cat-tabs__link <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $link ); ?>">
												<?php echo esc_html( $term->name ); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</nav>
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
