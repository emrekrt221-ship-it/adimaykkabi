<?php
/** Validate ledger and stock after concurrent worker processes exit. */
defined('ABSPATH') || exit;
global $wpdb;
$sku = (string) getenv('ADIM_RACE_SKU');
$key1 = (string) getenv('ADIM_RACE_KEY1');
$key2 = (string) getenv('ADIM_RACE_KEY2');
$expected_stock = (int) getenv('ADIM_RACE_EXPECT_STOCK');
$expected_complete = 1;
$stock_id = wc_get_product_id_by_sku($sku);
if (!$stock_id) {
    throw new RuntimeException('Race SKU missing');
}
$product = wc_get_product($stock_id);
if ((int) $product->get_stock_quantity() !== $expected_stock) {
    throw new RuntimeException('Race stock mismatch; got ' . $product->get_stock_quantity()
        . ', expected ' . $expected_stock);
}
$table = $wpdb->prefix . 'adim_pos_requests';
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT request_key, order_id, status FROM {$table} WHERE request_key IN (%s, %s)",
    $key1, $key2
));
$complete = 0;
foreach ($rows as $row) {
    if ($row->status === 'completed') {
        $order = wc_get_order((int) $row->order_id);
        if (!$order || $order->get_status() !== 'completed'
            || $order->get_meta('_adim_pos_request_key') !== $row->request_key) {
            throw new RuntimeException('Completed ledger missing matching WooCommerce order');
        }
        ++$complete;
    }
}
if ($complete !== $expected_complete) {
    throw new RuntimeException("Expected one completed POS order; got {$complete}");
}
echo "PASS POS: concurrent stock = {$expected_stock}, exactly {$complete} completed order" . PHP_EOL;
