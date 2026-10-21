<?php
/**
 * Disposable WordPress/WooCommerce TEST fixture, never run on a real shop.
 * Creates one synthetic approved shoe and a paid domestic shipping method.
 * It does NOT enable the online sale switch or configure a payment gateway.
 */
defined('ABSPATH') || exit;
if (!defined('WP_CLI') || !WP_CLI || getenv('ADIM_CI_FIXTURE') !== 'yes') {
    throw new RuntimeException('ADIM guest checkout fixture requires explicit CI-only consent.');
}
if (adim_ops_sales_enabled()) {
    throw new RuntimeException('Provision synthetic goods while public checkout is OFF.');
}
update_option('woocommerce_currency', 'TRY');
update_option('woocommerce_default_country', 'TR:31');
update_option('woocommerce_allowed_countries', 'specific');
update_option('woocommerce_specific_allowed_countries', ['TR']);
update_option('woocommerce_ship_to_countries', 'specific');
update_option('woocommerce_specific_ship_to_countries', ['TR']);
update_option('woocommerce_calc_taxes', 'no');
update_option('woocommerce_enable_guest_checkout', 'yes');
update_option('woocommerce_enable_shipping_calc', 'yes');
update_option('woocommerce_ship_to_destination', 'billing_only');

$sku = 'ADIM-CI-LEGAL-DEMO-SHOE';
$id = (int) wc_get_product_id_by_sku($sku);
$product = $id ? wc_get_product($id) : new WC_Product_Simple();
if (!($product instanceof WC_Product_Simple)) {
    throw new RuntimeException('Synthetic test SKU unexpectedly points to a different product type.');
}
$product->set_name('ADIM CI Synthetic Guest Shoe');
$product->set_sku($sku);
$product->set_status('publish');
$product->set_catalog_visibility('visible');
$product->set_regular_price('1500');
$product->set_manage_stock(true);
$product->set_stock_quantity(5);
$product->set_backorders('no');
$product->set_virtual(false);
$product->save();
update_post_meta($product->get_id(), '_adim_sale_approved', 'yes');

$zone = new WC_Shipping_Zone();
$zone->set_zone_name('ADIM CI Türkiye Shipping');
$zone->set_zone_order(0);
$zone->save();
if (!$zone->get_id()) {
    throw new RuntimeException('Cannot create isolated WooCommerce test shipping zone.');
}
$zone->add_location('TR', 'country');
$instance_id = (int) $zone->add_shipping_method('flat_rate');
if (!$instance_id) {
    throw new RuntimeException('Cannot attach WooCommerce flat-rate shipping to CI zone.');
}
update_option('woocommerce_flat_rate_' . $instance_id . '_settings', [
    'title' => 'ADIM CI Fixed Delivery',
    'tax_status' => 'none',
    'cost' => '59.00',
]);
update_option('woocommerce_ship_to_countries', 'specific');
$method = WC_Shipping_Zones::get_shipping_method($instance_id);
if (!$method || $method->id !== 'flat_rate') {
    throw new RuntimeException('CI test flat-rate shipping method could not be retrieved.');
}
file_put_contents('/tmp/adim-ci-guest-product-id', (string) $product->get_id());

echo 'PASS fixture: synthetic lawful shoe #' . $product->get_id()
    . ', test shipping zone #' . $zone->get_id() . ', flat rate #' . $instance_id
    . '. Sales remain disabled.' . PHP_EOL;
