<?php
ob_start();
/*
Template Name: Blogs
*/
?>

<?php get_header(); ?>

<section class="inner-banner abt-banner " style="background:url(<?php echo get_the_post_thumbnail_url();?>)">
  <div class="container-fluid">
    <div class="row">

    </div>
  </div>
</section>

<section class="story-post-title">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12 common-page-titles">
        <h1 class=""><?php
                      $page_extra_title = get_post_meta(get_the_ID(), 'page_extra_title', true);
                      $page_extra_title_description = get_post_meta(get_the_ID(), 'page_extra_title_description', true);
                      if ($page_extra_title) {
                        echo $page_extra_title;
                      } else {
                        echo get_the_title();
                      }


                      ?> </h1>
      </div>

    </div>
  </div>
</section>

<section class="all-blogs">

  <div class="container inner-container-wd">
    <div class="row">
      <!-- Blog Post -->
      <?php
      $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
      $args = array('post_type' => 'post', 'posts_per_page' => 9, 'paged' => $paged);

      $post_type_data = new WP_Query($args);

      set_query_var('page', $paged);
      while ($post_type_data->have_posts()) :
        $post_type_data->the_post();
        global $more;
        $more = 0; ?>
        <?php
        $excerpt = get_the_excerpt();
        $excerpt = substr($excerpt, 0, 300);
        $result = substr($excerpt, 0, strrpos($excerpt, ' '));

        ?>
        <div class="col-md-4">
          <div class="blog-block">
            <a href="<?php echo the_permalink(); ?>" class="no-anim">
              <?php if (has_post_thumbnail()) : ?>
                <?php $default = array('class' => 'img-responsive');
                the_post_thumbnail('wl_blog_img', $default); ?>
              <?php endif; ?>
              <h4><?php echo the_title(); ?></h4>
              <span><a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>"><?php echo get_the_author(); ?></a> <?php echo get_the_date('j'); ?> <?php echo the_time('M'); ?>, <?php echo the_time('Y'); ?> </span>
              <p><?php echo $result; ?></p>
            </a>
            <a href="<?php echo the_permalink(); ?>">Read More</a>
          </div>
        </div>
      <?php endwhile; ?>
      <div class="nav-previous alignleft col-md-12"><?php previous_posts_link('« Newer posts'); ?></div>
      <div class="nav-next alignright"><?php next_posts_link('Older posts »', $post_type_data->max_num_pages); ?></div>
      <!-- //Blog Post// -->

    </div>
  </div>

</section>


<?php get_footer(); 