<?php
/** ADIM storefront theme bootstrap. */
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'gallery', 'caption']);
    register_nav_menus(['primary' => __('Primary navigation', 'adim')]);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('adim-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
});
