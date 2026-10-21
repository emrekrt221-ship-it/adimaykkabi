<?php
/** Generic WordPress content page, including the WooCommerce cart/checkout pages. */
get_header();
?>
<main class="adim-container" id="main">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h1 class="adim-page-title"><?php the_title(); ?></h1>
            <?php the_content(); ?>
        </article>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
