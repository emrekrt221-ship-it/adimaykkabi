<?php
/**
 * Cart → delivery/payment presentation.
 * No merchant fee, delivery-time or pickup promise is hardcoded.
 */
defined('ABSPATH') || exit;

/** Render an accessible customer progress guide (not an alternative cart). */
function adim_checkout_render_steps(string $current = 'cart'): void
{
    $shop_url = function_exists('adim_shop_url') ? adim_shop_url() : home_url('/');
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    $steps = [
        ['label' => __('Ürünler', 'adim'), 'url' => $shop_url, 'key' => 'shop'],
        ['label' => __('Sepet', 'adim'), 'url' => $cart_url, 'key' => 'cart'],
        ['label' => __('Teslimat ve Ödeme', 'adim'), 'url' => $checkout_url, 'key' => 'checkout'],
    ];

    echo '<nav class="adim-journey" aria-label="' . esc_attr__('Alışveriş adımları', 'adim') . '">';
    echo '<ol class="adim-journey-list">';
    foreach ($steps as $index => $step) {
        $active = $current === $step['key'];
        $is_link = !$active && ($step['key'] !== 'checkout'
            || (function_exists('adim_ops_sales_enabled') && adim_ops_sales_enabled()));
        echo '<li class="' . esc_attr($active ? 'is-current' : '') . '">';
        if ($is_link) {
            echo '<a href="' . esc_url($step['url']) . '">';
        } else {
            echo '<span' . ($active ? ' aria-current="step"' : '') . '>';
        }
        echo '<span class="adim-journey-number" aria-hidden="true">'
            . esc_html((string) ($index + 1)) . '</span>';
        echo '<span>' . esc_html($step['label']) . '</span>';
        echo $is_link ? '</a>' : '</span>';
        echo '</li>';
    }
    echo '</ol></nav>';
}

/** Inform customers about the real checkout state without changing totals. */
function adim_checkout_render_status(): void
{
    if (!function_exists('adim_ops_sales_enabled') || !adim_ops_sales_enabled()) {
        echo '<div class="adim-checkout-alert" role="status"><strong>'
            . esc_html__('Online siparişler henüz açık değil.', 'adim') . '</strong> '
            . esc_html__('Ürünleri inceleyebilirsiniz ancak ödeme ve sipariş oluşturma şu anda kapalıdır.', 'adim')
            . '</div>';
    }
}

/**
 * Render the checkout explainer in the page template (also works with empty
 * carts and Woo blocks where classic Woo hook callbacks are not triggered).
 */
function adim_checkout_render_delivery_help(): void
{
    echo '<div class="adim-checkout-help">';
    echo '<h2>' . esc_html__('Teslimat ve ödeme bilgileri', 'adim') . '</h2>';
    echo '<p>' . esc_html__('Kargo bedeli ve varsa mağazadan teslim seçenekleri, adresinize göre WooCommerce ödeme ekranında hesaplanır. '
        . 'Yalnızca ödeme ekranında gösterilen yöntemleri kullanabilirsiniz.', 'adim') . '</p>';
    echo '</div>';
}

/** Make user expectations explicit without inventing carrier or rate metadata. */
add_action('woocommerce_review_order_before_payment', static function (): void {
    echo '<p class="adim-order-disclaimer">'
        . esc_html__('Siparişi onaylamadan önce adresinizi, seçtiğiniz numarayı, teslimat seçeneğini ve toplam tutarı kontrol edin.', 'adim')
        . '</p>';
}, 5);
