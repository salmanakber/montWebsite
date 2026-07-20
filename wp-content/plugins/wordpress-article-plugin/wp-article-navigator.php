<?php
/**
 * Plugin Name: Article Navigator
 * Plugin URI: https://sixerweb.com
 * Description: A custom article navigation plugin with sticky sidebar and category filtering
 * Version: 1.0.0
 * Author: Salman AKber
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Article_Navigator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_load_posts_by_category', array($this, 'load_posts_by_category'));
        add_action('wp_ajax_nopriv_load_posts_by_category', array($this, 'load_posts_by_category'));
        add_action('wp_ajax_load_single_post', array($this, 'load_single_post'));
        add_action('wp_ajax_nopriv_load_single_post', array($this, 'load_single_post'));
    }
    
    public function init() {
        add_shortcode('article_navigator', array($this, 'article_navigator_shortcode'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('article-navigator-js', plugin_dir_url(__FILE__) . 'assets/article-navigator.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('article-navigator-css', plugin_dir_url(__FILE__) . 'assets/article-navigator.css', array(), '1.0.0');
        
        // Localize script for AJAX
        wp_localize_script('article-navigator-js', 'article_navigator_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('article_navigator_nonce')
        ));
    }
    
    public function article_navigator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 10,
            'exclude_categories' => '',
            'include_categories' => ''
        ), $atts);
        
        ob_start();
        $this->render_article_navigator($atts);
        return ob_get_clean();
    }
    
    private function render_article_navigator($atts) {
        // Get categories
        $category_args = array(
            'taxonomy' => 'category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        );
        
        if (!empty($atts['include_categories'])) {
            $category_args['include'] = explode(',', $atts['include_categories']);
        }
        
        if (!empty($atts['exclude_categories'])) {
            $category_args['exclude'] = explode(',', $atts['exclude_categories']);
        }
        
        $categories = get_terms($category_args);
        
        // Get initial posts (all posts)
        $post_args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $atts['posts_per_page'],
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $posts = get_posts($post_args);
        $first_post = !empty($posts) ? $posts[0] : null;
        
        ?>
        <div id="article-navigator-container" class="article-navigator-wrapper">
            <!-- Left Sidebar (30% width) -->
            <div class="article-sidebar">
                <div class="sidebar-sticky">
                    <h3 class="sidebar-title">Articles</h3>
                    <div id="posts-list" class="posts-list">
                        <?php $this->render_posts_list($posts); ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Content Area (70% width) -->
            <div class="article-content">
                <!-- Categories Filter -->
                <div class="categories-filter">
                    <h3>Categories</h3>
                    <div class="category-buttons">
                        <button class="category-btn active" data-category="all">All Posts</button>
                        <?php foreach ($categories as $category): ?>
                            <button class="category-btn" data-category="<?php echo esc_attr($category->term_id); ?>">
                                <?php echo esc_html($category->name); ?>
                                <span class="post-count">(<?php echo $category->count; ?>)</span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Article Display Area -->
                <div id="article-display" class="article-display">
                    <?php if ($first_post): ?>
                        <?php $this->render_single_post($first_post); ?>
                    <?php else: ?>
                        <div class="no-posts">
                            <h3>No articles found</h3>
                            <p>Please check back later for new content.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Loading Spinner -->
            <div id="loading-spinner" class="loading-spinner" style="display: none;">
                <div class="spinner"></div>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Set first post as active by default
                <?php if ($first_post): ?>
                $('.posts-list .post-item:first').addClass('active');
                <?php endif; ?>
            });
        </script>
        <?php
    }
    
    private function render_posts_list($posts) {
        if (empty($posts)) {
            echo '<div class="no-posts-sidebar">No posts found</div>';
            return;
        }
        
        foreach ($posts as $post) {
            $featured_image = get_the_post_thumbnail_url($post->ID, 'medium');
            if (!$featured_image) {
                $featured_image = plugin_dir_url(__FILE__) . 'assets/default-image.jpg';
            }
            ?>
            <div class="post-item" data-post-id="<?php echo esc_attr($post->ID); ?>">
                <div class="post-image">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($post->post_title); ?>">
                </div>
                <div class="post-info">
                    <h4 class="post-title"><?php echo esc_html($post->post_title); ?></h4>
                    <div class="post-meta">
                        <span class="post-date"><?php echo get_the_date('M j, Y', $post->ID); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    private function render_single_post($post) {
        $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
        $categories = get_the_category($post->ID);
        $author = get_the_author_meta('display_name', $post->post_author);
        ?>
        <article class="single-post-display">
            <?php if ($featured_image): ?>
                <div class="post-featured-image">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($post->post_title); ?>">
                </div>
            <?php endif; ?>
            
            <header class="post-header">
                <h1 class="post-title"><?php echo esc_html($post->post_title); ?></h1>
                <div class="post-meta">
                    <span class="post-author">By <?php echo esc_html($author); ?></span>
                    <span class="post-date"><?php echo get_the_date('F j, Y', $post->ID); ?></span>
                    <?php if (!empty($categories)): ?>
                        <span class="post-categories">
                            <?php foreach ($categories as $category): ?>
                                <span class="category-tag"><?php echo esc_html($category->name); ?></span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </header>
            
            <div class="post-content">
                <?php echo apply_filters('the_content', $post->post_content); ?>
            </div>
        </article>
        <?php
    }
    
    // AJAX handler for loading posts by category
    public function load_posts_by_category() {
        check_ajax_referer('article_navigator_nonce', 'nonce');
        
        $category_id = sanitize_text_field($_POST['category_id']);
        $posts_per_page = intval($_POST['posts_per_page']);
        
        $post_args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if ($category_id !== 'all') {
            $post_args['cat'] = $category_id;
        }
        
        $posts = get_posts($post_args);
        
        ob_start();
        $this->render_posts_list($posts);
        $posts_html = ob_get_clean();
        
        $first_post_html = '';
        if (!empty($posts)) {
            ob_start();
            $this->render_single_post($posts[0]);
            $first_post_html = ob_get_clean();
        }
        
        wp_send_json_success(array(
            'posts_html' => $posts_html,
            'first_post_html' => $first_post_html,
            'first_post_id' => !empty($posts) ? $posts[0]->ID : null
        ));
    }
    
    // AJAX handler for loading single post
    public function load_single_post() {
        check_ajax_referer('article_navigator_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
        }
        
        ob_start();
        $this->render_single_post($post);
        $post_html = ob_get_clean();
        
        wp_send_json_success(array(
            'post_html' => $post_html
        ));
    }
}

// Initialize the plugin
new WP_Article_Navigator();

// Create assets directory and files on activation
register_activation_hook(__FILE__, 'article_navigator_create_assets');

function article_navigator_create_assets() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = plugin_dir_path(__FILE__);
    $assets_dir = $plugin_dir . 'assets/';
    
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
}
?>
