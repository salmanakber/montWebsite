<?php
class b2b extends getApi {
    public $path;
    public $url;
    public $api;

    function __construct($urlPath, $dirPath)
    {
        $this->path = $dirPath;
        $this->url = $urlPath;
        $this->api = 'sixerweb1234';

        // Call the activation hook
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));

        // Call the deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

        // Register shortcode
        add_shortcode('monte_b2b_shortcode', array($this, 'monte_b2b_shortcode'));


        // Call the enqueue scripts and styles hook
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_and_styles'));

        add_action('wp_footer', array($this, 'bubble_b2b_cart_button'));
    }
    
    public function plugin_activation() {
        // Create or update page with shortcode content
        $page_title = 'Monte Connected B2B';

        $shortcode_content = '[monte_b2b_shortcode]';


        $page_id = wp_insert_post(array(
            'post_title' => $page_title,
            'post_content' => $shortcode_content,
            'post_type' => 'page',
            'post_status' => 'publish',
        ));



        // Assign template to the newly created page
        if ($page_id) {
            $template_file = 'templates.php';
            update_post_meta($page_id, '_wp_page_template', $this->path . 'include/templates/' . $template_file);
        }

    }

    public function plugin_deactivation() {
        // Deactivation tasks, if any
    }

    /**
     * Decode CRM paths field to a list of relative image paths.
     *
     * @param mixed $paths
     * @return string[]
     */
    public function decode_product_paths( $paths ) {
        if ( is_array( $paths ) ) {
            return array_values( array_filter( array_map( 'strval', $paths ) ) );
        }
        if ( ! is_string( $paths ) || $paths === '' ) {
            return array();
        }
        $decoded = json_decode( $paths, true );
        if ( is_array( $decoded ) ) {
            return array_values( array_filter( array_map( 'strval', $decoded ) ) );
        }
        return array();
    }

    /**
     * Find a local WooCommerce product for a remote B2B catalog row (SKU / supplier SKU / fabric no).
     *
     * @param array $remote_product
     * @return WC_Product|null
     */
    public function find_local_wc_product( $remote_product ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return null;
        }

        static $cache = array();
        $remote_id = isset( $remote_product['id'] ) ? (string) $remote_product['id'] : '';
        if ( $remote_id !== '' && array_key_exists( $remote_id, $cache ) ) {
            return $cache[ $remote_id ];
        }

        $data = array();
        if ( ! empty( $remote_product['data'] ) ) {
            if ( is_array( $remote_product['data'] ) ) {
                $data = $remote_product['data'];
            } else {
                $decoded = json_decode( (string) $remote_product['data'], true );
                if ( is_array( $decoded ) ) {
                    $data = $decoded;
                }
            }
        }

        $candidates = array();
        foreach ( array( 'sku', 'SKU', 'supplier_sku', 'article', 'fabric_no', 'fabricNo', 'code' ) as $key ) {
            if ( ! empty( $data[ $key ] ) ) {
                $candidates[] = (string) $data[ $key ];
            }
        }
        if ( $remote_id !== '' ) {
            $candidates[] = $remote_id;
        }
        $candidates = array_values( array_unique( array_filter( array_map( 'trim', $candidates ) ) ) );

        $product = null;
        foreach ( $candidates as $sku ) {
            $pid = function_exists( 'wc_get_product_id_by_sku' ) ? wc_get_product_id_by_sku( $sku ) : 0;
            if ( $pid ) {
                $product = wc_get_product( $pid );
                if ( $product ) {
                    break;
                }
            }

            $by_meta = get_posts(
                array(
                    'post_type'              => 'product',
                    'post_status'            => 'publish',
                    'posts_per_page'         => 1,
                    'fields'                 => 'ids',
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                    'meta_query'             => array(
                        'relation' => 'OR',
                        array(
                            'key'   => '_supplier_sku',
                            'value' => $sku,
                        ),
                        array(
                            'key'   => '_fabric_no',
                            'value' => $sku,
                        ),
                    ),
                )
            );
            if ( ! empty( $by_meta[0] ) ) {
                $product = wc_get_product( (int) $by_meta[0] );
                if ( $product ) {
                    break;
                }
            }
        }

        // Last resort: title match on pname for B2B-flagged products.
        if ( ! $product && ! empty( $data['pname'] ) ) {
            global $wpdb;
            $title = sanitize_text_field( $data['pname'] );
            $found_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT p.ID FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_b2b_product'
                     WHERE p.post_type = 'product'
                       AND p.post_status = 'publish'
                       AND p.post_title = %s
                       AND m.meta_value IN ('1','yes')
                     LIMIT 1",
                    $title
                )
            );
            if ( $found_id ) {
                $product = wc_get_product( $found_id );
            }
        }

        if ( $remote_id !== '' ) {
            $cache[ $remote_id ] = $product;
        }
        return $product;
    }

    /**
     * Prefer local WC / DC Product Manager images; fall back to remote staff paths.
     *
     * @param array  $remote_product
     * @param string $remote_base
     * @return string[] Absolute image URLs
     */
    public function get_b2b_image_urls( $remote_product, $remote_base = 'https://dc-garment.com/staff/' ) {
        $urls = array();
        $local = $this->find_local_wc_product( $remote_product );

        if ( $local ) {
            $pid = $local->get_id();
            $dc  = get_post_meta( $pid, '_dc_product_image', true );
            if ( is_string( $dc ) && $dc !== '' && filter_var( $dc, FILTER_VALIDATE_URL ) ) {
                $urls[] = esc_url_raw( $dc );
            }
            $thumb_id = get_post_thumbnail_id( $pid );
            if ( $thumb_id ) {
                $thumb = wp_get_attachment_image_url( $thumb_id, 'large' );
                if ( $thumb ) {
                    $urls[] = $thumb;
                }
            }
            foreach ( (array) $local->get_gallery_image_ids() as $gid ) {
                $u = wp_get_attachment_image_url( (int) $gid, 'large' );
                if ( $u ) {
                    $urls[] = $u;
                }
            }
        }

        if ( empty( $urls ) ) {
            $base = trailingslashit( $remote_base );
            foreach ( $this->decode_product_paths( isset( $remote_product['paths'] ) ? $remote_product['paths'] : '' ) as $path ) {
                $path = ltrim( (string) $path, '/' );
                if ( $path !== '' ) {
                    $urls[] = $base . $path;
                }
            }
        }

        // Unique preserve order.
        $out = array();
        $seen = array();
        foreach ( $urls as $u ) {
            $u = esc_url_raw( $u );
            if ( ! $u || isset( $seen[ $u ] ) ) {
                continue;
            }
            $seen[ $u ] = true;
            $out[] = $u;
        }
        return $out;
    }

    /**
     * Render card image slider markup for B2B listing.
     *
     * @param string[] $urls
     * @param string   $alt
     * @return string
     */
    public function render_b2b_card_slider( $urls, $alt = '' ) {
        if ( empty( $urls ) ) {
            return '<div class="mont-card-slider mont-card-slider--empty"></div>';
        }
        $html  = '<div class="mont-card-slider" data-mont-card-slider>';
        $html .= '<div class="mont-card-slider__track">';
        foreach ( $urls as $i => $url ) {
            $html .= '<div class="mont-card-slider__slide">';
            $html .= '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"'
                . ( $i === 0 ? ' loading="eager"' : ' loading="lazy"' )
                . ' decoding="async" draggable="false">';
            $html .= '</div>';
        }
        $html .= '</div>';
        if ( count( $urls ) > 1 ) {
            $html .= '<div class="mont-card-slider__dots" aria-hidden="true">';
            foreach ( $urls as $i => $_u ) {
                $html .= '<button type="button" class="mont-card-slider__dot' . ( $i === 0 ? ' is-active' : '' ) . '" data-index="' . (int) $i . '" aria-label="Image ' . (int) ( $i + 1 ) . '"></button>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    function replace_variables_in_html_file($file_url, $variables) {
        $html_content = file_get_contents($file_url);
        if ($html_content === false) {
            return false;
        }
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $html_content = str_replace($placeholder, $value, $html_content);
        }
        return $html_content;
    }

    /**
     * Absolute image URLs for a local WC product (DC image, featured, gallery).
     *
     * @param WC_Product $wc_product
     * @return string[]
     */
    public function get_wc_product_image_urls( $wc_product ) {
        $urls = array();
        if ( ! $wc_product || ! is_a( $wc_product, 'WC_Product' ) ) {
            return $urls;
        }
        $pid = $wc_product->get_id();
        $dc  = get_post_meta( $pid, '_dc_product_image', true );
        if ( is_string( $dc ) && $dc !== '' && filter_var( $dc, FILTER_VALIDATE_URL ) ) {
            $urls[] = esc_url_raw( $dc );
        }
        $thumb_id = get_post_thumbnail_id( $pid );
        if ( $thumb_id ) {
            $thumb = wp_get_attachment_image_url( $thumb_id, 'large' );
            if ( $thumb ) {
                $urls[] = $thumb;
            }
        }
        foreach ( (array) $wc_product->get_gallery_image_ids() as $gid ) {
            $u = wp_get_attachment_image_url( (int) $gid, 'large' );
            if ( $u ) {
                $urls[] = $u;
            }
        }
        $out  = array();
        $seen = array();
        foreach ( $urls as $u ) {
            $u = esc_url_raw( $u );
            if ( ! $u || isset( $seen[ $u ] ) ) {
                continue;
            }
            $seen[ $u ] = true;
            $out[]      = $u;
        }
        if ( empty( $out ) && function_exists( 'wc_placeholder_img_src' ) ) {
            $out[] = wc_placeholder_img_src( 'large' );
        }
        return $out;
    }

    /**
     * Whether a WC product is marked B2B / Wholesale Channel.
     *
     * @param int $product_id
     * @return bool
     */
    public function is_b2b_wc_product( $product_id ) {
        $flag = get_post_meta( (int) $product_id, '_b2b_product', true );
        return in_array( (string) $flag, array( '1', 'yes' ), true );
    }

    /**
     * All published WC product IDs flagged for the B2B portal.
     *
     * @param int $term_id Optional product_cat term ID.
     * @return int[]
     */
    public function query_b2b_product_ids( $term_id = 0 ) {
        $args = array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
            'meta_query'             => array(
                array(
                    'key'     => '_b2b_product',
                    'value'   => array( '1', 'yes' ),
                    'compare' => 'IN',
                ),
            ),
        );
        $term_id = (int) $term_id;
        if ( $term_id > 0 ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            );
        }
        $ids = get_posts( $args );
        return array_map( 'intval', (array) $ids );
    }

    /**
     * Category tabs matching B2C shop (children of skjorter-herre, menu_order).
     * Products are local WC items flagged B2B / Wholesale.
     *
     * @return array<int, array{name:string,slug:string,products:int[]}>
     */
    public function get_local_b2b_catalog() {
        $ids = $this->query_b2b_product_ids();
        $by_term = array(); // term_id => product ids

        foreach ( $ids as $pid ) {
            $terms = get_the_terms( $pid, 'product_cat' );
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                continue;
            }
            foreach ( $terms as $term ) {
                $tid = (int) $term->term_id;
                if ( ! isset( $by_term[ $tid ] ) ) {
                    $by_term[ $tid ] = array();
                }
                $by_term[ $tid ][] = $pid;
            }
        }

        $ordered = array();
        $parent  = get_term_by( 'slug', 'skjorter-herre', 'product_cat' );
        $tab_terms = array();

        if ( $parent && ! is_wp_error( $parent ) ) {
            $tab_terms = get_terms(
                array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => (int) $parent->term_id,
                    'orderby'    => 'menu_order',
                    'order'      => 'ASC',
                )
            );
        }

        // Fallback: same top-level siblings as B2C when no children.
        if ( empty( $tab_terms ) || is_wp_error( $tab_terms ) ) {
            $tab_terms = get_terms(
                array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => 0,
                    'orderby'    => 'menu_order',
                    'order'      => 'ASC',
                    'number'     => 12,
                )
            );
        }

        if ( ! empty( $tab_terms ) && ! is_wp_error( $tab_terms ) ) {
            foreach ( $tab_terms as $term ) {
                if ( 'Uncategorized' === $term->name ) {
                    continue;
                }
                $tid = (int) $term->term_id;
                // Include products in this term or any descendant.
                $product_ids = isset( $by_term[ $tid ] ) ? $by_term[ $tid ] : array();
                $children    = get_terms(
                    array(
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => false,
                        'child_of'   => $tid,
                        'fields'     => 'ids',
                    )
                );
                if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
                    foreach ( $children as $cid ) {
                        if ( ! empty( $by_term[ (int) $cid ] ) ) {
                            $product_ids = array_merge( $product_ids, $by_term[ (int) $cid ] );
                        }
                    }
                }
                $product_ids = array_values( array_unique( array_map( 'intval', $product_ids ) ) );
                $label       = str_replace( ' skjorte', ' skjorter', $term->name );
                $ordered[ $tid ] = array(
                    'name'     => $label,
                    'slug'     => $term->slug,
                    'products' => $product_ids,
                );
            }
        }

        // Any B2B products not covered by the B2C tab tree → "Other".
        $shown = array();
        foreach ( $ordered as $block ) {
            $shown = array_merge( $shown, $block['products'] );
        }
        $orphan = array_values( array_diff( $ids, $shown ) );
        if ( ! empty( $orphan ) ) {
            $ordered[0] = array(
                'name'     => __( 'Andre', 'mont-b2b' ),
                'slug'     => 'andre',
                'products' => $orphan,
            );
        }

        return $ordered;
    }

    public function getCategory()
    {
        $catalog = $this->get_local_b2b_catalog();

        if ( ! empty( $catalog ) ) {

            echo '<div class="category-slider-container mont-cat-tabs">';
            echo '<button type="button" class="slider-arrow prev-arrow" aria-label="Previous categories">'
                . '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                . '</button>';
            echo '<div class="category-slider-wrapper mont-cat-tabs__scroller">';
            echo '<ul class="category-slider mont-cat-tabs__list" id="b2bmenu" role="tablist">';

            $first_category = true;

            foreach ( $catalog as $term_id => $category ) {
                $category_name = $category['name'];
                $slug_base     = ! empty( $category['slug'] ) ? $category['slug'] : sanitize_title( $category_name );
                $tab_id        = 'tab-' . $slug_base;
                $content_id    = 'content-' . $slug_base;

                $class  = $first_category ? 'active' : '';
                $class2 = $first_category ? 'active-li is-active' : '';

                echo '<li class="category-item mont-cat-tabs__item mont-cat-item ' . esc_attr( $class2 ) . '">';
                echo '<button class="nav-link-monte-b2b mont-cat-tabs__link ' . esc_attr( $class ) . '" id="' . esc_attr( $tab_id ) . '" data-bs-toggle="tab" data-bs-target="#' . esc_attr( $content_id ) . '" type="button" role="tab" aria-controls="' . esc_attr( $content_id ) . '" aria-selected="' . ( $first_category ? 'true' : 'false' ) . '">' . esc_html( $category_name ) . '</button>';
                echo '</li>';

                $first_category = false;
            }

            echo '</ul>';
            echo '</div>';
            echo '<button type="button" class="slider-arrow next-arrow" aria-label="Next categories">'
                . '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                . '</button>';
            echo '</div>';

            echo '<div class="tab-content tabb2b" id="myTabContent">';

            $first_category = true;

            foreach ( $catalog as $term_id => $category ) {
                $category_name = $category['name'];
                $slug_base     = ! empty( $category['slug'] ) ? $category['slug'] : sanitize_title( $category_name );
                $tab_id        = 'tab-' . $slug_base;
                $content_id    = 'content-' . $slug_base;
                $class         = $first_category ? ' show active' : '';

                echo '<div class="tab-pane-monte-b2b ' . esc_attr( $class ) . '" id="' . esc_attr( $content_id ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $tab_id ) . '">';
                echo '<div class="container-fluid ">';
                echo '<div class="row">';

                if ( ! empty( $category['products'] ) ) {
                    foreach ( $category['products'] as $product_id ) {
                        $wc = wc_get_product( $product_id );
                        if ( ! $wc ) {
                            continue;
                        }
                        $pname      = $wc->get_name();
                        $image_urls = $this->get_wc_product_image_urls( $wc );
                        $in_wishlist = isset( $_SESSION['custom_wishlist'] )
                            && in_array( $product_id, $_SESSION['custom_wishlist'], false );

                        echo '<div class="col-sm-3"><a class="b2b-product-card-link" href="' . esc_url( add_query_arg( 'productb2b', $product_id ) ) . '">';
                        echo '<div class="product-img-b2b" style="position:relative">';
                        ?>
                     <div class="wishlist-toggle <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>"
                       data-product-id="<?php echo esc_attr( $product_id ); ?>">
                       <i class="heart-icon"></i>
                   </div>
                   <?php
                        echo $this->render_b2b_card_slider( $image_urls, $pname );
                        echo '</div>';
                        echo '<div class="product-name-b2b">';
                        echo '<p>' . esc_html( $pname ) . '</p>';
                        echo '</div>';
                        echo '</a></div>';
                    }
                } else {
                    echo '<div class="b2b-empty-category">';
                    echo '<p class="b2b-empty-category__title">Nothing in this collection yet</p>';
                    echo '<p class="b2b-empty-category__text">New styles will appear here soon. Browse another category in the meantime.</p>';
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';

                $first_category = false;
            }

            echo '</div>';
        } else {
            echo '<div class="b2b-empty-category">';
            echo '<p class="b2b-empty-category__title">No B2B products yet</p>';
            echo '<p class="b2b-empty-category__text">Mark products as <strong>B2B / Wholesale Channel</strong> in the CRM stock manager to show them here.</p>';
            echo '</div>';
        }
    }

public function getProductDetails($pid, $remoteURL = '')
{
    $rightColumn = '';
    $leftColumn  = '';
    $pid         = (int) $pid;
    $wc          = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;

    if ( ! $wc || ! $this->is_b2b_wc_product( $pid ) ) {
        $rightColumn = '<div class="b2b-empty-category"><p class="b2b-empty-category__title">Product not found</p><p class="b2b-empty-category__text">This item is not available in the B2B portal.</p></div>';
        return array(
            'right' => $rightColumn,
            'left'  => '',
            'moq'   => 1,
            'data'  => array(
                'moq'     => 1,
                'pname'   => '',
                'color'   => '',
                'weight'  => '',
                'quality' => '',
            ),
        );
    }

    $image_urls = $this->get_wc_product_image_urls( $wc );
    $pname      = $wc->get_name();
    $moq        = get_post_meta( $pid, '_moq', true );
    $moq        = ( $moq === '' || $moq === null ) ? 1 : $moq;
    $color      = get_post_meta( $pid, '_fabric_color', true );
    if ( $color === '' || $color === null ) {
        $color = get_post_meta( $pid, '_fabric_color_english', true );
    }
    $weight  = get_post_meta( $pid, '_weight', true );
    $quality = get_post_meta( $pid, '_quality', true );
    $stock   = $wc->managing_stock() ? (int) $wc->get_stock_quantity() : '';

    // Desktop Gallery Grid
    $rightColumn .= '<div class="mont_gallery_wrapper">';
    $rightColumn .= '<div class="mont_gallery_image-grid">';
    foreach ( $image_urls as $index => $url ) {
        $rightColumn .= '<div class="mont_gallery_image-container">';
        $rightColumn .= '<img src="' . esc_url( $url ) . '" 
        class="mont_gallery_main-image" 
        alt="' . esc_attr( $pname . ' ' . ( $index + 1 ) ) . '" 
        data-index="' . $index . '" 
        data-gallerysrc="' . esc_url( $url ) . '"'
        . ( $index === 0 ? ' loading="eager" fetchpriority="high"' : ' loading="eager"' ) . '>';
        $rightColumn .= '</div>';
    }
    $rightColumn .= '</div>';

    $rightColumn .= '<div class="mont_gallery_navigation-dots">';
    foreach ( $image_urls as $index => $url ) {
        $rightColumn .= '<div class="mont_gallery_dot ' . ( $index === 0 ? 'active' : '' ) . '"></div>';
    }
    $rightColumn .= '</div>';

    $rightColumn .= '<div class="mobile-view-b2b loop owl-carousel owl-theme">';
    foreach ( $image_urls as $url ) {
        $rightColumn .= '<div class="item"><img src="' . esc_url( $url ) . '" class="b2b-img" loading="eager" alt="' . esc_attr( $pname ) . '"></div>';
    }
    $rightColumn .= '</div>';
    $rightColumn .= '</div>';

    $rightColumn .= '<div class="mont_gallery_lightbox">';
    $rightColumn .= '<div class="mont_gallery_close-btn">×</div>';
    $rightColumn .= '<div class="mont_gallery_zoom-controls">';
    $rightColumn .= '<div class="mont_gallery_zoom-btn mont_gallery_zoom-in">+</div>';
    $rightColumn .= '<div class="mont_gallery_zoom-btn mont_gallery_zoom-out">−</div>';
    $rightColumn .= '<div class="mont_gallery_zoom-btn mont_gallery_restore">↺</div>';
    $rightColumn .= '</div>';
    $rightColumn .= '<div class="mont_gallery_lightbox-content">';
    $rightColumn .= '<img src="/placeholder.svg" class="mont_gallery_lightbox-image" alt="Lightbox Image">';
    $rightColumn .= '<div class="mont_gallery_thumbnails">';

    foreach ( $image_urls as $index => $url ) {
        $rightColumn .= '<img src="' . esc_url( $url ) . '" 
        class="mont_gallery_thumbnail ' . ( $index === 0 ? 'active' : '' ) . '" 
        alt="Thumbnail ' . ( $index + 1 ) . '" 
        data-index="' . $index . '">';
    }

    $rightColumn .= '</div>';
    $rightColumn .= '</div>';
    $rightColumn .= '</div>';

    $leftColumn .= '<input type="hidden" id="moq" name="moq" value="' . esc_attr( $moq ) . '" >';
    $leftColumn .= '<input type="hidden" id="pname" name="pname" value="' . esc_attr( $pname ) . '" >';
    $leftColumn .= '<input type="hidden" id="pcolor" name="pcolor" value="' . esc_attr( $color ) . '" >';
    $leftColumn .= '<input type="hidden" id="pweight" name="pweight" value="' . esc_attr( $weight ) . '" >';
    $leftColumn .= '<input type="hidden" id="pquality" name="pquality" value="' . esc_attr( $quality ) . '" >';
    $leftColumn .= '<input type="hidden" id="pstock" name="pstock" value="' . esc_attr( $stock ) . '" >';
    $leftColumn .= '<input type="hidden" id="p_wc_id" name="p_wc_id" value="' . esc_attr( $pid ) . '" >';

    $data = array(
        'moq'     => $moq,
        'pname'   => $pname,
        'color'   => $color,
        'weight'  => $weight,
        'quality' => $quality,
    );

    return array(
        'right' => $rightColumn,
        'left'  => $leftColumn,
        'moq'   => $moq,
        'data'  => $data,
    );
}



    // Shortcode callback function
public function monte_b2b_shortcode($atts) {
    // Your shortcode content goes here
   session_start();
   $collar_type = '';
   $cuff_type = '';
   $output = '<div class="b2b-contents">';
    // Include your template HTML here
   foreach (get_field('choose_collar_update', 'option') as $key => $value){
    $is_sel = ( ( $value['selected'] ?? '' ) === 'Yes' );
    $collar_type .= '<label class="b2b-check-to-go-collar b2b-option-tile' . ( $is_sel ? ' is-selected' : '' ) . '">'
      . '<input type="radio" name="collar_type" value="' . esc_attr( ucfirst( $value['name'] ) ) . '" ' . ( $is_sel ? 'checked' : '' ) . '>'
      . '<input type="hidden" name="data_collar_type_transmit_129" value="' . esc_url( $value['image'] ) . '">'
      . '<span class="b2b-option-tile__media"><img src="' . esc_url( $value['image'] ) . '" alt="' . esc_attr( ucfirst( $value['name'] ) ) . '"></span>'
      . '<span class="text-and-check b2b-option-tile__meta">'
      . '<span class="blank-check ' . ( $is_sel ? 'checkbtn' : '' ) . '"></span>'
      . '<span class="b2b-option-tile__name">' . esc_html( ucfirst( $value['name'] ) ) . '</span>'
      . '</span></label>';
}

   foreach (get_field('choose_cuff_update', 'option') as $key => $value){
    $is_sel = ( ( $value['selected'] ?? '' ) === 'Yes' );
    $cuff_type .= '<label class="b2b-check-to-go-cuff b2b-option-tile' . ( $is_sel ? ' is-selected' : '' ) . '">'
      . '<input type="radio" name="cuff_type" value="' . esc_attr( ucfirst( $value['name'] ) ) . '" ' . ( $is_sel ? 'checked' : '' ) . '>'
      . '<input type="hidden" name="data_cuff_type_transmit_111" value="' . esc_url( $value['image'] ) . '">'
      . '<span class="b2b-option-tile__media"><img src="' . esc_url( $value['image'] ) . '" alt="' . esc_attr( ucfirst( $value['name'] ) ) . '"></span>'
      . '<span class="text-and-check b2b-option-tile__meta">'
      . '<span class="blank-check ' . ( $is_sel ? 'checkbtn' : '' ) . '"></span>'
      . '<span class="b2b-option-tile__name">' . esc_html( ucfirst( $value['name'] ) ) . '</span>'
      . '</span></label>';
}

        
$pdetails = get_query_var('productb2b');
if(isset($_GET['productb2b']) AND !empty($_GET['productb2b'])){
    $product_details = $this->getProductDetails( $_GET['productb2b'] );
     // $fabricDetail = '<input type="hidden" name="fabricColor" value="'.$product_details['data']['color'].'"> '
    // echo '<br><br><br><br>';
    // print_r($product_details['data']);
    $return_form_block = '';
    if ( function_exists( 'mont_return_form_has_form' ) && mont_return_form_has_form() && function_exists( 'mont_return_form_button' ) ) {
        $labels = function_exists( 'mont_return_form_labels' ) ? mont_return_form_labels() : array( 'button' => 'Return form' );
        $return_form_block = '<div class="mont_straight_line mont_straight_line--b2b mont_return-form-block">'
            . '<span class="mont_straight_line__label">' . esc_html( $labels['button'] ) . '</span>'
            . '<div class="mont_pdp-doc-buttons">' . mont_return_form_button() . '</div>'
            . '</div>';
    }

    $template_content_b2b_details = $this->replace_variables_in_html_file
    (
        $this->path . 'include/templates/details.php' ,
        array(
            'images' => $product_details['right'],
            'details' => $product_details['left'],
            'moq' => 'This color requires a minimum order of '.$product_details['moq'].' shirts total',
            'done' => (isset($_SESSION['products']) ? 'add-to-cart-button-bubble' : 'e'),
            'collar' => $collar_type,
            'cuff' =>  $cuff_type,
            'return_form_block' => $return_form_block,
        ));
    $output .= $template_content_b2b_details;
}
else
{
    $template_content = $this->replace_variables_in_html_file($this->path . 'include/templates/templates.php' , array('data' => $this->getCategory()));
    $output .= $template_content;
}
    // You can include any additional HTML or PHP code here
$output .= '</div>';
return $output;
}
public function bubble_b2b_cart_button(){
    require_once $this->path . 'include/templates/model.php';
}

public function enqueue_scripts_and_styles() {
        // Enqueue scripts and styles if needed
    $b2b_style_ver = @filemtime( $this->path . 'assets/css/style.css' ) ?: '2.1';
    $b2b_pdp_ver   = @filemtime( $this->path . 'assets/css/b2b-pdp.css' ) ?: '2.1';
    $b2b_modals_ver = @filemtime( $this->path . 'assets/css/b2b-modals.css' ) ?: '1.5';
    wp_enqueue_style('b2b-style', $this->url . 'assets/css/style.css', array(), (string) $b2b_style_ver);
    wp_enqueue_style('b2b-pdp', $this->url . 'assets/css/b2b-pdp.css', array('b2b-style'), (string) $b2b_pdp_ver);
    wp_enqueue_style('b2b-modals', $this->url . 'assets/css/b2b-modals.css', array('b2b-style'), (string) $b2b_modals_ver);
    wp_enqueue_style('b2b-notify', $this->url . 'assets/css/notify.css', array(), '1.0');
    wp_enqueue_style('b2b-owl-css-min', $this->url . 'assets/css/owl.carousel.min.css', array(), '1.0');
    wp_enqueue_style('b2b-owl-default-css', $this->url . 'assets/css/owl.theme.default.min.css', array(), '1.0');
    wp_enqueue_style('b2b-fontaweseom', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css');
    wp_enqueue_script('b2b-notify-script', $this->url . 'assets/js/b2b-notify.js', array('jquery'), '1.0', true);
    wp_enqueue_script('b2b-custom-script', $this->url . 'assets/js/custom.js', array('jquery'), '1.7', true);
    wp_enqueue_script('b2b-owl-script', $this->url . 'assets/js/owl.carousel.js', array('jquery'), '1.0', true);

    // Shared category tab design (theme file when available).
    $theme_tabs = get_template_directory() . '/assets/category-tabs.css';
    if ( file_exists( $theme_tabs ) ) {
        wp_enqueue_style(
            'mont-category-tabs',
            get_template_directory_uri() . '/assets/category-tabs.css',
            array( 'b2b-style' ),
            (string) filemtime( $theme_tabs )
        );
    }
    $theme_tabs_js = get_template_directory() . '/assets/category-tabs.js';
    if ( file_exists( $theme_tabs_js ) ) {
        wp_enqueue_script(
            'mont-category-tabs',
            get_template_directory_uri() . '/assets/category-tabs.js',
            array(),
            (string) filemtime( $theme_tabs_js ),
            true
        );
    }

    $b2b_page = get_page_by_path( 'monte-connected-b2b' );
    $b2b_url  = $b2b_page ? get_permalink( $b2b_page ) : home_url( '/monte-connected-b2b/' );

    wp_localize_script('b2b-custom-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
    wp_localize_script('b2b-custom-script', 'ajaxurl', array(
        'url' => admin_url('admin-ajax.php'),
    ));
    wp_add_inline_script(
        'b2b-custom-script',
        'var b2bShopUrl = ' . wp_json_encode( $b2b_url ) . ';',
        'before'
    );

}
}

?>