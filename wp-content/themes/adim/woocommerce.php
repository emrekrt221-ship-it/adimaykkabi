<?php
/** WooCommerce archive/single-product compatibility template. */
get_header();
?>
<main class="adim-container" id="main">
    <?php if (function_exists('woocommerce_content')) : ?>
        <?php woocommerce_content(); ?>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
