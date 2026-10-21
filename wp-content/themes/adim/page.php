<?php
/** Generic WordPress content page, including the WooCommerce cart/checkout pages. */
get_header();
?>
<main class="adim-container" id="main">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h1 class="adim-page-title"><?php the_title(); ?></h1>
            <?php
            if (function_exists('is_cart') && is_cart()) {
                adim_checkout_render_steps('cart');
                adim_checkout_render_status();
            } elseif (function_exists('is_checkout') && is_checkout()
                && (!function_exists('is_order_received_page') || !is_order_received_page())) {
                adim_checkout_render_steps('checkout');
                adim_checkout_render_status();
                adim_checkout_render_delivery_help();
            }
            the_content();
            ?>
        </article>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
