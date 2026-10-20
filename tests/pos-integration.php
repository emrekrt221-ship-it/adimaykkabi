<?php
/** Real WP + WooCommerce physical-sale tests. Run with wp eval-file. */
defined('ABSPATH') || exit;
if (!class_exists('WooCommerce') || !function_exists('adim_pos_record_sale')) {
    throw new RuntimeException('ADIM POS plugin or WooCommerce missing.');
}
function pos_test(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    echo 'PASS POS: ' . $message . PHP_EOL;
}
function pos_test_sku(string $label, int $stock, bool $approved): string
{
    $sku = 'ADIMCI-' . strtoupper(wp_generate_password(12, false, false));
    $p = new WC_Product_Simple();
    $p->set_name($label);
    $p->set_status('publish');
    $p->set_sku($sku);
    $p->set_regular_price('1499');
    $p->set_manage_stock(true);
    $p->set_stock_quantity($stock);
    $p->set_backorders('no');
    $p->save();
    if ($approved) {
        update_post_meta($p->get_id(), '_adim_sale_approved', 'yes');
    }
    return $sku;
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
pos_test(!empty($admins), 'admin user exists');
wp_set_current_user((int) $admins[0]->ID);
$operator = get_current_user_id();

pos_test(!adim_pos_enabled(), 'POS switch defaults off');
$sku = pos_test_sku('ADIM sample 42', 1, true);
$key = wp_generate_uuid4();
$off = adim_pos_record_sale($sku, 1, $key, $operator);
pos_test(is_wp_error($off) && $off->get_error_code() === 'adim_pos_disabled', 'sale blocked while switch off');

adim_pos_install();
pos_test(get_option('adim_pos_db_version') === '1', 'idempotency table migration applied');
define('ADIM_ENABLE_POS', true);
pos_test(adim_pos_enabled(), 'test POS switch enabled');

$invalid = adim_pos_record_sale($sku, 1, 'not-an-uuid', $operator);
pos_test(is_wp_error($invalid) && $invalid->get_error_code() === 'adim_pos_bad_key', 'reject invalid idempotency key');
$wrongActor = adim_pos_record_sale($sku, 1, wp_generate_uuid4(), $operator + 100);
pos_test(is_wp_error($wrongActor) && $wrongActor->get_error_code() === 'adim_pos_forbidden', 'reject mismatched operator');
$unreviewed = pos_test_sku('ADIM blocked model', 2, false);
$refuse = adim_pos_record_sale($unreviewed, 1, wp_generate_uuid4(), $operator);
pos_test(is_wp_error($refuse), 'unreviewed product cannot sell in store');

$first = adim_pos_record_sale($sku, 1, $key, $operator, 'cash');
if (is_wp_error($first)) {
    global $wpdb;
    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT order_id, status FROM {$wpdb->prefix}adim_pos_requests WHERE request_key=%s", $key
    ));
    echo 'DEBUG POS ledger ' . wp_json_encode($entry) . PHP_EOL;
    $suspect = $entry && $entry->order_id ? wc_get_order((int) $entry->order_id) : null;
    if ($suspect) {
        foreach ($suspect->get_items('line_item') as $item) {
            echo 'DEBUG POS item ' . wp_json_encode([
                'id' => $item->get_id(),
                'quantity' => $item->get_quantity(),
                'reduced_stock' => $item->get_meta('_reduced_stock', true),
                'database_reduced' => wc_get_order_item_meta($item->get_id(), '_reduced_stock', true),
            ]) . PHP_EOL;
        }
    }
    echo 'DEBUG POS remaining ' . (int) wc_get_product(wc_get_product_id_by_sku($sku))->get_stock_quantity() . PHP_EOL;
    throw new RuntimeException('POS first order: ' . $first->get_error_message());
}
pos_test($first instanceof WC_Order, 'approved POS sale creates WooCommerce order');
pos_test($first->get_status() === 'completed', 'sale produces completed order');
pos_test($first->get_created_via() === 'adim-pos', 'order channel marked as in-store');
pos_test($first->get_meta('_adim_pos_request_key') === $key, 'order carries idempotency key');
pos_test((int) wc_get_product_id_by_sku($sku) > 0, 'stock SKU resolves');
pos_test((int) wc_get_product(wc_get_product_id_by_sku($sku))->get_stock_quantity() === 0, 'sold item stock reduced to zero');
$again = adim_pos_record_sale($sku, 1, $key, $operator);
pos_test($again instanceof WC_Order && $again->get_id() === $first->get_id(), 'duplicate request reuses original order');
pos_test((int) wc_get_product(wc_get_product_id_by_sku($sku))->get_stock_quantity() === 0, 'duplicate does not double-decrement');
$second = adim_pos_record_sale($sku, 1, wp_generate_uuid4(), $operator);
pos_test(is_wp_error($second), 'new request cannot sell an item with no remaining stock');
pos_test((int) wc_get_product(wc_get_product_id_by_sku($sku))->get_stock_quantity() === 0, 'failed request does not decrement below zero');

$sku43 = pos_test_sku('ADIM sample 43', 3, true);
$third = adim_pos_record_sale($sku43, 1, wp_generate_uuid4(), $operator, 'external_terminal');
pos_test($third instanceof WC_Order && $third->get_status() === 'completed', 'external terminal sale is recorded, not charged online');
pos_test((int) wc_get_product(wc_get_product_id_by_sku($sku43))->get_stock_quantity() === 2, 'size 43 independently decremented');
pos_test((int) wc_get_product(wc_get_product_id_by_sku($sku))->get_stock_quantity() === 0, 'size 42 unaffected by size 43 sale');
pos_test($third->get_payment_method() === 'adim_pos_external_terminal', 'external card terminal channel recorded');


// Actual variable product: physical sale must select a child SKU, not a parent.
$attribute = new WC_Product_Attribute();
$attribute->set_name('Beden');
$attribute->set_options(['42', '43']);
$attribute->set_visible(true);
$attribute->set_variation(true);
$parent = new WC_Product_Variable();
$parent->set_name('ADIM test size-based footwear');
$parent->set_status('publish');
$parent->set_attributes([$attribute]);
$parent->save();
update_post_meta($parent->get_id(), '_adim_sale_approved', 'yes');

$childSku = [];
foreach (['42' => 2, '43' => 1] as $size => $initial) {
    $child = new WC_Product_Variation();
    $child->set_parent_id($parent->get_id());
    $child->set_status('publish');
    $child->set_attributes(['beden' => $size]);
    $child->set_sku('ADIM-POS-SIZE-' . $size . '-' . strtoupper(wp_generate_password(8, false, false)));
    $child->set_regular_price('1599');
    $child->set_manage_stock(true);
    $child->set_stock_quantity($initial);
    $child->set_backorders('no');
    $child->save();
    $childSku[$size] = $child->get_sku();
}
WC_Product_Variable::sync($parent->get_id());
$sizeSale = adim_pos_record_sale($childSku['42'], 1, wp_generate_uuid4(), $operator);
pos_test($sizeSale instanceof WC_Order && $sizeSale->get_status() === 'completed', 'POS records real 42-size WooCommerce variation');
pos_test((int) wc_get_product(wc_get_product_id_by_sku($childSku['42']))->get_stock_quantity() === 1, 'size 42 stock 2 to 1');
pos_test((int) wc_get_product(wc_get_product_id_by_sku($childSku['43']))->get_stock_quantity() === 1, 'size 43 stock remains 1');

echo 'ADIM POS integration tests passed.' . PHP_EOL;
