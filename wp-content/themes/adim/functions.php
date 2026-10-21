<?php
/**
 * ADIM Storefront theme initialization.
 *
 * @package AdimStorefront
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/inc/catalog.php';

add_action('after_setup_theme', static function (): void {
    load_theme_textdomain('adim', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'gallery', 'caption']);
    register_nav_menus(['primary' => __('Ana menü', 'adim')]);
});

add_action('wp_enqueue_scripts', static function (): void {
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('adim-style', get_stylesheet_uri(), [], $version);
    wp_enqueue_style(
        'adim-storefront',
        get_template_directory_uri() . '/assets/css/storefront.css',
        ['adim-style'],
        $version
    );
    wp_enqueue_script(
        'adim-navigation',
        get_template_directory_uri() . '/assets/js/navigation.js',
        [],
        $version,
        true
    );
});

/** Return the configured WooCommerce shop URL, when available. */
function adim_shop_url(): string
{
    if (function_exists('wc_get_page_permalink')) {
        $url = wc_get_page_permalink('shop');
        if ($url) {
            return (string) $url;
        }
    }
    return home_url('/shop/');
}

/** Return the current cart item count safely. */
function adim_cart_count(): int
{
    if (!function_exists('WC') || !WC()->cart) {
        return 0;
    }
    return (int) WC()->cart->get_cart_contents_count();
}
