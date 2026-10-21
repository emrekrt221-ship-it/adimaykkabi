<?php
/** Real WooCommerce inspected-item single-restock regression tests. */
defined('ABSPATH') || exit;

function restock_assert(bool $pass, string $message): void
{
    if (!$pass) {
        throw new RuntimeException('FAIL ADIM RESTOCK: ' . $message);
    }
    echo 'PASS ADIM RESTOCK: ' . $message . PHP_EOL;
}
function restock_sample(string $label, int $initial): WC_Product_Simple
{
    $p = new WC_Product_Simple();
    $p->set_name('ADIM synthetic ' . $label);
    $p->set_status('publish');
    $p->set_sku('ADIM-RST-' . strtoupper(wp_generate_password(10, false, false)));
    $p->set_regular_price('1299');
    $p->set_manage_stock(true);
    $p->set_stock_quantity($initial);
    $p->set_backorders('no');
    $p->save();
    update_post_meta($p->get_id(), '_adim_sale_approved', 'yes');
    return $p;
}
function restock_return(WC_Product $product, int $quantity, string $decision, int $cashier_id, int $admin_id): array
{
    wp_set_current_user($cashier_id);
    $order = adim_pos_record_sale($product->get_sku(), $quantity, wp_generate_uuid4(), $cashier_id);
    if (is_wp_error($order)) {
        throw new RuntimeException('POS preparation: ' . $order->get_error_message());
    }
    $item = current($order->get_items('line_item'));
    $key = wp_generate_uuid4();
    $receipt = adim_return_intake($order->get_id(), $item->get_id(), $quantity, $key, $cashier_id);
    if (is_wp_error($receipt)) {
        throw new RuntimeException('Return preparation: ' . $receipt->get_error_message());
    }
    wp_set_current_user($admin_id);
    $inspection = adim_return_inspect($key, $decision, 'Returned pair photographed and condition verified', $admin_id);
    if (is_wp_error($inspection)) {
        throw new RuntimeException('Inspection preparation: ' . $inspection->get_error_message());
    }
    return [$order, $key, $item->get_id()];
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
restock_assert(!empty($admins), 'admin exists');
$adminId = (int) $admins[0]->ID;
adim_pos_install_roles();
adim_pos_install();
adim_return_install();
adim_restock_install();
restock_assert(get_option('adim_restock_db_version') === '1', 'movement table installed');
$cashierName = 'adim_rst_' . strtolower(wp_generate_password(7, false, false));
$cashierId = wp_create_user($cashierName, wp_generate_password(), $cashierName . '@example.test');
restock_assert(!is_wp_error($cashierId), 'cashier user created');
$cashier = new WP_User((int) $cashierId);
$cashier->set_role('adim_cashier');

wp_set_current_user((int) $cashierId);
restock_assert(!adim_restock_enabled(), 'restock disabled unless explicitly enabled');
$blocked = adim_return_restock(wp_generate_uuid4(), 'A sufficiently long physical stock check note', (int) $cashierId);
restock_assert(is_wp_error($blocked) && $blocked->get_error_code() === 'adim_restock_disabled',
    'default-disabled restock blocks cashier');
define('ADIM_ENABLE_POS', true);
define('ADIM_ENABLE_RETURN_INTAKE', true);

$product = restock_sample('42', 5);
[$order, $key, $itemId] = restock_return($product, 2, 'accepted', (int) $cashierId, $adminId);
restock_assert((int) wc_get_product($product->get_id())->get_stock_quantity() === 3,
    'pre-restock stock is 3');
$originalTotal = $order->get_total();

define('ADIM_ENABLE_RETURN_RESTOCK', true);
ob_start();
adim_restock_render_screen();
$beforeHtml = ob_get_clean();
restock_assert(str_contains($beforeHtml, 'İncelenmiş Ürün Stoğa Geri Alma')
    && str_contains($beforeHtml, 'Tek Seferlik Stoğa Ekle')
    && str_contains($beforeHtml, 'name="_wpnonce"'),
    'manager UI exposes explicit confirmation and protected form');
$cashierAttempt = adim_return_restock($key, 'Cashier attempts to increase stock without permission', (int) $cashierId);
restock_assert(is_wp_error($cashierAttempt) && $cashierAttempt->get_error_code() === 'adim_restock_forbidden',
    'cashier cannot trigger stock adjustment');
$badReason = adim_return_restock($key, 'short', $adminId);
restock_assert(is_wp_error($badReason) && $badReason->get_error_code() === 'adim_restock_bad_reason',
    'manager must give meaningful stock-count note');

$note = 'Inspected product is safe for sale, size and serial checked';
$stockMovement = adim_return_restock($key, $note, $adminId);
if (is_wp_error($stockMovement)) {
    throw new RuntimeException('Approved restock failed: ' . $stockMovement->get_error_message());
}
restock_assert($stockMovement->status === 'completed', 'manager stock movement persisted');
restock_assert((int) $stockMovement->quantity === 2, 'return quantity retained in ledger');
restock_assert((int) wc_get_product($product->get_id())->get_stock_quantity() === 5,
    'verified return adds two units using WooCommerce inventory API');
ob_start();
adim_restock_render_screen();
$afterHtml = ob_get_clean();
restock_assert(str_contains($afterHtml, 'daha önce stok hareketi kaydedildi'),
    'completed return is shown as already-restocked rather than new action');

$replay = adim_return_restock($key, $note, $adminId);
restock_assert(!is_wp_error($replay) && $replay->status === 'completed',
    'same return request returns prior result without stock increase');
restock_assert((int) wc_get_product($product->get_id())->get_stock_quantity() === 5,
    'repeated request does not double-restock');
restock_assert(wc_get_order($order->get_id())->get_status() === 'completed'
    && wc_get_order($order->get_id())->get_total() === $originalTotal,
    'stock restock does not refund or cancel original sale');

$rejected = restock_sample('43 reject', 3);
[$rejectOrder, $rejectKey] = restock_return($rejected, 1, 'rejected', (int) $cashierId, $adminId);
$result = adim_return_restock($rejectKey, $note, $adminId);
restock_assert(is_wp_error($result) && $result->get_error_code() === 'adim_restock_not_inspected',
    'rejected physical inspection cannot restock');
restock_assert((int) wc_get_product($rejected->get_id())->get_stock_quantity() === 2,
    'rejected item remains outside saleable inventory');

$notAuthorized = restock_sample('44 authorization revoked', 2);
[$authOrder, $authKey] = restock_return($notAuthorized, 1, 'accepted', (int) $cashierId, $adminId);
update_post_meta($notAuthorized->get_id(), '_adim_sale_approved', 'no');
$blockedProduct = adim_return_restock($authKey, $note, $adminId);
restock_assert(is_wp_error($blockedProduct)
    && $blockedProduct->get_error_code() === 'adim_restock_product_ineligible',
    'revoked product legal review prevents saleable restock');

global $wpdb;
$table = adim_restock_table();
$uncertainKey = wp_generate_uuid4();
$wpdb->insert($table, [
    'return_key' => $uncertainKey,
    'order_id' => $order->get_id(),
    'item_id' => $itemId,
    'product_id' => $product->get_id(),
    'quantity' => 1, 'status' => 'applying', 'manager_id' => $adminId,
    'reason' => $note,
    'created_at' => current_time('mysql', true), 'updated_at' => current_time('mysql', true),
]);
$uncertain = adim_return_restock($uncertainKey, $note, $adminId);
restock_assert(is_wp_error($uncertain) && $uncertain->get_error_code() === 'adim_restock_uncertain',
    'interrupted movement cannot be automatically retried');
restock_assert((int) wc_get_product($product->get_id())->get_stock_quantity() === 5,
    'interrupted movement does not blindly increase stock');

echo 'ADIM real WooCommerce reviewed-return restock regression passed.' . PHP_EOL;
