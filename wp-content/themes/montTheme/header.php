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

	$resolve_page_url = static function ($slugs, $fallback) {
		foreach ((array) $slugs as $slug) {
			$page = get_page_by_path($slug);
			if ($page instanceof WP_Post) {
				return get_permalink($page);
			}
		}
		return home_url($fallback);
	};
	$store_url = $resolve_page_url(
		array('store-location', 'store-locations', 'stores', 'butikk', 'butikker', 'find-us', 'monte-napoleone-partnere'),
		'/store-location/'
	);
	$about_url = $resolve_page_url(
		array('about-us', 'about', 'om-oss', 'om-monte-napoleone'),
		'/about-us/'
	);

	$b2b_url = $resolve_page_url( array( 'monte-connected-b2b' ), '/monte-connected-b2b/' );
	$b2c_url = home_url( '/product-category/skjorter-herre/' );
	$channel = $is_b2b_page ? 'b2b' : 'b2c';

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
    <header class="mont_header_sticky-header removeWhite " data-mont-channel="<?php echo esc_attr( $channel ); ?>">
        <nav class="mont_header_nav">
            <div class="mont_header_nav-left">
                <div class="mont_header_hamburger <?php echo (wp_is_mobile() ? 'mobile-menu': 'desktop-menu') ?>">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="mont_header_switchers mont_header_switchers--desktop">
                    <?php if (class_exists('DC_Product_Manager\\DC_Region_Currency')) : ?>
                        <?php echo do_shortcode('[dc_region_switcher context="desktop"]'); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mont_header_logo">
                <a href="<?php echo home_url(); ?>">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png" alt="Logo">
                </a>
            </div>
            <style>
                .current_page_item a {
                    font-weight: 500 !important;
                    color: black !important;
                }
            </style>
            <div class="mont_header_nav-right">

                 <?php
                    $menu_name = 'MainMenu';
                    $menu = wp_get_nav_menu_object($menu_name);

                    if ($menu) {
                        wp_nav_menu([
                        'menu'        => $menu->term_id,
                        'container'   => false,
                        'items_wrap'  => '<ul class="mont_header_menu ascf">%3$s</ul>',
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
        <div class="mont_header_second_menu_header">
            <div class="mont_header_mobile_close" aria-label="Close menu"></div>
        </div>

        <div class="mont_header_mobile_menu_body">
            <div class="mont_header_mobile_main_menu">
                <ul class="mont_header_menu_mobile">
                    <li class="mont_header_menu_mobile__item mont_header_menu_mobile__item--shirts">
                        <a href="#" class="mont_mega">Skjorter</a>
                        <i data-lucide="chevron-right" class="right-icon-menu"></i>
                    </li>
                    <?php
                    $menu_name = 'MainMenu';
                    $menu = wp_get_nav_menu_object($menu_name);

                    if ($menu) {
                        wp_nav_menu([
                        'menu'        => $menu->term_id,
                        'container'   => false,
                        'items_wrap'  => '%3$s',
                        'depth'       => 2,
                        ]);
                    }
                    ?>
                </ul>

                <div class="mont_header_mobile_footer">
                    <a class="mont_header_mobile_footer__link" href="<?php echo esc_url( $store_url ); ?>">Store Location</a>

                    <?php if (class_exists('DC_Product_Manager\\DC_Region_Currency')) : ?>
                        <div class="mont_header_mobile_footer__region">
                            <?php echo do_shortcode('[dc_region_switcher context="mobile-footer"]'); ?>
                        </div>
                    <?php endif; ?>

                    <a class="mont_header_mobile_footer__link" href="<?php echo esc_url( $about_url ); ?>">About us</a>
                </div>
            </div>

            <div class="mont_header_mobile_mega_menu">
                <div class="mont_header_mobile_back_button">
                    <span><i data-lucide="chevron-left"></i></span> Back
                </div>
                <div class="mont_header_mobile_mega_content">
                    <?php echo do_shortcode('[custom_elementor_template id="20468"]'); ?>
                </div>
            </div>
        </div>
    </div>
    </header>
    <div class="mont-channel-bar" role="navigation" aria-label="Butikk type" data-mont-channel="<?php echo esc_attr( $channel ); ?>">
        <div class="mont-channel-switch" data-active="<?php echo esc_attr( $channel ); ?>">
            <span class="mont-channel-switch__pill" aria-hidden="true"></span>
            <a
                href="<?php echo esc_url( $b2c_url ); ?>"
                class="mont-channel-switch__btn<?php echo $channel === 'b2c' ? ' is-active' : ''; ?>"
                data-channel="b2c"
                aria-current="<?php echo $channel === 'b2c' ? 'page' : 'false'; ?>"
            >
                <span class="mont-channel-switch__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6.5 8.5h11l-.9 10.2a1.6 1.6 0 0 1-1.6 1.5H9a1.6 1.6 0 0 1-1.6-1.5L6.5 8.5Z"/>
                        <path d="M9 8.5V7a3 3 0 0 1 6 0v1.5"/>
                        <path d="M9.5 12.5h5"/>
                    </svg>
                </span>
                <span class="mont-channel-switch__copy">
                    <span class="mont-channel-switch__label">B2C</span>
                    <span class="mont-channel-switch__hint">Privat</span>
                </span>
            </a>
            <a
                href="<?php echo esc_url( $b2b_url ); ?>"
                class="mont-channel-switch__btn<?php echo $channel === 'b2b' ? ' is-active' : ''; ?>"
                data-channel="b2b"
                aria-current="<?php echo $channel === 'b2b' ? 'page' : 'false'; ?>"
            >
                <span class="mont-channel-switch__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3.5" y="8" width="17" height="12" rx="2"/>
                        <path d="M8 8V6.8A1.8 1.8 0 0 1 9.8 5h4.4A1.8 1.8 0 0 1 16 6.8V8"/>
                        <path d="M3.5 13h17"/>
                        <path d="M12 13v7"/>
                    </svg>
                </span>
                <span class="mont-channel-switch__copy">
                    <span class="mont-channel-switch__label">B2B</span>
                    <span class="mont-channel-switch__hint">Bedrift</span>
                </span>
            </a>
        </div>
    </div>
