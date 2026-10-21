<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="adim-skip-link" href="#main"><?php esc_html_e('İçeriğe geç', 'adim'); ?></a>
<header class="adim-header" role="banner">
    <div class="adim-header-inner">
        <a class="adim-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('ADIM AYAKKABI ana sayfa', 'adim'); ?>">ADIM <span>AYAKKABI</span></a>
        <button class="adim-menu-toggle" type="button" aria-controls="adim-primary-navigation" aria-expanded="false">
            <span class="adim-menu-icon" aria-hidden="true">☰</span>
            <span><?php esc_html_e('Menü', 'adim'); ?></span>
        </button>
        <nav id="adim-primary-navigation" class="adim-nav" aria-label="<?php esc_attr_e('Ana menü', 'adim'); ?>">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'adim-nav-list',
                    'depth' => 2,
                ]);
            } else {
                echo '<a href="' . esc_url(adim_shop_url()) . '">' . esc_html__('Tüm Ayakkabılar', 'adim') . '</a>';
            }
            ?>
        </nav>
        <div class="adim-header-actions">
            <form class="adim-search" role="search" action="<?php echo esc_url(home_url('/')); ?>" method="get">
                <label class="screen-reader-text" for="adim-search-input"><?php esc_html_e('Ürün ara', 'adim'); ?></label>
                <input id="adim-search-input" type="search" name="s" placeholder="<?php esc_attr_e('Ürün ara', 'adim'); ?>" required>
                <input type="hidden" name="post_type" value="product">
                <button type="submit" aria-label="<?php esc_attr_e('Ara', 'adim'); ?>">⌕</button>
            </form>
            <?php if (function_exists('wc_get_cart_url')) : ?>
                <a class="adim-cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                    <?php esc_html_e('Sepet', 'adim'); ?>
                    <span class="adim-cart-count" aria-label="<?php echo esc_attr(sprintf(__('Sepette %d ürün', 'adim'), adim_cart_count())); ?>"><?php echo esc_html(adim_cart_count()); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
