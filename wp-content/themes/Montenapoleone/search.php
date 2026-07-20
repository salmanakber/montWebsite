<?php
/**
 * The template for displaying search results pages.
 *
 * @package stackstar.
 */

get_header(); ?>
<section class="pd110 search-banner">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12 text-center"> 
          <h1 class=""> <span class="search-page-title"><?php printf( esc_html__( 'Search Results for: %s', stackstar ), '<span>' . get_search_query() . '</span>' ); ?></span></h1>
        </div>
      </div>
    </div>
</section>

    <div class="search-container">
    <section id="primary" class="content-area">
         <div class="container inner-container-wd">
    <div class="row">
        <div class="col-md-12">
        <main id="main" class="site-main" role="main" style="padding-top: 50px;">

        <?php if ( have_posts() ) : ?>

            <?php /* Start the Loop */ ?>
            <?php while ( have_posts() ) : the_post(); ?>
            
            <a href="<?php the_permalink(); ?>">
            <span class="search-post-title"><?php the_title(); ?></span>
            <span class="search-post-excerpt"><?php the_excerpt(); ?></span>
           </a>

            <?php endwhile; ?>

            <?php //the_posts_navigation(); ?>

        <?php else : ?>

            <?php //get_template_part( 'template-parts/content', 'none' ); ?>

        <?php endif; ?>

        </main><!-- #main -->
    </div>
             </div>
</div>
    </section><!-- #primary -->
</div>
<?php //get_sidebar(); ?>
<?php get_footer(); ?>