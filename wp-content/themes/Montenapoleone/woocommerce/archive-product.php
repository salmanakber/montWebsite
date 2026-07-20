<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );
// Get the current category

?>

<div class="main-shop pd120 ">
<div class="container">
    <div class="row">
        <div class="col-md-12">
<header class="woocommerce-products-header">
<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
	<?php if ( is_product_category( 'trousers' ) ){ ?>
		<h1 class="woocommerce-products-header__title page-title"> Coming Soon </h1>
	<?php } else {?>
		<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
	<?php } ?>
<?php endif; ?>
	<div class="sub-cats">
<?php

// Array of parent category slugs
$parent_slugs = array('herre-fritids-skjorter', 'linskjorter', 'flanell-skjorter', 'oxford-skjorter');
$parent2_slugs = array('herre-skjorter',  'herre-formelle-skjorter');

// Function to display subcategories based on the current URL
function display_subcategories_based_on_url($parent_slugs) {
    // Get the current queried object
    $current_cat = get_queried_object();

    if ($current_cat && isset($current_cat->slug)) {
        // Check if the current URL matches any parent slug
        foreach ($parent_slugs as $parent_slug) {
            if ($current_cat->slug === $parent_slug) {
                // Get the parent category
                $parent_cat = get_term_by('slug', $parent_slug, 'product_cat');

                if ($parent_cat) {
                    // Get child categories
                    $subcategories = get_terms(array(
                        'taxonomy' => 'product_cat',
                        'child_of' => $parent_cat->term_id,
                        'hide_empty' => false, // Set to true if you want to hide empty subcategories
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));

                    // Display the subcategories
//                     echo '<a class="all">All</a>';
                    foreach ($subcategories as $subcategory) {
                        $active_style = ($current_cat->slug == $subcategory->slug) ? 'color: #454fcb;text-decoration: underline;' : 'color: #7b7a7a;';
                        echo '<a href="/product-category/' . $subcategory->slug . '" style="' . $active_style . '">' . $subcategory->name . '</a>';
                    }
                }
                break; // Stop once the relevant parent category is found
            }
        }
    }
}

// Call the function for both parent slugs arrays
display_subcategories_based_on_url($parent_slugs);
display_subcategories_based_on_url($parent2_slugs);

		

	/**
	 * Hook: woocommerce_archive_description.
	 *
	 * @hooked woocommerce_taxonomy_archive_description - 10
	 * @hooked woocommerce_product_archive_description - 10
	 */
	do_action( 'woocommerce_archive_description' );
	?>
		</div>
</header>

<?php
if ( woocommerce_product_loop() ) {

	/**
	 * Hook: woocommerce_before_shop_loop.
	 *
	 * @hooked woocommerce_output_all_notices - 10
	 * @hooked woocommerce_result_count - 20
	 * @hooked woocommerce_catalog_ordering - 30
	 */
	do_action( 'woocommerce_before_shop_loop' );

	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'total' ) ) {
		while ( have_posts() ) {
			the_post();

			/**
			 * Hook: woocommerce_shop_loop.
			 */
			do_action( 'woocommerce_shop_loop' );

			wc_get_template_part( 'content', 'product' );
		}
	}

	woocommerce_product_loop_end();

	/**
	 * Hook: woocommerce_after_shop_loop.
	 *
	 * @hooked woocommerce_pagination - 10
	 */
	do_action( 'woocommerce_after_shop_loop' );
} else {
	/**
	 * Hook: woocommerce_no_products_found.
	 *
	 * @hooked wc_no_products_found - 10
	 */
	do_action( 'woocommerce_no_products_found' );
}

/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
do_action( 'woocommerce_sidebar' );
    
    ?>
</div>
    </div>
</div>
</div>
<?php

get_footer( 'shop' );
?>

