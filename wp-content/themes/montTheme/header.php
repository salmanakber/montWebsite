<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php
	$is_b2b_page = is_page('monte-connected-b2b') || isset($_GET['productb2b']);
	if (!$is_b2b_page) :
	?>
	<style>
	.top-bar h3 {
    font-weight: 500;
}
.top-bar {
    padding: 0 !important;
    font-size: 10px;
    display: flex;
    justify-content: center;
    align-content: center;
    align-items: center;
    flex-direction: column;
    height: 21px;
}
	</style>
<!-- <div class="top-bar">
	
	<h3>
		Gratis frakt over hele verden

	</h3>
	</div> -->
	<?php endif; ?>
    <header class="mont_header_sticky-header removeWhite ">
        <nav class="mont_header_nav">
            <div class="mont_header_nav-left">
                <div class="mont_header_hamburger <?php echo (wp_is_mobile() ? 'mobile-menu': 'desktop-menu') ?>">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="mont_header_switchers mont_header_switchers--desktop">
                    <!-- <div class="mont_header_language-switcher"> -->
                        <?php //echo do_shortcode('[gtranslate]'); ?>
                    <!-- </div> -->
                    <?php if (class_exists('DC_Product_Manager\\DC_Region_Currency')) : ?>
                        <?php echo do_shortcode('[dc_region_switcher]'); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mont_header_logo">
                <a href="<?php echo home_url(); ?>">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png" alt="Logo">
                </a>
            </div>
            <div class="mont_header_nav-right">

                 <?php
                    $menu_name = 'MainMenu'; // Change this to your menu name
                    $menu = wp_get_nav_menu_object($menu_name);

                    if ($menu) {
                        wp_nav_menu([
                        'menu'        => $menu->term_id, // Use the menu ID to display
                        'container'   => false,
                        'items_wrap'  => '<ul class="mont_header_menu">%3$s</ul>',
                        'depth'       => 2,
                        ]);
                    } else {
                        echo '<p style="color: red;">Menu not found: ' . $menu_name . '</p>';
                    }
                ?>

            
            <div class="mont_header_cart">
                <span class="mont_header_cart-icon" onclick="window.location.href='/cart'">
                    <img src="<?php echo wp_get_upload_dir()['baseurl']; ?>/2025/03/cart.png" alt="cart">
                </span>
                <span class="mont_header_cart-counter"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
            </div>
        </div>
    </nav>
    <?php require_once get_template_directory(). '/template/search-form.php'; ?>
    <div class="mont_header_mega-menu">
        <div class="mont_header_mega-menu-content">
            <?php echo do_shortcode('[custom_elementor_template id="20327"]'); ?>
        </div>
    </div>

    <!-- Mobile Menu Structure -->
    <div class="mont_header_mobile_menu_container">
        <!-- Close Button -->
        <div class="mont_header_second_menu_header">
            <div class="mont_header_mobile_close">
<!--                 <i data-lucide="x"></i> -->
            </div>
            <div class="mont_header_switchers">
                <!-- <div class="mont_header_language-switcher">
                    <?php// echo do_shortcode('[gtranslate]'); ?>
                </div> -->
                <?php if (class_exists('DC_Product_Manager\\DC_Region_Currency')) : ?>
                    <?php echo do_shortcode('[dc_region_switcher]'); ?>
                <?php endif; ?>
            </div>
            <!-- Main Mobile Menu -->
            <div class="mont_header_mobile_main_menu">
                <ul class="mont_header_menu_mobile">
                    <li><a href="#" class="mont_mega">Skjorter</a> <i data-lucide="chevron-right" class="right-icon-menu"></i></li>
                 <?php
                    $menu_name = 'MainMenu'; // Change this to your menu name
                    $menu = wp_get_nav_menu_object($menu_name);

                    if ($menu) {
                        wp_nav_menu([
                        'menu'        => $menu->term_id, // Use the menu ID to display
                        'container'   => false,
                        'items_wrap'  => '%3$s',
                        'depth'       => 2,
                        ]);
                    } else {
                        echo '<p style="color: red;">Menu not found: ' . $menu_name . '</p>';
                    }
                ?>
                </ul>
            </div>

            <!-- Mobile Mega Menu -->
            <div class="mont_header_mobile_mega_menu">
                <div class="mont_header_mobile_back_button">
                    <span><i data-lucide="chevron-left"></i></span> Back
                </div>
                <div class="mont_header_mobile_mega_content">
                    <?php echo do_shortcode('[custom_elementor_template id="20468"]'); ?> 
                </div>
            </div>
        </div>
    </header>

  

  
