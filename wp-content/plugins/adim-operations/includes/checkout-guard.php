<?php
/**
 * Checkout defense in depth for classic and WooCommerce Store API checkout.
 * Leave WooCommerce authoritative for shipping, taxes, payment and stock.
 */
defined('ABSPATH') || exit;

/**
 * @return WP_Error|null The checkout-specific rejection, or null if it should
 *         proceed to the remaining WooCommerce checkout validations.
 */
function adim_ops_checkout_rejection()
{
    if (!adim_ops_sales_enabled()) {
        return new WP_Error(
            'adim_checkout_disabled',
            __('İnternet üzerinden sipariş şu anda kapalıdır.', 'adim-operations'),
            ['status' => 403]
        );
    }

    // WooCommerce manages the session and cart lifecycle; an absent cart
    // must be rejected by its standard empty-cart validations, not invented here.
    if (!function_exists('WC') || !WC()->cart) {
        return null;
    }
    foreach (WC()->cart->get_cart() as $line) {
        $product = $line['data'] ?? null;
        if (!($product instanceof WC_Product)
            || !adim_ops_is_reviewed($product)
            || !$product->is_purchasable()) {
            return new WP_Error(
                'adim_checkout_product_blocked',
                __('Sepetteki bir ürün artık satışa uygun değil. Lütfen sepetinizi kontrol edin.', 'adim-operations'),
                ['status' => 409]
            );
        }
    }
    return null;
}

/**
 * Blocks Checkout POST at REST pre-dispatch, before the Store API can make an
 * order. GET stays available so the checkout page can render a notice.
 */
add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
    if ($result !== null || !($request instanceof WP_REST_Request)
        || strtoupper($request->get_method()) !== 'POST'
        || !preg_match('#^/wc/store/v[0-9]+/checkout/?$#', $request->get_route())) {
        return $result;
    }
    $denied = adim_ops_checkout_rejection();
    return is_wp_error($denied) ? $denied : $result;
}, 9, 3);

/** Classic Woo checkout validation; no stock/order/payment side effects. */
add_action('woocommerce_checkout_process', static function (): void {
    $denied = adim_ops_checkout_rejection();
    if (is_wp_error($denied) && function_exists('wc_add_notice')) {
        wc_add_notice($denied->get_error_message(), 'error');
    }
}, 1);
