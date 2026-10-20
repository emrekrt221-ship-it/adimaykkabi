<?php
/**
 * ADIM least-privilege cashier and interrupted-sale reconciliation tests
 * against actual WordPress, WooCommerce, MySQL, and order persistence.
 */
defined('ABSPATH') || exit;
function adim_review_assert(bool $ok, string $why): void
{
    if (!$ok) {
        throw new RuntimeException('FAIL POS RECONCILE: ' . $why);
    }
    echo 'PASS POS RECONCILE: ' . $why . PHP_EOL;
}
function adim_review_fixture(string $suffix): WC_Product_Simple
{
    $p = new WC_Product_Simple();
    $p->set_name('ADIM safe synthetic size test ' . $suffix);
    $p->set_status('publish');
    $p->set_sku('ADIM-RECON-' . $suffix);
    $p->set_regular_price('1200');
    $p->set_manage_stock(true);
    $p->set_stock_quantity(3);
    $p->set_backorders('no');
    $p->save();
    update_post_meta($p->get_id(), '_adim_sale_approved', 'yes');
    return $p;
}
add_filter('pre_wp_mail', '__return_true');
adim_pos_install_roles();
adim_review_assert(get_role('adim_cashier') instanceof WP_Role, 'cashier role registered');
$administrator = get_users(['role' => 'administrator', 'number' => 1])[0] ?? null;
adim_review_assert($administrator instanceof WP_User, 'administrator exists');
$cashierId = wp_create_user('adim_ci_cashier_' . strtolower(wp_generate_password(6, false, false)),
    wp_generate_password(), 'ci-cashier-' . strtolower(wp_generate_password(6, false, false)) . '@example.test');
adim_review_assert(!is_wp_error($cashierId), 'dedicated cashier user created');
$cashier = new WP_User((int) $cashierId);
$cashier->set_role('adim_cashier');
wp_set_current_user((int) $cashierId);
adim_review_assert(adim_pos_can_sell(), 'cashier has physical sale capability');
adim_review_assert(!current_user_can('manage_woocommerce'), 'cashier lacks general WooCommerce administration');
adim_review_assert(!current_user_can('manage_options'), 'cashier cannot administer site options');
adim_review_assert(!adim_pos_can_reconcile(), 'cashier cannot resolve uncertain payment/stock');
ob_start();
adim_pos_render_screen();
$cashierHtml = ob_get_clean();
adim_review_assert(str_contains($cashierHtml, 'ADIM AYAKKABI'), 'cashier can view POS screen');
adim_pos_install();
define('ADIM_ENABLE_POS', true);
$product = adim_review_fixture(strtoupper(wp_generate_password(10, false, false)));
$key = wp_generate_uuid4();
$order = adim_pos_record_sale($product->get_sku(), 1, $key, (int) $cashierId);
adim_review_assert($order instanceof WC_Order && $order->get_status() === 'completed',
    'cashier can create one authorized physical sale without elevated role');
adim_review_assert((int) wc_get_product($product->get_id())->get_stock_quantity() === 2,
    'cashier sale decrements stock via WooCommerce');

global $wpdb;
$table = adim_pos_ledger_table();
$wpdb->update($table, ['status' => 'needs_review'], ['request_key' => $key], ['%s'], ['%s']);
$wrong = adim_pos_acknowledge_review($key, 'Evraklar ve stok sayimi kontrol edildi', (int) $cashierId);
adim_review_assert(is_wp_error($wrong) && $wrong->get_error_code() === 'adim_pos_review_forbidden',
    'cashier cannot acknowledge an uncertain ledger entry');
$screenDenied = !adim_pos_can_reconcile();
adim_review_assert($screenDenied, 'review screen manager guard stays denied for cashier');

wp_set_current_user((int) $administrator->ID);
adim_review_assert(adim_pos_can_reconcile(), 'store manager/administrator can review');
$short = adim_pos_acknowledge_review($key, 'kısa', (int) $administrator->ID);
adim_review_assert(is_wp_error($short), 'manager cannot clear review without evidence');
$reason = 'Fiziksel kasa kaydı ve sipariş kontrol edildi, stok 2 adet';
$ok = adim_pos_acknowledge_review($key, $reason, (int) $administrator->ID);
adim_review_assert($ok === true, 'manager acknowledges matching order with explanation');
$state = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE request_key=%s", $key));
adim_review_assert($state === 'reviewed', 'ledger records manager review');
adim_review_assert((int) wc_get_product($product->get_id())->get_stock_quantity() === 2,
    'manual review does not restore or consume stock');
$notes = wc_get_order_notes(['order_id' => $order->get_id(), 'limit' => 20]);
adim_review_assert(
    count(array_filter($notes, static fn($note) => str_contains($note->content, $reason))) >= 1,
    'WooCommerce order retains review evidence'
);
// A delayed worker must never be able to overwrite an acknowledged review.
$late = $wpdb->update($table, ['status' => 'completed'],
    ['request_key' => $key, 'status' => 'processing'], ['%s'], ['%s', '%s']);
adim_review_assert($late === 0, 'late worker cannot override reviewed ledger through optimistic update');
adim_review_assert($wpdb->get_var($wpdb->prepare(
    "SELECT status FROM {$table} WHERE request_key=%s", $key
)) === 'reviewed', 'previously acknowledged review remains immutable');

$second = adim_pos_acknowledge_review($key, $reason, (int) $administrator->ID);
adim_review_assert(is_wp_error($second) && $second->get_error_code() === 'adim_pos_review_not_pending',
    'same review cannot be acknowledged twice');

// Interrupted request with no order: must remain blocked for developer investigation.
$orphan = wp_generate_uuid4();
$old = gmdate('Y-m-d H:i:s', time() - 22 * MINUTE_IN_SECONDS);
$wpdb->insert($table, [
    'request_key' => $orphan, 'order_id' => 0,
    'status' => 'processing', 'created_at' => $old, 'updated_at' => $old,
], ['%s', '%d', '%s', '%s', '%s']);
$rows = adim_pos_pending_reviews();
adim_review_assert((bool) array_filter($rows, static fn($row) => $row->request_key === $orphan),
    'stale processing without order appears in review queue');
$orphanResult = adim_pos_acknowledge_review($orphan, $reason, (int) $administrator->ID);
adim_review_assert(is_wp_error($orphanResult) && $orphanResult->get_error_code() === 'adim_pos_review_no_order',
    'missing order cannot be silently marked reviewed');

$pending = wp_generate_uuid4();
$now = current_time('mysql', true);
$wpdb->insert($table, [
    'request_key' => $pending, 'order_id' => $order->get_id(),
    'status' => 'processing', 'created_at' => $now, 'updated_at' => $now,
], ['%s', '%d', '%s', '%s', '%s']);
$pendingResult = adim_pos_acknowledge_review($pending, $reason, (int) $administrator->ID);
adim_review_assert(is_wp_error($pendingResult)
    && $pendingResult->get_error_code() === 'adim_pos_review_in_progress',
    'recent processing request cannot be prematurely acknowledged');

ob_start();
adim_pos_render_review_screen();
$html = ob_get_clean();
adim_review_assert(str_contains($html, 'Kasa İşlemi İnceleme Kuyruğu'), 'manager review screen renders');
adim_review_assert(str_contains($html, 'Teknik inceleme gerekli'), 'orphan row warns of manual developer review');
adim_review_assert(!str_contains($html, 'name="adim_request_key" value="' . $orphan . '"'),
    'orphan has no one-click dismissal form');

echo 'ADIM real WooCommerce POS role and reconciliation tests passed.' . PHP_EOL;
