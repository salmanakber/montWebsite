<?php get_header(); ?> <!-- Includes header.php -->

    <main>
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
    
                the_content();
            endwhile;
        else :
            echo '<p>No posts found.</p>';
        endif;
        ?>
    </main>
        <script>
        lucide.createIcons(); // This initializes the icons
    </script>
 
<?php get_footer(); ?>
