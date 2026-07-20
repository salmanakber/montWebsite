<?php get_header();?>
<?php while ( have_posts() ) : the_post(); 
 $post_id = get_the_ID();
  $featured_img_url = get_the_post_thumbnail_url($post_id,'large'); 
  $termID = get_the_category($post_id);
	?>




<section class="blog-conentt pd110">
  <div class="container inner-container-wd">
    <div class="row">
      <div class="col-md-8"> 
           <div class="blog-main">
            <img src="<?php echo $featured_img_url ?>" alt="">
           <?php //the_post_thumbnail('full'); ?>
              <span>  <?php// the_author_posts_link(); ?></span><span><?php the_time('F jS, Y'); ?></span>
                    <h4><?php the_title();?></h4>
            	<?php the_content(); ?>
          </div>
        </div>
        <div class="col-md-4">
            <div class="right-secc-content">
              
              <form role="search" method="get" id="searchform"
    class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <div>
        <label class="screen-reader-text" for="s"><?php _x( 'Search for:', 'label' ); ?></label>
        <input type="text" value="<?php echo get_search_query(); ?>" name="s" id="s" placeholder="Search keyword"/>
        <input type="submit" id="searchsubmit"
            value="<?php echo esc_attr_x( 'Search', 'submit button' ); ?>" />
    </div>
</form>

<?php 
$post_type = get_post_type( $post_id );
if ($post_type=='post') {
  $text = 'Råd & Inspirasjon';
}else{
  $text = $post_type;
}


?>
                <h3 style="text-transform: uppercase;"> <?php echo $text ?></h3>
               <?php 
                $args = array(
        'post_type' => $post_type,
        'posts_per_page' => 3,



      );
               $the_query = new WP_Query( $args); 
              // print_r($the_query);
               while ($the_query -> have_posts()) : $the_query -> the_post();
                $post_id = get_the_ID();
                  $featured_img_url = get_the_post_thumbnail_url($post_id, 'full');
                  $excerpt = get_the_excerpt();
 
                  $excerpt = substr($excerpt, 0, 100);
                 $result = substr($excerpt, 0, strrpos($excerpt, ' '));

                ?>
                <div class="latest-post">
                  <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                 <div class="latestimg">  <img src="<?php echo $featured_img_url ?>" alt=""></div>
                  <div class="latest-content"><h4><?php the_title(); ?></h4>
                   <p><?php echo $result; ?></p>
                    <span><?php the_time('F jS, Y'); ?></span></div>
                    </a>
                    <div class="clearfix"></div>
                </div>
                
                <?php endwhile;wp_reset_postdata();?>
            </div>
        </div>
      </div>
    </div>
</section>

<?php endwhile; ?>
<?php get_footer();?>