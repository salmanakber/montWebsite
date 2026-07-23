<?php
defined( 'ABSPATH' ) || exit;

global $product;

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}

// ==========================================
// 1. DATA PREPARATION (Unified Grid)
// ==========================================
$main_image_id = $product->get_image_id();
$gallery_images = $product->get_gallery_image_ids();
$video_url = get_post_meta(get_the_ID(), '_product_video', true);

$media_items = [];

// Add Main Image
if ($main_image_id) {
    $media_items[] = ['type' => 'image', 'id' => $main_image_id];
}

// Add Gallery Images
foreach ($gallery_images as $img_id) {
    $media_items[] = ['type' => 'image', 'id' => $img_id];
}

// Insert Video at Index 1 (Top Row, Right Side) if it exists
if (!empty($video_url)) {
    array_splice($media_items, 1, 0, [['type' => 'video', 'url' => $video_url]]);
}

$total_items = count($media_items);
require_once get_template_directory(). '/codeFunction/custom-variation.php';
$customVariation = new CustomVariation();
$product_categories = wp_get_post_terms( get_the_ID(), 'product_cat' );

?>
<!-- Removed Plyr CSS/JS as we are using custom controls now -->

<style type="text/css">
body.single-product {margin: 0 !important;}

/* =========================================
   CUSTOM CURSORS (SVG Data URIs)
   ========================================= */
.custom-cursor-expand {
  cursor: url('data:image/svg+xml;utf8, <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24"> <rect x="5" y="5" width="14" height="14" fill="white"/>  <g transform="translate(12 12) scale(0.6) translate(-12 -12)">    <path d="M15 3h6v6 M9 21H3v-6 M21 3l-7 7 M3 21l7-7"          fill="none"          stroke="black"          stroke-width="1.2"          stroke-linecap="round"          stroke-linejoin="round"/>  </g></svg>') 16 16, pointer;
}



.custom-cursor-plus {
    cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>') 16 16, zoom-in;
}
.custom-cursor-minus {
    cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>') 16 16, zoom-out;
}

/* =========================================
   LAYOUT STRUCTURE
   ========================================= */
.mont_single_product_container { overflow: visible; }

.mont_top_layout {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    position: relative;
}

.mont_layout_sixty {
    width: 65%; 
    padding-right: 10px;
}

.mont_layout_fourty {
    width: 35%;
    position: -webkit-sticky;
    position: sticky;
    top: 20px;
    height: auto;
    z-index: 20;
}
	.smallSubname {
    font-size: 12px;
    text-align: center;
    padding-top: 5px;
    padding-bottom: 8px;
    color: rgb(10 10 10 / 60%);
}

/* =========================================
   UNIFIED GRID GALLERY
   ========================================= */
.mont_gallery_grid_wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr; /* 2 Items Per Row */
    gap: 2px;
    width: 100%;
}

.mont_gallery_item {
    position: relative;
    width: 100%;
    /* FORCE 3:4 ASPECT RATIO */
    aspect-ratio: 3 / 4; 
    overflow: hidden;
    background: #f9f9f9;
}

/* Hide items > 4 initially */
.mont_gallery_item.initially-hidden { display: none; }

.mont_gallery_item video,
.mont_gallery_item img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

/* =========================================
   CUSTOM VIDEO BUTTONS (Grid & Lightbox)
   ========================================= */
.mont_video_overlay_btn {
     position: absolute;
    bottom: 15px;
    left: 15px;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    z-index: 5;
    transition: background 0.3s;
}

.mont_video_overlay_btn:hover { background: #fff; }
.mont_video_overlay_btn svg { width: 14px; height: 14px; fill: #000; }

/* See More Button */
.mont_see_more_container {
    width: 100%;
    display: flex;
    justify-content: center;
    margin: 20px 0;
}
.mont_see_more_btn {
    background: #000;
    color: #fff;
    border: 1px solid #000;
    padding: 10px 30px;
    text-transform: uppercase;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.mont_see_more_btn:hover { background: #fff; color: #000; }

div#mont_backButton { z-index: 999; }

/* =========================================
   LIGHTBOX STYLES
   ========================================= */
.mont_gallery_lightbox {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgb(228 228 228);
    z-index: 99999;
    justify-content: center;
    align-items: center;
}
.mont_gallery_lightbox.active { display: flex !important; }

.mont_gallery_lightbox-content {
    position: relative;
    width: 90%;
    height: 90%;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Image State */
.mont_gallery_lightbox-image {
    max-width: 100%; max-height: 100%;
    object-fit: contain;
    transition: transform 0.2s ease;
    display: none; 
}
.mont_gallery_lightbox-image.visible { display: block; }

/* Video State */
.mont_gallery_lightbox-video-container {
    position: relative; /* For button positioning */
    width: 100%;
    height: 100%;
    max-width: 1000px; /* Limit max width of video in lightbox */
    display: none; 
    align-items: center;
    justify-content: center;
}
.mont_gallery_lightbox-video-container.visible { display: flex; }

.mont_gallery_lightbox-video {
    width: 100%;
    height: auto;
    max-height: 100%;
}

.mont_gallery_close-btn_new {
    position: absolute;
    top: 20px; right: 30px;
    color: #fff; font-size: 40px;
    cursor: pointer; z-index: 10000;
}

/* Responsive */
@media (max-width: 1024px) {
    .mont_layout_sixty, .mont_layout_fourty { width: 100%; padding-right: 0; position: static; }
    .mont_top_layout { flex-direction: column; }
}
@media (max-width: 768px) {
    .mont_single_product_container {
        padding: 0 0 40px;
    }
    .mont_top_layout {
        flex-direction: column;
        gap: 0;
    }
    .mont_layout_sixty,
    .mont_layout_fourty {
        width: 100%;
        padding: 0;
        position: static;
    }
    .mont_layout_fourty {
        padding: 16px 16px 0;
    }
    .mont_back_button {
        margin: 8px 12px;
    }

    /* Mobile: single-column slider for images AND video (same design) */
    .mont_gallery_wrapper-unified {
        position: relative;
        width: 100%;
        overflow: hidden;
    }
    .mont_gallery_grid_wrapper,
    .mont_gallery_grid_wrapper#mont_gallery_track {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        grid-template-columns: none !important;
        grid-template-rows: none !important;
        gap: 0 !important;
        width: 100%;
        overflow-x: auto !important;
        overflow-y: hidden;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .mont_gallery_grid_wrapper::-webkit-scrollbar {
        display: none;
    }
    /* Critical: video intrinsic size must not break one-slide-per-view */
    .mont_gallery_item,
    .mont_gallery_item.video-trigger,
    .mont_gallery_item.initially-hidden {
        display: block !important;
        flex: 0 0 100% !important;
        flex-shrink: 0 !important;
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        scroll-snap-align: start;
        scroll-snap-stop: always;
        aspect-ratio: 3 / 4;
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
    }
    .mont_gallery_item video,
    .mont_gallery_item img,
    .mont_gallery_main-video,
    .mont_gallery_main-image {
        width: 100% !important;
        max-width: 100% !important;
        height: 100% !important;
        min-width: 0 !important;
        object-fit: cover !important;
        display: block;
    }
    /* Keep play/pause usable while swiping the track */
    .mont_gallery_item video {
        pointer-events: none;
    }
    .mont_gallery_item .mont_video_overlay_btn {
        pointer-events: auto;
        z-index: 7;
    }
    .mont_see_more_container {
        display: none !important;
    }

    .mont_gallery_nav {
        display: flex !important;
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        transform: translateY(-50%);
        justify-content: space-between;
        pointer-events: none;
        z-index: 8;
        padding: 0 8px;
    }
    .mont_gallery_nav_btn {
        pointer-events: auto;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: rgba(255, 255, 255, 0.85);
        color: #111;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 1px 4px rgba(0,0,0,0.12);
        padding: 0;
    }
    .mont_gallery_nav_btn svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        fill: none;
        stroke-width: 1.5;
    }
    .mont_gallery_nav_btn:disabled {
        opacity: 0.35;
        cursor: default;
    }
    .mont_gallery_dots {
        display: flex !important;
        justify-content: center;
        gap: 6px;
        padding: 10px 0 4px;
    }
    .mont_gallery_dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #ccc;
        border: none;
        padding: 0;
        cursor: pointer;
    }
    .mont_gallery_dot.is-active {
        background: #111;
    }

    .mont_product-title {
        font-size: 20px;
        line-height: 1.3;
    }
    .mont_product-info {
        flex-wrap: wrap;
        gap: 8px;
    }
    .collar-options {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px;
    }
    .mont_cart_button a {
        width: 100%;
        text-align: center;
        display: block;
        box-sizing: border-box;
    }
    .mont_bottom_layout {
        padding: 24px 16px;
    }
}

@media (min-width: 769px) {
    .mont_gallery_nav,
    .mont_gallery_dots {
        display: none !important;
    }
}

</style>

<div class="mont_single_product_container">
	<div class="mont_top_area">
		 <p>Gratis frakt over hele verden</p>
		<?php $customVariation->display_slider_on_product_page(); ?>
		<p><?php
if(get_field("product_type") == "FORHÅNDSORDRE")
{
	echo  str_replace("Gratis frakt over hele verden.", " ", get_field("pre-order_value"));
}
			else{
					echo  str_replace("Gratis frakt over hele verden.", "", get_field("nos_values"));
			}
			
			?></p>
	</div>
	<div class="mont_top_layout">
		
        <!-- ========================== -->
        <!-- LEFT: GALLERY SECTION      -->
        <!-- ========================== -->
        <div class="mont_layout_sixty">
            <div class="mont_back_button" id="mont_backButton">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                <span>Back</span>
                <div class="mont_popover" id="mont_prevPagePopover">
					<div class="mont_popover_arrow"></div>
					<div class="mont_popover_content">
						<div class="mont_popover_text" id="mont_prevPageTitle">Loading previous page...</div>
					</div>
				</div>
            </div>
		
            <div class="mont_gallery_wrapper-unified">
                <div class="mont_gallery_grid_wrapper" id="mont_gallery_track">
                    <?php 
                    $counter = 0;
                    foreach ($media_items as $index => $item) : 
                        $counter++;
                        // Hide items beyond first 4 (desktop only; mobile CSS shows all)
                        $hidden_class = ($counter > 4) ? 'initially-hidden' : '';
                        
                        if ($item['type'] === 'video') : ?>
                            <!-- VIDEO ITEM -->
                            <div class="mont_gallery_item <?php echo esc_attr($hidden_class); ?> custom-cursor-expand video-trigger"
                                 data-media-type="video"
                                 data-src="<?php echo esc_url($item['url']); ?>">
                                
                                <video id="grid-video-<?php echo $index; ?>" 
                                       class="mont_gallery_main-video"
                                       autoplay muted loop playsinline preload="auto">
                                    <source src="<?php echo esc_url($item['url']); ?>" type="video/mp4">
                                </video>

                                <!-- Grid Play/Pause Button -->
                                <div class="mont_video_overlay_btn" onclick="toggleVideo(event, 'grid-video-<?php echo $index; ?>')">
                                    <svg class="icon-pause" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                    <svg class="icon-play" style="display:none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                </div>
                            </div>

                        <?php else : 
                            $image_url = wp_get_attachment_image_url($item['id'], 'full');
                            ?>
                            <!-- IMAGE ITEM -->
                            <div class="mont_gallery_item <?php echo esc_attr($hidden_class); ?>">
                                <img src="<?php echo esc_url($image_url); ?>"
                                     class="mont_gallery_main-image custom-cursor-expand lightbox-trigger"
                                     alt="Product Image"
                                     data-media-type="image"
                                     data-src="<?php echo esc_url($image_url); ?>"
                                     loading="lazy">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_items > 1) : ?>
                <div class="mont_gallery_nav" aria-label="Gallery navigation">
                    <button type="button" class="mont_gallery_nav_btn mont_gallery_prev" aria-label="Previous">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="mont_gallery_nav_btn mont_gallery_next" aria-label="Next">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
                <div class="mont_gallery_dots" id="mont_gallery_dots"></div>
                <?php endif; ?>

                <?php if ($total_items > 4) : ?>
                <div class="mont_see_more_container">
                    <button id="mont_see_more_btn" class="mont_see_more_btn">See more images</button>
                </div>
                <?php endif; ?>
            </div>
        </div> 

        <!-- ========================== -->
        <!-- RIGHT: PRODUCT DETAILS     -->
        <!-- ========================== -->
        <div class="mont_layout_fourty">	
			<div class="mont_product-title">
				<?php echo $product->get_title(); ?>
			</div>
			<div class="mont_product-info">
				<div class="mont_product-price">
						<input type="hidden" value="0" id="added-price"/>
					<input type="hidden" value="<?php echo get_post_meta($product->get_id(), '_price', true) ?>" id="actual-price"/>
					
					<?php echo $product->get_price_html(); ?>
				</div>
				<div class="mont_product-stock">
						<?php 
								$stock_qty = $product->get_stock_quantity();
								echo ($stock_qty && $stock_qty > 0)   ? 'Tilgjengelig ' . $stock_qty    : 'Pre-order';
							?>
				</div>
			</div>

			<style>
				.desc_preview h4 {
  						  font-size: 13px;
   					 font-weight: 400;
					}
			</style>
			<div class="mont_product_des">
				<div class="mon_long_desc">
					<div class="desc_preview">
					<?php
					$content = apply_filters('the_content', $product->get_description());
							$trimmed = mb_strimwidth(strip_tags($content, '<strong><em><b><i><ul><ol><li><br><h1><h2><h3><h4><h5><h6>'), 0, 300, '...');
					echo wp_kses_post($trimmed);
						?>
					</div>
					<div class="desc_full" style="display: none;">
						<?php echo apply_filters('the_content', $product->get_description()); ?>
					</div>
					<span class="mont_show_hide_desc_text">Les mer...</span>
				</div>
			</div>
			<div class="mont_straight_line">
				Vennligst fyll ut
			</div>
			<div class="mont_custom_options">
				<div class="mont_custom_option_list_loop">
					<div class="mont_custom_single_loop_item">
						<!-- Custom product blocks will go here -->

						<?php foreach ($customVariation->customVariation($product) as $names): ?>
							<?php //print_r($names); ?>
							<div class="mont_variation-selector to-be-open-<?php echo esc_attr($names['attribute_slug']); ?>">
								<!-- Passform Section -->
								<div class="mont_variation-group <?php echo esc_attr($names['attribute_slug']); ?>">
									<div class="mont_variation-header <?php echo esc_attr($names['attribute_slug']); ?>" onclick="toggleSection(this)" 
										data-listName="<?php echo esc_attr($names['attribute_name']); ?>"
										data-attribute-key="<?php echo esc_attr($names['attribute_slug']); ?>">

										<h3><span class="mont_required">*</span> 
											<?php echo ($names['attribute_slug'] === 'pa_body-fit' ? 'Passform (Obligatorisk)' : 'Størrelse (Obligatorisk)'); ?>
										</h3>
										<span class="dpName"></span>
										<span class="mont_toggle-icon"> <i data-lucide="chevron-down"></i></span>
									</div>
									<ul class="mont_option-list">
										<?php foreach ($names['attribute_values'] as $value): ?>
											<li class="mont_option-item <?php echo $names['attribute_slug'].'-option'; ?>" 
												data-slug="<?php echo $value['slug']; ?>" 
												data-id="<?php echo $product->get_id();?>">
												<div class="mont_option-left">
													<span class="tobeSelected"><?php echo (esc_html($value['name'])); ?></span>
												</div>
												
												<div class="mont_option-right">
													<input type="checkbox" class="mont_checkbox_select <?php echo $names['attribute_slug'].'-checkbox'; ?>" 
													name="passform" 
													value="<?php //echo esc_attr($value['id']); ?>">
												</div>
											</li>
										<?php endforeach; ?>
										<!-- Add more passform options here if needed -->
									</ul>
								</div>
							</div>
						<?php endforeach; ?>
					<?php
					if ( !empty( $product_categories ) && !is_wp_error( $product_categories ) ) {
										$category_id = $product_categories[0]->term_id;
  											 $CustomCupAndCollarHide = get_field('cup_and_collar', 'product_cat_' . $category_id);
							if(!$CustomCupAndCollarHide)
								{
								?>
							<div class="mont_variation-selector custom-add velg-snipp ">
								<div class="mont_variation-group">
									<div class="mont_variation-header" onclick="toggleSection(this)">
										<h3>Velg Snipp (Valg fritt)</h3>
										<span class="dpName skname"><b></b></span>
										<span class="mont_toggle-icon"><i data-lucide="chevron-down"></i></span>
									</div>
									<div class="collar-options mont_option-list">
										<?php foreach (get_field('choose_collar_update', 'option') as $key => $value): ?>
											<label class="collar-option radioTocheck collar-option-click <?php echo (($value['selected']) == 'Yes'  ?  'selected' :  ''); ?>">
												<input 
												type="radio" 
												name="collar-style" 
												value="<?php  echo esc_attr(ucfirst($value['name'])); ?>" 
												data-check ="<?php echo esc_attr($value['selected']);  ?>"
												<?php echo (($value['selected']) == 'Yes'  ?  'checked' :  ''); ?>
												>
												<img src="<?php  echo esc_url($value['image']); ?>" alt="<?php  echo esc_attr(ucfirst($value['name'])); ?>" class="<?php echo  esc_attr(ucfirst($value['name'])); ?>">
												<div class="collar-name"><?php  echo esc_html(ucfirst($value['name'])); ?></div>
												<div class="smallSubname"><?php  echo esc_html(ucfirst($value['sub_name'])); ?>	</div>
											</label>
										<?php endforeach ?>
									</div>
								</div>
							</div>
	
							<div class="mont_variation-selector custom-add velg-mansjetter">
								<div class="mont_variation-group">
									<div class="mont_variation-header" onclick="toggleSection(this)">
										<h3> Velg Mansjetter (Valg fritt)</h3>
										<span class="dpName skname"><b></b></span>
										<span class="mont_toggle-icon"><i data-lucide="chevron-down"></i></span>
									</div>
									<div class="collar-options mont_option-list">
										<?php foreach (get_field('choose_cuff_update', 'option') as $key => $value): ?>
											<label class="collar-option radioTocheck cup-option-click <?php echo (($value['selected']) == 'Yes'  ?  'selected' :  ''); ?>">
												<input 
												type="radio" 
												name="cuff-style" 
												value="<?php  echo esc_attr(ucfirst($value['name'])); ?>" 
												<?php echo (($value['selected']) === 'Yes' ? 'checked' : ''); ?>
												>
											<img src="<?php  echo esc_url($value['image']); ?>" alt="<?php  echo esc_attr(ucfirst($value['name'])); ?>" class="<?php echo  esc_attr(ucfirst($value['name'])); ?>">
												<div class="collar-name"><?php  echo esc_html(ucfirst($value['name'])); ?></div>
												<div class="smallSubname"><?php  echo esc_html(ucfirst($value['sub_name'])); ?>	</div>
											</label>
										<?php endforeach ?>
									</div>
								</div>
							</div>
				<?php 
							}
					}
						?>
						
						<?php
								if ( !empty( $product_categories ) && !is_wp_error( $product_categories ) ) {
										$category_id = $product_categories[0]->term_id;
  											 $CustomTailorHide = get_field('customer_tailoring_', 'product_cat_' . $category_id);
											if(!$CustomTailorHide){
									?>

						<div class="mont_variation-selector skreddersydd">
							<div class="mont_variation-group">
								<div class="mont_variation-header" onclick="toggleSection(this)">
									<h3>Skreddersydd (Valgt Fritt)</h3>
									<span class="mont_toggle-icon"><i data-lucide="chevron-down"></i></span>
								</div>

								<?php require_once get_template_directory(). '/woocommerce/custom-sizes/customSize.php'; ?>
							</div>
						</div>
						<?php
											}
								}
										?>


					</div>
				</div>
			</div>
			<div class="mont_add_to_cart_button_and_alert">
				<div class="mont_cart_button"> 
					<a href="javascript:void(0)" 
					class="custom-add-to-cart" 
					data-product_id="<?php echo get_the_ID(); ?>">
					LEGG I HANDLE POSEN 
				</a>
			</div>
				<?php
				do_action( 'woocommerce_after_add_to_cart_button' );
				?>
			<div class="mont_alerts" >
				<div class="mont_alert" id="mont_alert" style="display: none;">
					<button class="mont_alert_close" onclick="closeAlert()">×</button>

					<h2 class="mont_alert_title">KANSELLERING OG RETUR FOR SKREDDERSYKTE SKJORTER.</h2>
					<p class="mont_alert_text">
						Alle skreddersydde skjorter er 100% individuelt tilpasset etter kundens preferanser. Derfor aksepterer vi IKKE returer av noen grunn bortsett fra produksjonsfeil.
					</p>

					<h2 class="mont_alert_title">LEVERINGSTID FOR SKREDDERSYKTE SKJORTER.</h2>
					<p class="mont_alert_text">
						Alle skreddersydde skjorter krever mer arbeid og skifter av nye deler, derfor må vi legge til opptil syv (7) dager ekstra i tillegg til normal leveringstid.
					</p>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="mont_bottom_layout">
	<div class="mont_related_products_slider">
		<h3>Skjorter: Våre anbefalinger</h3>
		<div class="mont_slider_single_product_page">
            <?php echo do_shortcode('[custom_product_grid related="yes" limit="3"]'); ?>
        </div>
    </div>
</div>

<!-- ========================== -->
<!-- LIGHTBOX HTML              -->
<!-- ========================== -->
<div class="mont_gallery_lightbox">
	<div class="mont_gallery_close-btn">×</div>
	<div class="mont_gallery_lightbox-content">
		
        <!-- Image Element -->
		<img src="" class="mont_gallery_lightbox-image custom-cursor-plus" alt="Lightbox Image">
        
        <!-- Video Element with Custom Controls -->
        <div class="mont_gallery_lightbox-video-container">
            <!-- Note: controls removed, muted="true" added -->
            <video id="lightbox-main-video" class="mont_gallery_lightbox-video" playsinline muted loop>
                <source src="" type="video/mp4">
            </video>
            
            <!-- Lightbox Play/Pause Button -->
            <div class="mont_video_overlay_btn" onclick="toggleVideo(event, 'lightbox-main-video')">
                <svg class="icon-pause" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                <svg class="icon-play" style="display:none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </div>
        </div>

	</div>
</div>


<script>
// Generic Function to Toggle Video (Works for both Grid and Lightbox)
function toggleVideo(e, videoId) {
    e.stopPropagation(); 
    
    const video = document.getElementById(videoId);
    const btn = e.currentTarget;
    const playIcon = btn.querySelector('.icon-play');
    const pauseIcon = btn.querySelector('.icon-pause');

    if (video.paused) {
        video.play();
        playIcon.style.display = 'none';
        pauseIcon.style.display = 'block';
    } else {
        video.pause();
        playIcon.style.display = 'block';
        pauseIcon.style.display = 'none';
    }
}

document.addEventListener("DOMContentLoaded", function () {
    
    // 1. "See More" Button Logic (desktop)
    const seeMoreBtn = document.getElementById('mont_see_more_btn');
    if (seeMoreBtn) {
        seeMoreBtn.addEventListener('click', function() {
            document.querySelectorAll('.mont_gallery_item.initially-hidden').forEach(item => {
                item.classList.remove('initially-hidden');
            });
            this.parentElement.style.display = 'none';
        });
    }

    // 2. Mobile gallery slider
    (function initMobileGallerySlider() {
        const track = document.getElementById('mont_gallery_track');
        if (!track) return;

        const items = Array.from(track.querySelectorAll('.mont_gallery_item'));
        if (items.length < 2) return;

        const prevBtn = document.querySelector('.mont_gallery_prev');
        const nextBtn = document.querySelector('.mont_gallery_next');
        const dotsWrap = document.getElementById('mont_gallery_dots');
        let index = 0;

        if (dotsWrap) {
            items.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'mont_gallery_dot' + (i === 0 ? ' is-active' : '');
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                dot.addEventListener('click', () => goTo(i));
                dotsWrap.appendChild(dot);
            });
        }

        function isMobile() {
            return window.matchMedia('(max-width: 768px)').matches;
        }

        function goTo(i) {
            if (!isMobile()) return;
            index = Math.max(0, Math.min(i, items.length - 1));
            const slideWidth = track.clientWidth || items[index].getBoundingClientRect().width;
            track.scrollTo({ left: slideWidth * index, behavior: 'smooth' });
            updateUI();
            // Pause videos that are not the active slide (keep play button working on active)
            items.forEach((item, idx) => {
                const vid = item.querySelector('video');
                if (!vid) return;
                if (idx === index) return;
                try { vid.pause(); } catch (e) {}
            });
        }

        function updateUI() {
            if (dotsWrap) {
                dotsWrap.querySelectorAll('.mont_gallery_dot').forEach((d, i) => {
                    d.classList.toggle('is-active', i === index);
                });
            }
            if (prevBtn) prevBtn.disabled = index <= 0;
            if (nextBtn) nextBtn.disabled = index >= items.length - 1;
        }

        function syncIndexFromScroll() {
            if (!isMobile()) return;
            const slideWidth = track.clientWidth || 1;
            index = Math.round(track.scrollLeft / slideWidth);
            index = Math.max(0, Math.min(index, items.length - 1));
            updateUI();
        }

        if (prevBtn) prevBtn.addEventListener('click', () => goTo(index - 1));
        if (nextBtn) nextBtn.addEventListener('click', () => goTo(index + 1));
        track.addEventListener('scroll', () => {
            window.clearTimeout(track._scrollTimer);
            track._scrollTimer = window.setTimeout(syncIndexFromScroll, 80);
        }, { passive: true });

        updateUI();
        window.addEventListener('resize', updateUI);
    })();

    // ============================================
    // LIGHTBOX LOGIC
    // ============================================

    const lightbox = document.querySelector('.mont_gallery_lightbox');
    const lightboxImg = document.querySelector('.mont_gallery_lightbox-image');
    const lightboxVideoContainer = document.querySelector('.mont_gallery_lightbox-video-container');
    const lightboxVideo = document.getElementById('lightbox-main-video');
    const closeBtn = document.querySelector('.mont_gallery_close-btn');
    
    // Select both Images and Video Containers in grid
    const triggers = document.querySelectorAll('.lightbox-trigger, .video-trigger');

    function resetLightbox() {
        lightbox.classList.remove('active');
        
        // Stop & Reset Video
        lightboxVideo.pause();
        lightboxVideo.querySelector('source').src = "";
        lightboxVideo.load();
        
        // Reset Image
        lightboxImg.src = "";
        lightboxImg.style.transform = "scale(1)";
        lightboxImg.classList.remove('custom-cursor-minus');
        lightboxImg.classList.add('custom-cursor-plus');
    }

    triggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            // On mobile, don't open lightbox for simple swipe browsing — still allow tap to zoom/view
            const type = this.getAttribute('data-media-type');
            const src = this.getAttribute('data-src');

            lightbox.classList.add('active'); 

            if (type === 'video') {
                // Show Video, Hide Image
                lightboxImg.classList.remove('visible');
                lightboxVideoContainer.classList.add('visible');

                // Set Video Source
                lightboxVideo.querySelector('source').src = src;
                lightboxVideo.load();
                
                // MUTE and PLAY automatically
                lightboxVideo.muted = true;
                lightboxVideo.play();

                // Ensure Lightbox Button shows 'Pause' icon initially (since it's playing)
                const lbBtn = lightboxVideoContainer.querySelector('.mont_video_overlay_btn');
                if(lbBtn) {
                    lbBtn.querySelector('.icon-play').style.display = 'none';
                    lbBtn.querySelector('.icon-pause').style.display = 'block';
                }

            } else {
                // Show Image, Hide Video
                lightboxVideoContainer.classList.remove('visible');
                lightboxImg.classList.add('visible');
                lightboxImg.src = src;
            }
        });
    });

    // Close Actions
    if(closeBtn) closeBtn.addEventListener('click', resetLightbox);
    
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox || e.target.classList.contains('mont_gallery_lightbox-content')) {
            resetLightbox();
        }
    });

    // Lightbox Image Zoom Logic
    if (lightboxImg) {
        lightboxImg.addEventListener('click', function(e) {
            e.stopPropagation();
            const currentTransform = this.style.transform;
            const isZoomed = currentTransform && currentTransform !== "scale(1)";

            if (isZoomed) {
                this.style.transform = "scale(1)";
                this.style.transformOrigin = "center center";
                this.classList.remove('custom-cursor-minus');
                this.classList.add('custom-cursor-plus');
            } else {
                const rect = this.getBoundingClientRect();
                const xPercent = ((e.clientX - rect.left) / rect.width) * 100;
                const yPercent = ((e.clientY - rect.top) / rect.height) * 100;
                this.style.transformOrigin = `${xPercent}% ${yPercent}%`;
                this.style.transform = "scale(2.5)";
                this.classList.remove('custom-cursor-plus');
                this.classList.add('custom-cursor-minus');
            }
        });

        lightboxImg.addEventListener('mousemove', function(e) {
            if (this.style.transform === "scale(2.5)") {
                const rect = this.getBoundingClientRect();
                const xPercent = ((e.clientX - rect.left) / rect.width) * 100;
                const yPercent = ((e.clientY - rect.top) / rect.height) * 100;
                this.style.transformOrigin = `${xPercent}% ${yPercent}%`;
            }
        });
    }
});
</script>