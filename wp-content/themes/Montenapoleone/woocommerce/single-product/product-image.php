<?php
/**

 * Single Product Image

 *

 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.

 *

 * HOWEVER, on occasion WooCommerce will need to update template files and you

 * (the theme developer) will need to copy the new files to your theme to

 * maintain compatibility. We try to do this as little as possible, but it does

 * happen. When this occurs the version of the template file will be bumped and

 * the readme will list any important changes.

 *

 * @see     https://docs.woocommerce.com/document/template-structure/

 * @package WooCommerce/Templates

 * @version 3.5.1

 */



defined('ABSPATH') || exit;



// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.

if (!function_exists('wc_get_gallery_image_html')) {

    return;

}



global $product;



$columns   = apply_filters('woocommerce_product_thumbnails_columns', 4);

$post_thumbnail_id = $product->get_image_id();

$attachment_ids = $product->get_gallery_image_ids();

if ($attachment_ids) {

    array_unshift($attachment_ids, $post_thumbnail_id);

}

$attachment_ids = array_slice($attachment_ids, 0, 6, true);

// $attachment_ids[] = 1245;

$wrapper_classes   = apply_filters(

    'woocommerce_single_product_image_gallery_classes',

    array(

        'woocommerce-product-gallery',

        'woocommerce-product-gallery--' . ($product->get_image_id() ? 'with-images' : 'without-images'),

        'woocommerce-product-gallery--columns-' . absint($columns),

        'images',

    )
);

?>



<div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $wrapper_classes))); ?>" data-columns="<?php echo esc_attr($columns); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">


    <div class="product_images_main container f">
        <div class="row">
						<?php
	


	$shippingValue = '';
	if(!empty(get_field('product_type')))
	{
		
		if(get_field('product_type') == 'TILGJENGELIG')
		{
		$shippingValue = get_field('nos_values');
		}
		else
		{
			$shippingValue = get_field('pre-order_value');
		}
	}
		else{
			$shippingValue = 'Free shipping all over the world';
		}
  ?>
		    <div class="shi"><?php echo $shippingValue; ?></div>
            <div class="product_images_col col-12 galleryData" >
				<div class="imageShow" data-click="false">
					<aside></aside>
					<div class="anim">
					<div class="cursor"></div>
					<div class="cursor2"></div>
					</div>
				</div>
				<div class="pref">
				<div href="javascript:history.back()" class="GoBack"><span class="fa fa-chevron-left"></span></div>
				</div>
<div class="slick-carousel">
    <?php
    $i = 0;
    foreach ($attachment_ids as $attachment_id) {
        if ($i == 4) break;
        $img_url = wp_get_attachment_image_src($attachment_id, "full")[0];
    ?>
        <div class="single_thumb_box1 gallery_lighBox" data-src="<?php echo $img_url; ?>">
            <a class="" href="javascript:void(0);"><img src="<?php echo $img_url; ?>" class="zoom-image" alt="Product Image"></a>
        </div>
    <?php
        $i++;
    }
    ?>
</div>



            </div>


        </div>

    </div>
	
	  <script>
		  
jQuery(document).ready(function($) {
    // Get the referring page title from sessionStorage
    var formattedLastPart = document.referrer || '';
    var urlParts = formattedLastPart.split('/');

    // Get the last part of the URL
    var lastPart = urlParts[urlParts.length - 2];

    // Replace hyphens with spaces
    var referringPageURL = lastPart.replace(/-/g, ' ');

    // Truncate the URL if it's longer than 25 characters
    if (referringPageURL.length > 25) {
        referringPageURL = referringPageURL.substring(0, 25) + '...';
    }
	


    if (referringPageURL) {
        // Set the title attribute of the link
        $('.GoBack').attr('title', referringPageURL);

        // Add a custom tooltip on hover
        $('.GoBack').hover(function() {
            // Create the custom tooltip element
            var tooltip = $('<div class="custom-tooltip"></div>').text(referringPageURL);

            // Append the tooltip to the parent container
            $(this).parent().append(tooltip);

            // Position the tooltip relative to the link

            // Show the tooltip with sliding effect
            tooltip.stop().animate({
                opacity: 1,
                left: '+=0'
            }, 'fast');
        }, function() {
            // Hide and remove the tooltip on mouse out with sliding effect
            $('.custom-tooltip').stop().animate({
                opacity: 0,
                left: '-=10'
            }, 'fast', function() {
                $(this).remove();
            });
        });

        // Handle click event to go back to the referring page
        $('.GoBack').on('click', function() {
            // Redirect to the referring page
            window.location.href = document.referrer;
        });
    }
});

jQuery(document).ready(function($) {
    // Initialize Slick Carousel on mobile devices
    if ($(window).width() < 768) {
        $('.slick-carousel').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            infinite: true,
            arrows: false,
            dots: true,
            // Add other Slick options as needed
        });
    }
});
		  
jQuery(document).ready(function($) {
    // Single product hover zoom in - Only for desktop
    if ($(window).width() > 768) {
        $('.single_thumb_box1').addClass('zoom-container').mousemove(function(e) {
            var $zoomer = $(this);
            var offsetX = e.offsetX ? e.offsetX : e.touches[0].pageX;
            var offsetY = e.offsetY ? e.offsetY : e.touches[0].pageY;
            var x = (offsetX / $zoomer.width()) * 100;
            var y = (offsetY / $zoomer.height()) * 100;
            $zoomer.find('.zoom-image').css('transform-origin', x + '% ' + y + '%');
        });
    }
});


	 document.addEventListener("DOMContentLoaded", function () {
      const container = document.querySelector('.monto-grid-container');
      const items = container.querySelectorAll('.monto-grid-item');
      if (items.length <= 10) {
        items.forEach(item => {
          item.style.width = 'calc(25% - 5px)';
		item.classList.add('less-than-ten-per-row');
        });
      } else {
        items.forEach((item, index) => {
			item.classList.add('less-than-ten-per-row');
        	item.style.width = 'calc(25% - 5px)';
        });
      }
    });
		  
jQuery(document).ready(function($) {
    $('.monto-grid-item a').on('mouseover', function() {
        var imgSrc = $(this).data('src');
        var popupHtml = '<div class="image-popup"><img src="' + imgSrc + '"></div>';

        // Append the popup HTML to the body
        $('body').append(popupHtml);

        // Position the popup close to the left side of the clicked thumbnail
        var offset = $(this).offset();
        var popupWidth = $('.image-popup').outerWidth();
        $('.image-popup').css({
            'top': offset.top + 'px',
            'left': offset.left - popupWidth + 'px', // Adjust the left position
            'position': 'absolute'  // Ensure the absolute positioning
        });
    });

    // Remove the image popup on mouseout
    $('.monto-grid-item a').on('mouseout', function() {
       $('.image-popup').remove();
    });


$('.monto-grid-item a').on('click', function() {
    var thisElement = $(this);
    $('.monto-grid-item a').removeClass('active-product');
    thisElement.addClass('active-product');
	$('.selected_vc').val(thisElement.attr('data-text'))
});

});



	/// Images gallery code
	jQuery(document).ready(function(){
		var statusChecker = 0;
		jQuery(".gallery_lighBox").click(function(){
			var imgPath = jQuery(this).find('img').attr('src');
			var thisEl = jQuery(".imageShow");
				thisEl.show()
				thisEl.find('aside').html('<img src="'+imgPath+'" class="scaleImage"/>');
				jQuery(".gallery_lighBox").find('img').css("visibility","hidden");
				statusChecker = 1
				thisEl.attr('data-click','true');
				jQuery('body').attr('data-click','true');
				jQuery('body').addClass('hideGallery');
				})
			
				jQuery(".imageShow").click(function(){
				var thisEl = jQuery(".imageShow");
				thisEl.hide()
				thisEl.find('aside').html('');
				jQuery(".gallery_lighBox").find('img').css("visibility","visible");
				jQuery(".gallery_lighBox").addClass("scaleImage");
				thisEl.attr('data-click','false');
				jQuery('body').attr('data-click','false');
				jQuery('body').removeClass('hideGallery');
				
		
});
	})


	// UPDATE: I was able to get this working again... Enjoy!

var cursor = document.querySelector('.cursor');
var cursorinner = document.querySelector('.cursor2');
var a = document.querySelectorAll('a');

document.addEventListener('mousemove', function(e){
  var x = e.clientX;
  var y = e.clientY;
  cursor.style.transform = `translate3d(calc(${e.clientX}px - 50%), calc(${e.clientY}px - 50%), 0)`
});

document.addEventListener('mousemove', function(e){
  var x = e.clientX;
  var y = e.clientY;
  cursorinner.style.left = x + 'px';
  cursorinner.style.top = y + 'px';
});

document.addEventListener('mousedown', function(){
  cursor.classList.add('click');
  cursorinner.classList.add('cursorinnerhover')
});

document.addEventListener('mouseup', function(){
  cursor.classList.remove('click')
  cursorinner.classList.remove('cursorinnerhover')
});

a.forEach(item => {
  item.addEventListener('mouseover', () => {
    cursor.classList.add('hover');
  });
  item.addEventListener('mouseleave', () => {
    cursor.classList.remove('hover');
  });
})
	</script>
	<style>
/* 		single prdocut hover zoom in css  */
	.zoom-container {
    position: relative;
    overflow: hidden;
}

.zoom-image {
    width: 100%;
    height: 100%;
    transition: transform 0.3s ease;
}

.zoom-container:hover .zoom-image {
    transform: scale(2.5); /* Increase the scale value for more zoom */
}

.imageShow {
    position: absolute;
    width: 100% !important;
    height: 700px !important;
	display:none;
    z-index: 9;
}
		
@media(max-width: 768px)
{
	.imageShow {
    z-index: 0 !important;
}
}
		

.cursor {
  width: 50px;
  height: 50px;
  border-radius: 100%;
  border: 1px solid black;
  transition: all 200ms ease-out;
  position: fixed;
  pointer-events: none;
  left: 0;
  top: 0;
  transform: translate(calc(-50% + 15px), -50%);
}


.cursor2 {
    width: 43px;
    height: 43px;
    color: red;
    border-radius: 100%;
    background-color: #77a464;
    opacity: .3;
    position: fixed;
    transform: translate(-50%, -50%);
    pointer-events: none;
    text-align: center;
    padding: !important;
    transition: width .3s, height .3s, opacity .3s;
}
.cursor2:after {
    content: "X";
    text-align: center;
    line-height: 48px;
    cursor: pointer;
}


.cursorinnerhover {
  text-align: center;
  width: 50px;
	color:white;
  height: 50px;
  opacity: .5;
}
		.scaleImage {
  animation: scaling-image-animation 1s ease;
}

@keyframes scaling-image-animation {
  0% {
    transform: scale(0.3);
    opacity: 0.8;
  }
  50% {
    transform: scale(0.7);
  }
  100% {
    transform: scale(1);
  }
}

	</style>
    <div class="clearfix"></div>

    <?php 
	function attr_Design() {
    $option_main = get_post_meta(get_the_ID() , 'choose_option', true);
    if($option_main=='Yes')
    {
          if( have_rows('choose_collar') ):
            ?>
            <span class='custom_collar'>Velg Snipp</span>
            <div class="" id="collor-design">
               <div class="select_design">
                <?php
                while( have_rows('choose_collar') ) : the_row();
                // Load sub field value.
                $name = get_sub_field('name');
                $image = get_sub_field('image');
                ?>
                  <div class='product_inner'>
                    <label>
                      <div class='product_img'><img src='<?php echo  $image; ?>'></div>
                      <div class="product_name"><input type="checkbox" class="radio"  value="<?php echo $name ?>" name="collor_design" data_value='collar_design'/><div class="collar_txt sds"><?php echo $name; ?></div></div>
                    </label>
                  </div>
             <?php
            endwhile;
            ?>
             </div>
             </div>
           <?php
       else: ?>
            <span class='custom_collar'>Velg Snipp</span>
            <div class="" id="collor-design">
               <div class="select_design">
                <?php
                $upload_dir = wp_upload_dir();
                $collar_names = ['/2021/03/New-Project-4.png' =>'Name','/2021/03/New-Project-5.png' =>'Name','/2021/03/New-Project-6.png' =>'Name','/2021/03/New-Project-7.png' =>'Name'];
                 foreach($collar_names as $collar_image => $collar_name){ ?>
                      <div class='product_inner'>
                        <label>
                          <div class='product_img'><img src='<?php  echo $upload_dir['baseurl'].$collar_image;  ?>'></div>
                          <div class="product_name"><input type="checkbox" class="radio"  value="<?php echo $collar_name ?>" name="collor_design" data_value='collar_design'/><div class="collar_txt "><?php echo $collar_name; ?></div></div>
                        </label>
                      </div>
                    <?php
                }
                ?>
                </div>
            </div>
                <?php
            endif;
              
            if( have_rows('choose_cuff') ):
              ?>
                <span class='custom_collar'>Velg Mansjetter</span>
              <div class="" id="cuff-design">
               <div class="select_design">
                <?php
                  while( have_rows('choose_cuff') ) : the_row();
                    // Load sub field value.
                    $name = get_sub_field('name');
                    $image = get_sub_field('image');
                  ?>
                    <div class='product_inner'>
                      <label>
                        <div class='product_img'><img src='<?php echo  $image; ?>'></div>
                        <div class="product_name"><input type="checkbox" class="radio"  value="<?php echo $name ?>" name="cuff_design"  data_value='cuff_design'/><div class="collar_txt"><?php echo $name; ?></div></div>
                      </label>
                    </div>
                  <?php
                  endwhile;
                  ?>
                </div>
              </div>
           <?php
             else: ?>
                <span class='custom_collar'>Velg Mansjetter</span>
                <div class="" id="cuff-design">
                    <div class="select_design">
                        <?php
                        $upload_dir = wp_upload_dir();
                        $collar_names = ['/2021/03/New-Project-1.png' =>'Name','/2021/03/New-Project-3.png' =>'Name','/2021/03/New-Project-2.png' =>'Name','/2022/03/New-Project-3.png' =>'Name'];
                        foreach($collar_names as $collar_image => $collar_name){ ?>
                            <div class='product_inner'>
                                <label>
                                    <div class='product_img'><img src='<?php echo $upload_dir['baseurl'].$collar_image; ?>'></div>
                                    <div class="product_name"><input type="checkbox" class="radio"  value="<?php echo $collar_name ?>" name="cuff_design"  data_value='cuff_design'/><div class="collar_txt"><?php echo $collar_name; ?></div></div>
                                </label>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
            endif;
}

else
	{
		
	}
	}
	
	/////// New design block Above design temprory disable// ///
	
		
		function Product_customization() {
		global $product;
$catType = "";

$categories = get_the_terms( $product->get_id(), 'product_cat' );

if ( $categories && ! is_wp_error( $categories ) ) {
    foreach ( $categories as $category ) {
        if ( $category->parent == 0 ) {
            $category_name = $category->name;
            
            if ( $category_name === "Herre Fritids Skjorter" ) {
                $catType = "casual";
                break;
            } elseif ( $category_name === "Herre Formelle Skjorter" ) {
                $catType = "business";
                break;
            }
        }
    }
}
			
    $option_main = get_post_meta(get_the_ID() , 'choose_option', true);
    if($option_main=='Yes')
    {
          if( have_rows('choose_collar') ):
            ?>
	<div class="content_parents">
	<div class="heading dssd">Velg Snipp (Valg fritt)</div>
			<div class="contents">
            <div class="" id="collor-design">
               <div class="select_design">
                <?php
				$cufftype = 0;
                while( have_rows('choose_collar') ) : the_row();
                // Load sub field value.
                // 
                $image = '';
				$cufftype ++;
                $name = get_sub_field('name');
				if(!empty(get_sub_field('image')))
				{
                $image = get_sub_field('image');
				}
				else{
					if($catType === "casual"){
					if($cufftype == 1)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/BUTTON-DOWN.jpg';
					}
							if($cufftype == 2)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/CASUAL-CUTAWAY-copy.jpg';
					}		if($cufftype == 3)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/CASUAL-SPREAD.jpg';
					}
							if($cufftype == 4)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/MODERN-BD-copy.jpg';
					}
					}
					if($catType === "business"){
					if($cufftype == 1)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/BUTTON-DOWN-1.jpg';
					}
							if($cufftype == 2)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/CLASSIC-copy.jpg';
					}		if($cufftype == 3)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/CUTAWAY-copy.jpg';
					}
							if($cufftype == 4)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/SEMI-SPREAD-copy.jpg';
					}
					}
				}
				$check = get_sub_field('selected');
		
                ?>
                  <div class='product_inner'>
                    <label>
                      <div class='product_img'><img src='<?php echo  $image; ?>'></div>
                      <div class="product_name containers">
						  <input type="checkbox" class="radio" <?php echo ($check == 'Yes' ? "checked='checked'" : '') ?> value="<?php echo $name ?>" name="collor_design" data_value='collar_design'/><i class="checkmark"></i><div class="collar_txt accs"><?php echo $name; ?></div></div>
                    </label>
                  </div>
             <?php
            endwhile;
            ?>
             </div>
             </div>
		</div>
	</div>
                <?php
            endif;
              
            if( have_rows('choose_cuff') ):
              ?>
	<div class="content_parents">
		<div class="heading">Velg Mansjetter (Valg fritt)</div>
			<div class="contents">
              <div class="" id="cuff-design">
               <div class="select_design">
                <?php
				$cufftype = 0;
                  while( have_rows('choose_cuff') ) : the_row();
                    // Load sub field value.
                                 $image = '';
					$cufftype ++;
                $name = get_sub_field('name');
				if(!empty(get_sub_field('image')))
				{
                $image = get_sub_field('image');
				}
				else{
					if($cufftype == 1)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/ANGLE.jpg';
					}
							if($cufftype == 2)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/MODERN-ROUND.jpg';
					}		if($cufftype == 3)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/ROUNDED.jpg';
					}
							if($cufftype == 4)
					{
					$image = site_url().'/'.'wp-content/uploads/2024/04/SQUARE.jpg';
					}
				}
		$check = get_sub_field('selected');
                  ?>
                    <div class='product_inner'>
                      <label>
                        <div class='product_img'><img src='<?php echo  $image; ?>'></div>
                        <div class="product_name containers">
							<input type="checkbox" <?php echo ($check == 'Yes' ? "checked='checked'" : '') ?> class="radio"  value="<?php echo $name ?>" name="cuff_design"  data_value='cuff_design'/><i class="checkmark"></i><div class="collar_txt accs"><?php echo $name; ?></div></div>
                      </label>
                    </div>
                  <?php
                  endwhile;
                  ?>
                </div>
              </div>
	</div>
	</div>

                <?php
            endif;
}

else
	{
		
	}
	}

	add_shortcode('Product_customization_attributes','Product_customization');
	
            ?>

<?php 
if( has_term( 'suits', 'product_cat' ) ) {
    ?>
<div class="custommade_cancellation_policy alert alert-info">
    <h2>CANCELLATION & RETURNS FOR TAILOR MADE SHIRTS.</h2>
      <p> All tailor-made shirts is 100% individually customize according to customers preferences. Hence, we DO NOT accept returns for any reason except production error.</p>
      <h2>DELIVERYTIME FOR TAILOR MADE SHIRTS.</h2>
      <p>All tailor-made shirts require more work and changing new body parts therefore we have to add up-to seven (7) days extra additional to normal delivery time. </p>
     </div>
<?php }
else{
    ?>
    <div class="custommade_cancellation_policy alert alert-info">
    <h2>CANCELLATION & RETURNS FOR TAILOR MADE SHIRTS.</h2>
    <p> All tailor-made shirts are 100% individually customize according to customers preferences. Hence, we DO NOT accept returns for any reason except production error.</p>
    <h2>DELIVERYTIME FOR TAILOR MADE SHIRTS.</h2>
    <p>All tailor-made shirts require more work and changing new body parts therefore we have to add up-to seven (7) days extra additional to normal delivery time. </p>
         </div>
     <?php }
    ?>
</div>