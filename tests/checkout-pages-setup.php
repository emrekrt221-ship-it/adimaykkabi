<?php
/** Disposable CI-only classic Woo cart/checkout pages. Never run on production. */
defined('ABSPATH') || exit;
if (!defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('Only WP-CLI fixture process may create these pages.');
}
function adim_ci_checkout_page(string $title, string $slug, string $shortcode): int
{
    $existing = get_page_by_path($slug);
    if ($existing instanceof WP_Post) {
        return (int) $existing->ID;
    }
    $id = wp_insert_post([
        'post_type' => 'page',
        'post_title' => $title,
        'post_name' => $slug,
        'post_status' => 'publish',
        'post_content' => $shortcode,
    ], true);
    if (is_wp_error($id) || !$id) {
        throw new RuntimeException('Unable to create CI WooCommerce page: ' . $title);
    }
    return (int) $id;
}
$cartId = adim_ci_checkout_page('ADIM CI Cart', 'adim-ci-cart', '[woocommerce_cart]');
$checkoutId = adim_ci_checkout_page('ADIM CI Checkout', 'adim-ci-checkout', '[woocommerce_checkout]');
update_option('woocommerce_cart_page_id', $cartId);
update_option('woocommerce_checkout_page_id', $checkoutId);
echo sprintf('CI cart page #%d, checkout page #%d. No payment configured.' . PHP_EOL,
    $cartId, $checkoutId);
