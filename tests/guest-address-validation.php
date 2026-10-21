<?php
/**
 * ADIM — Native WooCommerce billing validation without process_checkout.
 *
 * This file uses WooCommerce's own validate_posted_data implementation with
 * Reflection only in a disposable WP-CLI CI environment. It never submits
 * checkout, saves personal data, creates an order, or charges a customer.
 */
defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI || getenv('ADIM_CI_FIXTURE') !== 'yes') {
    throw new RuntimeException('Guest address validation is for explicit disposable CI only.');
}

function adim_guest_field_check(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL ADIM field validation: ' . $description);
    }
    echo 'PASS ADIM field validation: ' . $description . PHP_EOL;
}

adim_guest_field_check(!adim_ops_sales_enabled(),
    'real checkout remains disabled at start of isolated process');
$orders_before = wc_get_orders(['limit' => -1, 'return' => 'ids']);

// The temporary positive cart fixture is only usable inside this WP-CLI process.
define('ADIM_ENABLE_SALES', true);
WC()->session = new WC_Session_Handler();
WC()->session->init();
WC()->customer = new WC_Customer(0, true);
WC()->customer->set_billing_country('TR');
WC()->customer->set_shipping_country('TR');
WC()->customer->set_billing_city('Reyhanlı');
WC()->customer->set_shipping_city('Reyhanlı');
WC()->cart = new WC_Cart();
$product_id = (int) wc_get_product_id_by_sku('ADIM-CI-LEGAL-DEMO-SHOE');
adim_guest_field_check($product_id > 0
    && (bool) WC()->cart->add_to_cart($product_id, 1), 'one synthetic item in guest cart');
WC()->cart->calculate_totals();

$checkout = WC()->checkout();
$fields = $checkout->get_checkout_fields('billing');
adim_guest_field_check(!empty($fields['billing_first_name']['required'])
    && !empty($fields['billing_last_name']['required'])
    && !empty($fields['billing_address_1']['required'])
    && !empty($fields['billing_email']['required']),
    'WooCommerce billing name, street and email remain required');

$validate = new ReflectionMethod(WC_Checkout::class, 'validate_posted_data');
adim_guest_field_check(!$validate->isPublic(),
    'test invokes protected core validator, not any actual checkout submission');

$baseline = [
    'billing_first_name' => 'Test',
    'billing_last_name' => 'Müşteri',
    'billing_country' => 'TR',
    'billing_address_1' => 'Deneme Sokak 1',
    'billing_address_2' => '',
    'billing_city' => 'Reyhanlı',
    'billing_postcode' => '31500',
    'billing_phone' => '05321234567',
    'billing_email' => 'test@example.test',
    'ship_to_different_address' => false,
];
// Populate all known checkout inputs, otherwise Woo core skips missing keys.
foreach ($checkout->get_checkout_fields() as $section) {
    foreach ($section as $key => $definition) {
        if (!array_key_exists($key, $baseline)) {
            $baseline[$key] = '';
        }
    }
}

/** @return WP_Error */
function adim_validate_native_fields(WC_Checkout $checkout, ReflectionMethod $validator, array $data): WP_Error
{
    $errors = new WP_Error();
    $validator->invokeArgs($checkout, [&$data, &$errors]);
    return $errors;
}

$ok = adim_validate_native_fields($checkout, $validate, $baseline);
adim_guest_field_check(!in_array('billing_email_validation', $ok->get_error_codes(), true)
    && !in_array('billing_first_name_required', $ok->get_error_codes(), true)
    && !in_array('billing_address_1_required', $ok->get_error_codes(), true),
    'plausible test email, name and street do not create those field errors');

$missing = $baseline;
$missing['billing_first_name'] = '';
$missing['billing_last_name'] = '';
$missing['billing_address_1'] = '';
$missing['billing_email'] = '';
$missing_errors = adim_validate_native_fields($checkout, $validate, $missing);
adim_guest_field_check(in_array('billing_first_name_required', $missing_errors->get_error_codes(), true),
    'empty customer first name rejected by WooCommerce core');
adim_guest_field_check(in_array('billing_last_name_required', $missing_errors->get_error_codes(), true),
    'empty customer surname rejected by WooCommerce core');
adim_guest_field_check(in_array('billing_email_required', $missing_errors->get_error_codes(), true),
    'missing email rejected by WooCommerce core');
adim_guest_field_check(in_array('billing_address_1_required', $missing_errors->get_error_codes(), true),
    'empty delivery/billing street rejected by WooCommerce core');

$email = $baseline;
$email['billing_email'] = 'not-an-email';
$email_errors = adim_validate_native_fields($checkout, $validate, $email);
adim_guest_field_check(in_array('billing_email_validation', $email_errors->get_error_codes(), true),
    'invalid guest email rejected by WooCommerce core');

$phone = $baseline;
$phone['billing_phone'] = 'phone-abc';
$phone_errors = adim_validate_native_fields($checkout, $validate, $phone);
adim_guest_field_check(in_array('billing_phone_validation', $phone_errors->get_error_codes(), true),
    'invalid guest telephone rejected by WooCommerce core');

$country = $baseline;
$country['billing_country'] = 'ZZ';
$country_errors = adim_validate_native_fields($checkout, $validate, $country);
adim_guest_field_check(in_array('billing_country_validation', $country_errors->get_error_codes(), true),
    'unknown destination country rejected by WooCommerce core');

adim_guest_field_check(WC()->customer->get_id() === 0, 'test user stays guest');
$orders_after = wc_get_orders(['limit' => -1, 'return' => 'ids']);
adim_guest_field_check($orders_before === $orders_after,
    'direct field validation creates no orders and charges no payment');
echo 'ADIM WooCommerce native guest-address validation completed.' . PHP_EOL;
