<?php
/** Inspect durable records after an independent PHP process exits unexpectedly. */
defined('ABSPATH') || exit;
function crash_assert(bool $ok, string $description): void
{
    if (!$ok) {
        throw new RuntimeException('FAIL crash: ' . $description);
    }
    echo 'PASS crash: ' . $description . PHP_EOL;
}

$stage = (string) getenv('ADIM_CRASH_STAGE');
$key = (string) getenv('ADIM_CRASH_KEY');
$sku = (string) getenv('ADIM_CRASH_SKU');
$expected_unlinked = in_array($stage, ['ledger_claimed', 'order_saved_before_link'], true);
$expected_no_order = $stage === 'ledger_claimed';
$expected_reduced = in_array($stage, ['stock_reduced', 'order_completed'], true);
global $wpdb;
$table = adim_pos_ledger_table();
$ledger = $wpdb->get_row($wpdb->prepare(
    "SELECT request_key, order_id, status, updated_at FROM {$table} WHERE request_key=%s",
    $key
));
crash_assert((bool) $ledger && $ledger->status === 'processing',
    $stage . ': ledger remains processing after hard exit');
crash_assert(((int) $ledger->order_id === 0) === $expected_unlinked,
    $stage . ': ledger link state is correct');

$orders = wc_get_orders([
    'limit' => 10,
    'type' => 'shop_order',
    'meta_key' => '_adim_pos_request_key',
    'meta_value' => $key,
]);
crash_assert(count($orders) === ($expected_no_order ? 0 : 1),
    $stage . ': no duplicated order after hard exit');
if (!$expected_no_order) {
    crash_assert($orders[0]->get_created_via() === 'adim-pos',
        $stage . ': persistent order belongs to POS');
    if (!$expected_unlinked) {
        crash_assert($orders[0]->get_id() === (int) $ledger->order_id,
            $stage . ': ledger order link matches');
    }
    crash_assert(
        $orders[0]->get_status() === ($stage === 'order_completed' ? 'completed' : 'pending'),
        $stage . ': persisted order status correct');
}
$stockId = wc_get_product_id_by_sku($sku);
$stockBeforeReview = (int) wc_get_product($stockId)->get_stock_quantity();
crash_assert($stockBeforeReview === ($expected_reduced ? 1 : 2),
    $stage . ': no accidental stock adjustment');

$admins = get_users(['role' => 'administrator', 'number' => 1]);
wp_set_current_user((int) $admins[0]->ID);
define('ADIM_ENABLE_POS', true);
$result = adim_pos_record_sale($sku, 1, $key, get_current_user_id());
crash_assert(is_wp_error($result) && $result->get_error_code() === 'adim_pos_duplicate',
    $stage . ': retry with same UUID blocked until human review');
crash_assert((int) wc_get_product($stockId)->get_stock_quantity() === $stockBeforeReview,
    $stage . ': duplicate retry has zero stock effects');

$old = gmdate('Y-m-d H:i:s', time() - 21 * MINUTE_IN_SECONDS);
$wpdb->update($table, ['updated_at' => $old], ['request_key' => $key], ['%s'], ['%s']);
$reviewRows = adim_pos_pending_reviews();
crash_assert((bool) array_filter($reviewRows, static fn($row) => $row->request_key === $key),
    $stage . ': stale interrupted request appears in review queue');

$reason = 'Kasa fişi, tahsilat ve stok sayımı ayrı ayrı kontrol edildi';
$review = adim_pos_acknowledge_review($key, $reason, get_current_user_id());
if ($expected_unlinked) {
    crash_assert(is_wp_error($review) && $review->get_error_code() === 'adim_pos_review_no_order',
        $stage . ': unlinked request cannot be dismissed as resolved');
    $state = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE request_key=%s", $key));
    crash_assert($state === 'processing', $stage . ': orphan remains for technical investigation');
} else {
    crash_assert($review === true, $stage . ': linked order can be manually acknowledged');
    $state = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE request_key=%s", $key));
    crash_assert($state === 'reviewed', $stage . ': review creates persistent audit state');
}
crash_assert((int) wc_get_product($stockId)->get_stock_quantity() === $stockBeforeReview,
    $stage . ': manual review never changes stock');
$afterOrders = wc_get_orders([
    'limit' => 10,
    'type' => 'shop_order',
    'meta_key' => '_adim_pos_request_key',
    'meta_value' => $key,
]);
crash_assert(count($afterOrders) === count($orders),
    $stage . ': no extra POS order after duplicate and review');
echo 'ADIM crash checkpoint validated: ' . $stage . PHP_EOL;
