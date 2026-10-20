<?php
/** Real WordPress + WooCommerce integration smoke checks for CI.
 * Run: wp eval-file tests/integration-wordpress.php --allow-root
 */
defined('ABSPATH') || exit;
if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
    throw new RuntimeException('WooCommerce is not active.');
}

function adim_integration_assert(bool $passed, string $reason): void
{
    if (!$passed) {
        throw new RuntimeException('FAIL: ' . $reason);
    }
    echo 'PASS: ' . $reason . PHP_EOL;
}

function adim_integration_product(string $name, bool $reviewed): WC_Product_Simple
{
    $product = new WC_Product_Simple();
    $product->set_name($name);
    $product->set_status('publish');
    $product->set_regular_price('1499.00');
    $product->set_manage_stock(true);
    $product->set_stock_quantity(3);
    $product->set_catalog_visibility('visible');
    $product->save();
    if ($reviewed) {
        update_post_meta($product->get_id(), '_adim_sale_approved', 'yes');
    }
    return wc_get_product($product->get_id());
}

$approved = adim_integration_product('ADIM TEST approved sample', true);
$blocked  = adim_integration_product('ADIM TEST blocked sample', false);
adim_integration_assert(adim_ops_is_reviewed($approved), 'approved product review read');
adim_integration_assert(!adim_ops_is_reviewed($blocked), 'unreviewed product denied');
adim_integration_assert(!adim_ops_sales_enabled(), 'sales disabled by default');
adim_integration_assert(!$approved->is_purchasable(), 'approved product not purchasable before launch');
adim_integration_assert(!$blocked->is_purchasable(), 'blocked product not purchasable before launch');
adim_integration_assert($approved->is_visible(), 'approved Woo product visible');
adim_integration_assert(!$blocked->is_visible(), 'blocked Woo product invisible');

$variation = new WC_Product_Variation();
$variation->set_parent_id($approved->get_id());
$variation->set_regular_price('1499.00');
$variation->set_manage_stock(true);
$variation->set_stock_quantity(2);
$variation->set_attributes(['beden' => '42']);
$variation->save();
$variation = wc_get_product($variation->get_id());
adim_integration_assert(adim_ops_is_reviewed($variation), 'variation inherits parent authorization');
adim_integration_assert(!$variation->is_purchasable(), 'variation not purchasable before launch');

// Test-only toggle: no production configuration is written. This process exits after the test.
define('ADIM_ENABLE_SALES', true);
adim_integration_assert($approved->is_purchasable(), 'approved product purchasable with test switch');
adim_integration_assert(!$blocked->is_purchasable(), 'blocked product still cannot be purchased');
adim_integration_assert($variation->is_purchasable(), 'approved variation with test switch');

$storeApi = rest_do_request(new WP_REST_Request('GET', '/wc/store/v1/products'));
adim_integration_assert($storeApi->get_status() === 200, 'Store API product collection responds');
$data = $storeApi->get_data();
$ids = [];
foreach ((array) $data as $row) {
    if (is_array($row) && isset($row['id'])) {
        $ids[] = (int) $row['id'];
    }
}
adim_integration_assert(in_array($approved->get_id(), $ids, true), 'approved product returned by Store API');
adim_integration_assert(!in_array($blocked->get_id(), $ids, true), 'unreviewed product absent from Store API');

// Check a direct product page query hook on its corresponding WP_Query.
$query = new WP_Query(['post_type' => 'product', 'posts_per_page' => 25]);
$visibleIds = array_map(static fn($p) => (int) $p->ID, $query->posts);
adim_integration_assert(in_array($approved->get_id(), $visibleIds, true), 'public WP query contains approved');
adim_integration_assert(!in_array($blocked->get_id(), $visibleIds, true), 'public WP query excludes unreviewed');

echo 'ADIM real WooCommerce integration smoke checks passed.' . PHP_EOL;
