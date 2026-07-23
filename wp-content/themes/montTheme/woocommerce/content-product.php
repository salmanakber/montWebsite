<?php
/**
 * Product card / category archive loop item.
 * Category tabs render once, then the product grid shortcode.
 *
 * @package Montenapoleone
 */

global $category_slider_displayed;

if ( ! isset( $category_slider_displayed ) || true !== $category_slider_displayed ) {

	$current_category = get_queried_object();

	if ( is_product_category() && isset( $current_category->term_id ) ) {

		$selected_id  = (int) $current_category->term_id;
		$walk         = $current_category;
		$top_level_id = (int) $walk->term_id;

		// Walk up to the top-level parent, then show its children as tabs.
		while ( ! empty( $walk->parent ) ) {
			$walk = get_term( (int) $walk->parent, 'product_cat' );
			if ( ! $walk || is_wp_error( $walk ) ) {
				break;
			}
			$top_level_id = (int) $walk->term_id;
		}

		$categories_to_show = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => $top_level_id,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		// If current IS the top-level and has no children, show sibling top-level shirt cats.
		if ( ( empty( $categories_to_show ) || is_wp_error( $categories_to_show ) ) && 0 === (int) $current_category->parent ) {
			$categories_to_show = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'parent'     => 0,
					'orderby'    => 'name',
					'order'      => 'ASC',
					'number'     => 12,
				)
			);
		}

		if ( ! empty( $categories_to_show ) && ! is_wp_error( $categories_to_show ) ) {
			$slider_id = 'category_slider_' . wp_rand( 1000, 9999 );
			?>
			<nav class="category-slider-container ssds mont-shop-tabs" aria-label="<?php esc_attr_e( 'Product categories', 'montenapoleone' ); ?>">
				<div class="category-slider" id="<?php echo esc_attr( $slider_id ); ?>">
					<?php foreach ( $categories_to_show as $category ) : ?>
						<?php if ( 'Uncategorized' === $category->name ) { continue; } ?>
						<a href="<?php echo esc_url( get_term_link( $category ) ); ?>"
						   class="category-item <?php echo ( (int) $category->term_id === $selected_id ) ? 'category-active' : ''; ?>">
							<?php echo esc_html( str_replace( ' skjorte', ' skjorter', $category->name ) ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</nav>
			<script>
			document.addEventListener('DOMContentLoaded', function () {
				var slider = document.getElementById(<?php echo wp_json_encode( $slider_id ); ?>);
				if (!slider) return;
				var active = slider.querySelector('.category-active');
				if (active && active.scrollIntoView) {
					active.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
				}
			});
			</script>
			<?php
			$category_slider_displayed = true;
		}
	}
}
?>

<div class="mont-product-list-category" style="margin-top: 24px;">
	<?php
	global $mont_slider_displayed;
	if ( ! isset( $mont_slider_displayed ) || true !== $mont_slider_displayed ) {
		$obj = get_queried_object();
		if ( $obj && ! empty( $obj->slug ) ) {
			echo do_shortcode( '[custom_product_grid category="' . esc_attr( $obj->slug ) . '" limit="all"]' );
		}
		$mont_slider_displayed = true;
	}
	?>
</div>
