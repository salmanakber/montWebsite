<?php
/**
 * Clean Category Slider (No Fallback)
 */

global $category_slider_displayed;

if (!isset($category_slider_displayed) || $category_slider_displayed !== true) {

    $current_category = get_queried_object();

    if (is_product_category() && isset($current_category->term_id)) {

        $top_level_id = $current_category->term_id;
        $selected_id = $current_category->term_id;

        // Find top-level parent
        while ($current_category->parent != 0) {
            $current_category = get_term($current_category->parent, 'product_cat');
            $top_level_id = $current_category->term_id;
        }

        // Get direct children of top-level parent
        $categories_to_show = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => $top_level_id,
        ]);

        if (!empty($categories_to_show) && !is_wp_error($categories_to_show)) {
            $slider_id = 'category_slider_' . rand(1000, 9999);
            ?>
            
            <!-- START CATEGORY SLIDER -->
            <div class="category-slider-container ssds">
                <div class="category-slider" id="<?php echo esc_attr($slider_id); ?>">
                    <?php foreach ($categories_to_show as $category) : ?>
                        <?php if ($category->name !== 'Uncategorized') : ?>
                            <a href="<?php echo esc_url(get_term_link($category)); ?>"
                               class="category-item <?php echo ($category->term_id == $selected_id) ? 'category-active' : ''; ?>">
								
                                <?php echo esc_html(str_replace(' skjorte',' skjorter',$category->name)); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const slider = document.getElementById('<?php echo esc_js($slider_id); ?>');
                if (!slider) return;

                const activeCategory = slider.querySelector('.category-active');
                if (activeCategory) {
                    activeCategory.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'center'
                    });
                }

                let isDown = false;
                let startX, scrollLeft;

                slider.addEventListener('mousedown', (e) => {
                    isDown = true;
                    startX = e.pageX - slider.offsetLeft;
                    scrollLeft = slider.scrollLeft;
                });
                slider.addEventListener('mouseleave', () => isDown = false);
                slider.addEventListener('mouseup', () => isDown = false);
                slider.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const x = e.pageX - slider.offsetLeft;
                    slider.scrollLeft = scrollLeft - (x - startX) * 2;
                });
            });
            </script>
            <!-- END CATEGORY SLIDER -->

            <?php
            $category_slider_displayed = true;
        }
    }
}
?>


<div class="mont-product-list-category" style="margin-top: 51px;">
    <?php
    // Only run this code once
    global $mont_slider_displayed;
    if (!isset($mont_slider_displayed) || $mont_slider_displayed !== true) {
        // Your slider code here
             echo do_shortcode('[custom_product_grid category="'.get_queried_object()->slug.'" limit="all"]');
        // Mark as displayed
        $mont_slider_displayed = true;
    }
    ?>
</div>