<?php
/**
 * ADIM inspected physical return — explicit one-time inventory restoration.
 *
 * Never refunds a customer or modifies order payment/status. A durable unique
 * movement claim is recorded BEFORE stock changes. Uncertain claims may ONLY
 * be reconciled manually; they are never automatically retried.
 */
defined('ABSPATH') || exit;

function adim_restock_enabled(): bool
{
    return defined('ADIM_ENABLE_RETURN_RESTOCK') && ADIM_ENABLE_RETURN_RESTOCK === true;
}

function adim_restock_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'adim_return_stock_movements';
}

function adim_restock_install(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = adim_restock_table();
    $charset = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$table} (
return_key varchar(36) NOT NULL,
order_id bigint(20) unsigned NOT NULL,
item_id bigint(20) unsigned NOT NULL,
product_id bigint(20) unsigned NOT NULL,
quantity int(10) unsigned NOT NULL,
status varchar(24) NOT NULL DEFAULT 'applying',
manager_id bigint(20) unsigned NOT NULL,
reason text NOT NULL,
created_at datetime NOT NULL,
updated_at datetime NOT NULL,
PRIMARY KEY  (return_key),
KEY status (status),
KEY order_id (order_id)
) {$charset};");
    update_option('adim_restock_db_version', '1', false);
}

/**
 * A separate privileged step AFTER physical inspection.
 * Returns a ledger row on successful first call or identical completed replay.
 * Returns WP_Error and NEVER silently retries an unknown stock outcome.
 *
 * @return object|WP_Error
 */
function adim_return_restock(string $return_key, string $reason, int $manager_id)
{
    global $wpdb;

    if (!adim_restock_enabled()) {
        return new WP_Error('adim_restock_disabled', 'Stoğa geri alma işlemi etkin değil.');
    }
    if (!function_exists('adim_pos_can_reconcile') || !adim_pos_can_reconcile()
        || $manager_id !== get_current_user_id()) {
        return new WP_Error('adim_restock_forbidden', 'Yetkili mağaza yöneticisi gerekli.');
    }
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $return_key)) {
        return new WP_Error('adim_restock_bad_key', 'İade kimliği geçersiz.');
    }
    $reason = trim($reason);
    $note_length = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
    if ($note_length < 15 || $note_length > 1000) {
        return new WP_Error('adim_restock_bad_reason', 'İnceleme ve stok sayımı hakkında 15–1000 karakter açıklama gerekli.');
    }
    if (get_option('adim_returns_db_version') !== '1' || get_option('adim_restock_db_version') !== '1') {
        return new WP_Error('adim_restock_not_ready', 'Stok hareketi veritabanı hazır değil.');
    }

    $table = adim_restock_table();
    // First check any prior movement. Never create another movement for the same return.
    $previous = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE return_key=%s", $return_key));
    if ($previous) {
        return $previous->status === 'completed' ? $previous
            : new WP_Error('adim_restock_uncertain', 'İşlem başlamış fakat tamamlanmamış. Tekrar denemeyin; stok ve siparişi elle inceleyin.');
    }

    $return = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . adim_return_table() . " WHERE request_key=%s", $return_key
    ));
    if (!$return || $return->state !== 'accepted' || (int) $return->reviewed_by < 1) {
        return new WP_Error('adim_restock_not_inspected', 'Ürün incelemesi kabul edilmemiş.');
    }
    if ((int) $return->quantity <= 0) {
        return new WP_Error('adim_restock_bad_quantity', 'İade miktarı geçersiz.');
    }
    $order = wc_get_order((int) $return->order_id);
    if (!($order instanceof WC_Order)
        || $order->get_created_via() !== 'adim-pos'
        || $order->get_meta('_adim_channel') !== 'in_store') {
        return new WP_Error('adim_restock_invalid_order', 'İade ile POS siparişi eşleşmiyor.');
    }
    $item = $order->get_item((int) $return->item_id);
    if (!($item instanceof WC_Order_Item_Product)
        || (int) $return->quantity > (int) $item->get_quantity()
        || (int) $item->get_meta('_reduced_stock', true) < (int) $return->quantity) {
        return new WP_Error('adim_restock_invalid_item', 'Satılan ve stoktan düşülen ürün miktarı doğrulanamadı.');
    }
    // A WooCommerce refund may already have restored stock through its own
    // workflow. Refuse an additional ADIM restock; operator investigates.
    if (method_exists($order, 'get_qty_refunded_for_item')
        && abs((float) $order->get_qty_refunded_for_item((int) $return->item_id)) > 0) {
        return new WP_Error('adim_restock_refund_exists', 'Siparişte kayıtlı iade bulundu; ikinci stok artırımı yapılmıyor.');
    }
    $product_id = (int) ($item->get_variation_id() ?: $item->get_product_id());
    $product = $product_id > 0 ? wc_get_product($product_id) : false;
    if (!($product instanceof WC_Product)
        || $product->get_status() !== 'publish'
        || !adim_ops_is_reviewed($product)
        || !$product->managing_stock()
        || (int) $product->get_stock_managed_by_id() !== $product_id
        || $product->backorders_allowed()) {
        return new WP_Error('adim_restock_product_ineligible', 'Satılabilir, onaylı ve numara bazlı stok takipli ürün gerekiyor.');
    }
    $qty = (int) $return->quantity;

    // Atomic INSERT IGNORE on unique return_key is the cross-worker barrier.
    // If the worker crashes after claiming, replays MUST fail closed.
    $now = current_time('mysql', true);
    $claimed = $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$table}
         (return_key,order_id,item_id,product_id,quantity,status,manager_id,reason,created_at,updated_at)
         VALUES (%s,%d,%d,%d,%d,'applying',%d,%s,%s,%s)",
        $return_key, (int) $return->order_id, (int) $return->item_id, $product_id,
        $qty, $manager_id, $reason, $now, $now
    ));
    if ($claimed !== 1) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE return_key=%s", $return_key));
        return $row && $row->status === 'completed' ? $row
            : new WP_Error('adim_restock_uncertain', 'Aynı iadenin stok işlemi zaten başlatılmış; tekrar artırılmıyor.');
    }

    try {
        $note_id = $order->add_order_note(sprintf(
            'ADIM stoğa geri alma isteği. İade %s, ürün #%d, miktar %d, yönetici #%d. Açıklama: %s',
            $return_key, $product_id, $qty, $manager_id, $reason
        ), 0, true);
        if (!$note_id) {
            throw new RuntimeException('Stok hareketi için ilk denetim notu kaydedilemedi.');
        }

        // WooCommerce core uses an atomic stock update; do not manipulate _stock via SQL.
        $new_stock = wc_update_product_stock($product, $qty, 'increase');
        if (!is_numeric($new_stock)) {
            throw new RuntimeException('WooCommerce stok artırımı doğrulanamadı.');
        }

        // Last status write may fail after stock has changed. Preserve uncertainty
        // and never silently reapply the adjustment on the next request.
        $saved = $wpdb->update($table, [
            'status' => 'completed', 'updated_at' => current_time('mysql', true),
        ], ['return_key' => $return_key, 'status' => 'applying'],
            ['%s', '%s'], ['%s', '%s']);
        if ($saved !== 1) {
            throw new RuntimeException('Stok artırılmış olabilir, tamamlanma kaydı başarısız. Yönetici incelemesi gerekli.');
        }
        $order->add_order_note(sprintf(
            'ADIM stok artırımı tamamlandı. İade %s, ürün #%d, adet +%d. Ödeme/sipariş durumu değiştirilmedi.',
            $return_key, $product_id, $qty
        ), 0, true);
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE return_key=%s", $return_key));
    } catch (Throwable $error) {
        // Fail-closed: whether Woo stock was changed is potentially UNKNOWN.
        // Do not run an automatic compensation/retry on this return.
        $wpdb->update($table, [
            'status' => 'needs_review', 'updated_at' => current_time('mysql', true),
        ], ['return_key' => $return_key, 'status' => 'applying'], ['%s', '%s'], ['%s', '%s']);
        return new WP_Error('adim_restock_uncertain',
            'İşlem sonucu belirsiz. Bu ürünü ikinci kez stoğa eklemeyin; kayıt ve fiziksel stok kontrolü gerekli.');
    }
}
