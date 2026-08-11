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

	$b2b_hub = $resolve_page_url( array( 'monte-connected-b2b' ), '/monte-connected-b2b/' );
	$b2c_hub = home_url( '/product-category/skjorter-herre/' );
	$b2b_url = $b2b_hub;
	$b2c_url = $b2c_hub;
	$channel = $is_b2b_page ? 'b2b' : 'b2c';
	$b2b_switch_disabled = false;
	$b2b_switch_title    = '';

	// Smart detail-page switching: same WC product ID across B2C ↔ B2B.
	$smart_product_id = 0;
	if ( function_exists( 'is_product' ) && is_product() ) {
		$smart_product_id = (int) get_queried_object_id();
	} elseif ( ! empty( $_GET['productb2b'] ) ) {
		$smart_product_id = (int) $_GET['productb2b'];
	}

	if ( $smart_product_id > 0 ) {
		$b2b_flag      = get_post_meta( $smart_product_id, '_b2b_product', true );
		$is_b2b_marked = in_array( (string) $b2b_flag, array( '1', 'yes' ), true );
		$b2c_permalink = get_permalink( $smart_product_id );

		if ( $channel === 'b2b' || ( ! empty( $_GET['productb2b'] ) && (int) $_GET['productb2b'] === $smart_product_id ) ) {
			// On B2B details → B2C product page for the same ID.
			$b2b_url = add_query_arg( 'productb2b', $smart_product_id, $b2b_hub );
			$b2c_url = $b2c_permalink ? $b2c_permalink : $b2c_hub;
		} else {
			// On B2C single product → B2B details if marked wholesale.
			$b2c_url = $b2c_permalink ? $b2c_permalink : $b2c_hub;
			if ( $is_b2b_marked ) {
				$b2b_url = add_query_arg( 'productb2b', $smart_product_id, $b2b_hub );
			} else {
				$b2b_switch_disabled = true;
				$b2b_url             = '#';
				$b2b_switch_title    = 'Ikke tilgjengelig i B2B';
			}
		}
	}

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
        <?php
        $ship_lang = 'en';
        if ( class_exists( 'DC_Product_Manager\\DC_Region_Currency' ) ) {
            $ship_lang = \DC_Product_Manager\DC_Region_Currency::get_current_lang();
        }
        $ship_copy = array(
            'en' => 'FREE SHIPPING WORLDWIDE',
            'nb' => 'GRATIS FRAKT VERDEN OVER',
            'it' => 'SPEDIZIONE GRATUITA IN TUTTO IL MONDO',
            'vi' => 'MIỄN PHÍ VẬN CHUYỂN TOÀN CẦU',
        );
        $ship_text = isset( $ship_copy[ $ship_lang ] ) ? $ship_copy[ $ship_lang ] : $ship_copy['en'];
        ?>
        <div class="mont_header_mobile_ship" role="note">
            <span class="mont_header_mobile_ship__text"><?php echo esc_html( $ship_text ); ?></span>
        </div>

        <div class="mont_header_second_menu_header">
            <div class="mont_header_mobile_close" aria-label="Close menu"></div>
        </div>

        <div class="mont_header_mobile_menu_body">
            <div class="mont_header_mobile_main_menu">
                <div class="mont_header_mobile_brand">
                    <a class="mont_header_mobile_brand__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="Monte Napoleone">
                    </a>
                    <span class="mont_header_mobile_brand__year" aria-hidden="true">1974</span>
                </div>

                <?php if ( class_exists( 'DC_Product_Manager\\DC_Region_Currency' ) ) : ?>
                    <div class="mont_header_mobile_lang">
                        <?php echo do_shortcode( '[dc_region_switcher context="mobile-footer"]' ); ?>
                    </div>
                <?php endif; ?>

                <ul class="mont_header_menu_mobile">
                    <li class="mont_header_menu_mobile__item mont_header_menu_mobile__item--shirts">
                        <a href="#" class="mont_mega">Skjorter</a>
                        <i data-lucide="chevron-right" class="right-icon-menu"></i>
                    </li>
                    <?php
                    $menu_name = 'MainMenu';
                    $menu = wp_get_nav_menu_object( $menu_name );

                    if ( $menu ) {
                        wp_nav_menu( array(
                            'menu'        => $menu->term_id,
                            'container'   => false,
                            'items_wrap'  => '%3$s',
                            'depth'       => 2,
                        ) );
                    }
                    ?>
                </ul>

                <div class="mont_header_mobile_footer">
                    <a class="mont_header_mobile_footer__link" href="<?php echo esc_url( $store_url ); ?>">Store Location</a>
                    <a class="mont_header_mobile_footer__link" href="<?php echo esc_url( $about_url ); ?>">About us</a>
                </div>
            </div>

            <div class="mont_header_mobile_mega_menu">
                <div class="mont_header_mobile_back_button">
                    <span><i data-lucide="chevron-left"></i></span> Back
                </div>
                <div class="mont_header_mobile_mega_content">
                    <?php echo do_shortcode( '[custom_elementor_template id="20468"]' ); ?>
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
                <span class="mont-channel-switch__copy">
                    <span class="mont-channel-switch__label">B2C Skjorter</span>
                </span>
            </a>
            <a
                href="<?php echo $b2b_switch_disabled ? '#' : esc_url( $b2b_url ); ?>"
                class="mont-channel-switch__btn<?php echo $channel === 'b2b' ? ' is-active' : ''; ?><?php echo $b2b_switch_disabled ? ' is-disabled' : ''; ?>"
                data-channel="b2b"
                aria-current="<?php echo $channel === 'b2b' ? 'page' : 'false'; ?>"
                <?php if ( $b2b_switch_disabled ) : ?>
                    aria-disabled="true"
                    tabindex="-1"
                    title="<?php echo esc_attr( $b2b_switch_title ); ?>"
                    onclick="return false;"
                <?php endif; ?>
            >
                <span class="mont-channel-switch__copy">
                    <span class="mont-channel-switch__label">B2B Skjorter</span>
                </span>
            </a>
        </div>
        <?php if ( $b2b_switch_disabled ) : ?>
            <span class="mont-channel-switch__note"><?php echo esc_html( $b2b_switch_title ); ?></span>
        <?php endif; ?>
    </div>
