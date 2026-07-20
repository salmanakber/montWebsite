<?php
get_header(); ?>

<div class="woocommerce-category-page">
    <header class="woocommerce-products-header">
        <?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
            <h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
        <?php endif; ?>

        <?php do_action( 'woocommerce_archive_description' ); ?>
    </header>

    <?php if ( have_posts() ) : ?>

        <div class="woocommerce-products">
            <?php woocommerce_product_loop_start(); ?>

            <?php while ( have_posts() ) : the_post(); ?>
                <?php wc_get_template_part( 'content', 'product' ); ?>
            <?php endwhile; ?>

            <?php woocommerce_product_loop_end(); ?>
        </div>

        <?php woocommerce_pagination(); ?>

    <?php else : ?>
        <?php wc_no_products_found(); ?>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
