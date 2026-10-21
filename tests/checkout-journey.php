<?php
/** Test no real money, no orders. Uses disposable WordPress/WooCommerce CI. */
defined('ABSPATH') || exit;

function adim_checkout_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('ADIM checkout FAIL: ' . $message);
    }
    echo 'PASS ADIM checkout: ' . $message . PHP_EOL;
}
adim_checkout_check(!adim_ops_sales_enabled(), 'live sales switch defaults disabled');

$before = wc_get_orders(['limit' => -1, 'return' => 'ids']);
$denial = adim_ops_checkout_rejection();
adim_checkout_check(is_wp_error($denial)
    && $denial->get_error_code() === 'adim_checkout_disabled',
    'closed checkout is rejected before payment');
$post = new WP_REST_Request('POST', '/wc/store/v1/checkout');
$blockedResponse = rest_do_request($post);
$blockedData = (array) $blockedResponse->get_data();
adim_checkout_check($blockedResponse->get_status() === 403
    && ($blockedData['code'] ?? '') === 'adim_checkout_disabled',
    'Store API checkout POST is rejected with 403 when sales disabled');
$get = rest_do_request(new WP_REST_Request('GET', '/wc/store/v1/products'));
adim_checkout_check($get->get_status() === 200,
    'closing checkout does not break ordinary product browsing');

ob_start();
adim_checkout_render_steps('cart');
$cartSteps = ob_get_clean();
adim_checkout_check(str_contains($cartSteps, 'aria-current="step"')
    && str_contains($cartSteps, 'Sepet')
    && str_contains($cartSteps, 'Teslimat ve Ödeme'),
    'cart guide has step navigation and current-step semantics');
ob_start();
adim_checkout_render_steps('checkout');
$checkoutSteps = ob_get_clean();
adim_checkout_check(str_contains($checkoutSteps, 'aria-current="step"')
    && substr_count($checkoutSteps, '<li') === 3,
    'checkout guide renders three steps for responsive styling');
ob_start();
adim_checkout_render_status();
$closedNotice = ob_get_clean();
adim_checkout_check(str_contains($closedNotice, 'Online siparişler henüz açık değil')
    && str_contains($closedNotice, 'role="status"'),
    'customer sees honest disabled-sales notice');

// Define only in this short-lived WP-CLI testing process, NOT wp-config or disk.
define('ADIM_ENABLE_SALES', true);
adim_checkout_check(!is_wp_error(adim_ops_checkout_rejection()),
    'staged test checkout proceeds to WooCommerce native validation when cart absent');

$product = new WC_Product_Simple();
$product->set_name('ADIM lawful synthetic cart demo');
$product->set_status('publish');
$product->set_regular_price('1250');
$product->set_manage_stock(true);
$product->set_stock_quantity(3);
$product->save();
update_post_meta($product->get_id(), '_adim_sale_approved', 'yes');

$cart = new WC_Cart();
WC()->cart = $cart;
$cartKey = $cart->add_to_cart($product->get_id(), 1);
adim_checkout_check((bool) $cartKey, 'authorized synthetic item adds to native WooCommerce cart');
adim_checkout_check(!is_wp_error(adim_ops_checkout_rejection()),
    'authorized cart is not rejected by extra checkout authorization');

update_post_meta($product->get_id(), '_adim_sale_approved', 'no');
$revoked = adim_ops_checkout_rejection();
adim_checkout_check(is_wp_error($revoked)
    && $revoked->get_error_code() === 'adim_checkout_product_blocked',
    'revoking a product prevents checkout with an already populated cart');
$revokedResponse = rest_do_request(new WP_REST_Request('POST', '/wc/store/v1/checkout'));
$revokedData = (array) $revokedResponse->get_data();
adim_checkout_check($revokedResponse->get_status() === 409
    && ($revokedData['code'] ?? '') === 'adim_checkout_product_blocked',
    'Store API checkout POST rejects newly unapproved cart with 409');

$after = wc_get_orders(['limit' => -1, 'return' => 'ids']);
adim_checkout_check($before === $after, 'blocked requests never create a WooCommerce order');

echo 'ADIM checkout policy and progress-navigation tests passed.' . PHP_EOL;
