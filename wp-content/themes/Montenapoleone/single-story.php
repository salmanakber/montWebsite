<?php get_header(); ?>
<?php while (have_posts()) : the_post();
  $post_id = get_the_ID();
  $featured_img_url = get_the_post_thumbnail_url($post_id, 'large');
?>
<style>
.inner-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 400px; /* Change this to the desired height */
  position: relative;
}
	.inner-banner p {
    text-align: left !important;
    font-size: 14px;
}
	section.story-block {
    display: none !important;
}

.inner-banner h3 {
    text-align: center;
    color: #fff;
    font-size: 28px !important;
    position: absolute;
    top: -14%;
    width: 100%;
    padding: 0 20px;
}
</style>

 <?php if ($post_id=='1823') {?>
     <section class="inner-banner abt-banner story-bannerr" style="background:url(<?php echo str_replace("https://wordpress-843741-3123615.cloudwaysapps.com", "", $featured_img_url);?>)" >
    
    
    <div class="container-fluid">
      <div class="row">

      </div>
    </div>
  </section>
  <div class="story-titlee" style="padding-bottom: 0!important; text-align: center;">
            <h4 class="story-title-data" style="padding: 50px 0px 0px 0px; font-color:#7b7a7a!important0"><span class="new-title-data" ><?php the_title(); ?></span></h4></div> 
 
  <?php } else { ?>

 <section class="inner-banner abt-banner story-bannerr " style="background:url(<?php echo str_replace("https://wordpress-843741-3123615.cloudwaysapps.com", "", $featured_img_url);?>)">
    <div class="container-fluid">
      <div class="row">
		<div class="col-md-12">
			<h3 class="story-title-dat" style="paddng: 50px 0px 0px 0px;"><span class="new-title-data" ><?php the_title(); ?></span></h3>
		<?php the_content(); ?>
      </div>
    </div>
    </div>

  </section>
  <div class="story-titlee" style="padding-bottom: 0!important; text-align: center;">
            </div> 

<?php }?>


  <section class="story-post-title">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12 common-page-titles">
         <!-- <h1 class=""><?php
                        $page_extra_title = get_post_meta(get_the_ID(), 'page_extra_title', true);
                        $page_extra_title_description = get_post_meta(get_the_ID(), 'page_extra_title_description', true);
                        if ($page_extra_title) {
                          echo $page_extra_title;
                        } else {
                          echo get_the_title();
                        }


                        ?> </h1>-->
        </div>

      </div>
    </div>
  </section>


<section class="story-block"> 
   <div class="container">
      <div class="row">
        <div class="col-md-12 text-center">
           
            
           <!-- <div class="story-imagee">
               <?php the_post_thumbnail(); ?>
            </div>--->
            
            <div class="story-contentt" style="">
                   <?php //the_content(); ?>
            </div>
          </div>
       </div>
    </div>
</section>

 <!-- <section class="left-right-sec">
    <div class="container">
      <div class="row">
        <div class="col-md-6 col-sm-12">
          <div class="left-imgg">
            <?php the_post_thumbnail(); ?>
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="right-sec-content">
            <h4><?php the_title(); ?></h4>
        
          </div>
        </div>
      </div>
    </div>
  </section>-->


<?php endwhile; ?>
<?php get_footer(); ?>