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

// Specific Store API detail endpoints must respect the same review gate.
$approvedDetail = rest_do_request(new WP_REST_Request('GET', '/wc/store/v1/products/' . $approved->get_id()));
adim_integration_assert($approvedDetail->get_status() === 200, 'approved Store API detail visible');
$blockedDetail = rest_do_request(new WP_REST_Request('GET', '/wc/store/v1/products/' . $blocked->get_id()));
adim_integration_assert($blockedDetail->get_status() === 404, 'unreviewed Store API detail not found');

// Native add-to-cart checks should reject even if a request bypasses product listing.
$cart = new WC_Cart();
adim_integration_assert((bool) $cart->add_to_cart($approved->get_id(), 1), 'approved sample adds to test cart');
adim_integration_assert(!$cart->add_to_cart($blocked->get_id(), 1), 'unreviewed sample denied by add-to-cart');

// Check a direct product page query hook on its corresponding WP_Query.
$query = new WP_Query(['post_type' => 'product', 'posts_per_page' => 25]);
$visibleIds = array_map(static fn($p) => (int) $p->ID, $query->posts);
adim_integration_assert(in_array($approved->get_id(), $visibleIds, true), 'public WP query contains approved');
adim_integration_assert(!in_array($blocked->get_id(), $visibleIds, true), 'public WP query excludes unreviewed');


// Regression: even when a third-party filter uses OR, unreviewed products
// must not leak through the public WordPress product query.
$orQuery = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 50,
    'meta_query' => [
        'relation' => 'OR',
        ['key' => '_adim_sale_approved', 'compare' => 'NOT EXISTS'],
        ['key' => '_adim_sale_approved', 'value' => 'yes'],
    ],
]);
$orIds = array_map(static fn($p) => (int) $p->ID, $orQuery->posts);
adim_integration_assert(in_array($approved->get_id(), $orIds, true), 'reviewed product remains visible with OR filters');
adim_integration_assert(!in_array($blocked->get_id(), $orIds, true), 'unreviewed product excluded even with OR filters');


// Real variable-product stock isolation: one sold size must not change another.
$sizeAttribute = new WC_Product_Attribute();
$sizeAttribute->set_name('Beden');
$sizeAttribute->set_options(['42', '43']);
$sizeAttribute->set_variation(true);
$sizeAttribute->set_visible(true);

$variable = new WC_Product_Variable();
$variable->set_name('ADIM TEST authorized size variants');
$variable->set_status('publish');
$variable->set_attributes([$sizeAttribute]);
$variable->save();
update_post_meta($variable->get_id(), '_adim_sale_approved', 'yes');

$sizeVariations = [];
foreach (['42' => 1, '43' => 3] as $size => $quantity) {
    $child = new WC_Product_Variation();
    $child->set_parent_id($variable->get_id());
    $child->set_attributes(['beden' => $size]);
    $child->set_status('publish');
    $child->set_regular_price('1299.00');
    $child->set_manage_stock(true);
    $child->set_stock_quantity($quantity);
    $child->save();
    $sizeVariations[$size] = $child->get_id();
}
WC_Product_Variable::sync($variable->get_id());
adim_integration_assert(
    (int) wc_get_product($sizeVariations['42'])->get_stock_quantity() === 1,
    'size 42 starts with 1 unit'
);
adim_integration_assert(
    (int) wc_get_product($sizeVariations['43'])->get_stock_quantity() === 3,
    'size 43 starts with 3 units'
);
adim_integration_assert(
    (int) wc_update_product_stock($sizeVariations['42'], 1, 'decrease') === 0,
    'decreasing size 42 updates just its stock'
);
adim_integration_assert(
    (int) wc_get_product($sizeVariations['43'])->get_stock_quantity() === 3,
    'size 43 unchanged after size 42 adjustment'
);
adim_integration_assert(
    !wc_get_product($sizeVariations['42'])->is_in_stock(),
    'size 42 unavailable when zero stock'
);
adim_integration_assert(
    (int) wc_update_product_stock($sizeVariations['42'], 1, 'increase') === 1,
    'restoring size 42 is recorded correctly'
);

echo 'ADIM real WooCommerce integration smoke checks passed.' . PHP_EOL;
