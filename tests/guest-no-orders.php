<?php
/** Assert that the guest browser demonstration never created a checkout order. */
defined('ABSPATH') || exit;
if (!defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('Must run in isolated WP-CLI.');
}
$orders = wc_get_orders(['limit' => 1, 'return' => 'ids']);
if ($orders) {
    throw new RuntimeException('FAIL: Browser preview unexpectedly created WooCommerce order(s).');
}
$gateways = WC()->payment_gateways()->get_available_payment_gateways();
if ($gateways) {
    throw new RuntimeException('FAIL: Browser demo unexpectedly exposes enabled payment gateway(s).');
}
echo 'PASS ADIM CI: zero orders and zero available payment gateways after guest checkout preview.' . PHP_EOL;
