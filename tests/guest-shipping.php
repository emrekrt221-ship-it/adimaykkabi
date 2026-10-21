<?php
/**
 * In-process guest address/cart shipping test. No payable order is submitted.
 * CI explicitly enables sales only within this WP-CLI process to test Woo core.
 */
defined('ABSPATH') || exit;
if (!defined('WP_CLI') || !WP_CLI || getenv('ADIM_CI_FIXTURE') !== 'yes') {
    throw new RuntimeException('Disposable guest shipping CI only.');
}
function adim_guest_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException('ADIM GUEST FAIL: ' . $message);
    }
    echo 'PASS ADIM guest: ' . $message . PHP_EOL;
}

adim_guest_assert(!adim_ops_sales_enabled(), 'production-style switch remains off before isolated test');
$before_orders = wc_get_orders(['limit' => -1, 'return' => 'ids']);
$sku = 'ADIM-CI-LEGAL-DEMO-SHOE';
$product_id = (int) wc_get_product_id_by_sku($sku);
$product = wc_get_product($product_id);
adim_guest_assert($product instanceof WC_Product_Simple && adim_ops_is_reviewed($product),
    'only the synthetic reviewed item is used');

define('ADIM_ENABLE_SALES', true);
WC()->session = new WC_Session_Handler();
WC()->session->init();
WC()->customer = new WC_Customer(0, true);
WC()->customer->set_billing_country('TR');
WC()->customer->set_billing_city('Reyhanlı');
WC()->customer->set_billing_postcode('31500');
WC()->customer->set_shipping_country('TR');
WC()->customer->set_shipping_city('Reyhanlı');
WC()->customer->set_shipping_postcode('31500');
WC()->customer->set_shipping_address('Test Sokak No: 1');
WC()->cart = new WC_Cart();
$key = WC()->cart->add_to_cart($product_id, 2);
adim_guest_assert((bool) $key, 'guest cart accepts two legally neutral test pairs');
WC()->cart->calculate_totals();
adim_guest_assert(WC()->customer->get_id() === 0, 'customer remains a guest');
adim_guest_assert(WC()->cart->needs_shipping(), 'physical shoes require shipping');
adim_guest_assert((float) WC()->cart->get_subtotal() === 3000.0
    || (float) WC()->cart->get_cart_contents_total() === 3000.0,
    'two pairs subtotal is ₺3,000 before shipping');

$packages = WC()->cart->get_shipping_packages();
adim_guest_assert(count($packages) === 1, 'one parcel is calculated for the guest cart');
$zone = wc_get_shipping_zone($packages[0]);
adim_guest_assert(str_contains($zone->get_zone_name(), 'ADIM CI Türkiye'),
    'destination matches configured domestic shipping zone');
WC()->shipping()->calculate_shipping($packages);
$shipped = WC()->shipping()->get_packages();
$matched = [];
foreach ($shipped[0]['rates'] ?? [] as $rate) {
    if ($rate->get_method_id() === 'flat_rate') {
        $matched[] = $rate;
    }
}
adim_guest_assert(count($matched) === 1, 'one configured flat-rate method available for Turkey');
adim_guest_assert(abs((float) $matched[0]->get_cost() - 59.0) < 0.001,
    'shipping cost is calculated from WooCommerce settings as ₺59, not hardcoded in theme');
$chosen_rate = $matched[0]->get_id();
WC()->session->set('chosen_shipping_methods', [$chosen_rate]);
WC()->cart->calculate_totals();
adim_guest_assert(abs((float) WC()->cart->get_shipping_total() - 59.0) < 0.001,
    'cart total includes selected configured shipping rate');
adim_guest_assert(abs((float) WC()->cart->get_total('edit') - 3059.0) < 0.001,
    'guest subtotal + configured shipping is ₺3,059 with CI taxes disabled');

// Verify no unsupported international delivery promise is displayed.
WC()->customer->set_shipping_country('DE');
WC()->customer->set_billing_country('DE');
WC()->shipping()->reset_shipping();
$outside = WC()->cart->get_shipping_packages();
$outside_zone = wc_get_shipping_zone($outside[0]);
adim_guest_assert($outside_zone->get_zone_name() !== $zone->get_zone_name(),
    'unconfigured international country cannot match domestic zone');
WC()->shipping()->calculate_shipping($outside);
$unavailable = WC()->shipping()->get_packages();
adim_guest_assert(empty($unavailable[0]['rates']),
    'no invented international shipping option when none is configured');

// Restore guest destination for validation checks.
WC()->customer->set_shipping_country('TR');
WC()->customer->set_billing_country('TR');
$fields = WC()->checkout()->get_checkout_fields('billing');
adim_guest_assert(!empty($fields['billing_first_name']['required'])
    && !empty($fields['billing_last_name']['required'])
    && !empty($fields['billing_address_1']['required']),
    'native Woo guest billing name and street address remain required');
adim_guest_assert(!empty($fields['billing_country']['required']),
    'native Woo country field remains required');
$after_orders = wc_get_orders(['limit' => -1, 'return' => 'ids']);
adim_guest_assert($before_orders === $after_orders, 'guest pricing and address tests never create an order');
echo 'ADIM synthetic guest shipping test PASSED; no gateway, merchant or payment used.' . PHP_EOL;
