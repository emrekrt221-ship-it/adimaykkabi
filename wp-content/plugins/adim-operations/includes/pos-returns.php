<?php
/**
 * ADIM physical return intake and item inspection.
 *
 * Records customer return evidence only. Never refunds payment, adjusts stock,
 * or cancels a WooCommerce order. Separate finance/stock workflows required.
 */
defined('ABSPATH') || exit;

function adim_return_install(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = $wpdb->prefix . 'adim_return_inspections';
    $charset = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$table} (
request_key varchar(36) NOT NULL,
order_id bigint(20) unsigned NOT NULL,
item_id bigint(20) unsigned NOT NULL,
quantity int unsigned NOT NULL,
state varchar(24) NOT NULL DEFAULT 'quarantine',
received_by bigint(20) unsigned NOT NULL,
reviewed_by bigint(20) unsigned NOT NULL DEFAULT 0,
decision_note text NOT NULL,
created_at datetime NOT NULL,
updated_at datetime NOT NULL,
PRIMARY KEY  (request_key),
KEY order_item (order_id,item_id),
KEY state (state)
) {$charset};");
    update_option('adim_returns_db_version', '1', false);
}

/** Return request ledger, independent from WooCommerce inventory and payment. */
function adim_return_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'adim_return_inspections';
}

function adim_return_intake(int $order_id, int $item_id, int $quantity, string $key, int $operator_id)
{
    global $wpdb;
    if (!defined('ADIM_ENABLE_RETURN_INTAKE') || ADIM_ENABLE_RETURN_INTAKE !== true) {
        return new WP_Error('adim_returns_disabled', 'İade teslim alma henüz etkin değil.');
    }
    if (!function_exists('adim_pos_can_sell') || !adim_pos_can_sell() || $operator_id !== get_current_user_id()) {
        return new WP_Error('adim_return_forbidden', 'Kasiyer yetkisi gerekli.');
    }
    if (get_option('adim_returns_db_version') !== '1') {
        return new WP_Error('adim_return_not_ready', 'İade kayıtları hazır değil.');
    }
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key) || $quantity < 1) {
        return new WP_Error('adim_return_bad_request', 'İşlem kimliği veya adet geçersiz.');
    }
    $order = wc_get_order($order_id);
    if (!($order instanceof WC_Order) || $order->get_created_via() !== 'adim-pos'
        || $order->get_meta('_adim_channel') !== 'in_store') {
        return new WP_Error('adim_return_not_pos', 'Geçerli fiziksel mağaza siparişi bulunamadı.');
    }
    $item = $order->get_item($item_id);
    if (!($item instanceof WC_Order_Item_Product) || $quantity > (int) $item->get_quantity()) {
        return new WP_Error('adim_return_bad_item', 'Sipariş kalemi veya adet uygun değil.');
    }
    $table = adim_return_table();
    // Lock the per-item intake using a MySQL named lock. This lock does not
    // touch Woo stock and covers parallel cashiers and different UUIDs.
    $lock = 'adim_return_' . md5($wpdb->prefix . ':' . $order_id . ':' . $item_id);
    $acquired = (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock));
    if ($acquired !== 1) {
        return new WP_Error('adim_return_busy', 'Bu ürün için başka bir iade işlemi sürüyor.');
    }
    try {
        $prior = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE request_key = %s", $key));
        if ($prior) {
            if ((int) $prior->order_id === $order_id && (int) $prior->item_id === $item_id
                && (int) $prior->quantity === $quantity) {
                return $prior; // identical retry returns the original receipt.
            }
            return new WP_Error('adim_return_key_conflict', 'İşlem kimliği başka iade için kullanılmış.');
        }
        $already = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(quantity),0) FROM {$table} WHERE order_id = %d AND item_id = %d",
            $order_id, $item_id
        ));
        if ($already + $quantity > (int) $item->get_quantity()) {
            return new WP_Error('adim_return_over_quantity', 'Alınan toplam iade sayısı satılan adedi geçemez.');
        }
        $now = current_time('mysql', true);
        $saved = $wpdb->insert($table, [
            'request_key' => $key, 'order_id' => $order_id, 'item_id' => $item_id,
            'quantity' => $quantity, 'state' => 'quarantine',
            'received_by' => $operator_id, 'reviewed_by' => 0,
            'decision_note' => '', 'created_at' => $now, 'updated_at' => $now,
        ], ['%s','%d','%d','%d','%s','%d','%d','%s','%s','%s']);
        if ($saved !== 1) {
            return new WP_Error('adim_return_write_failed', 'İade kayıt işlemi başarısız.');
        }
        $order->add_order_note(sprintf(
            'ADIM iade teslim alındı; inceleme/karantina. Kayıt %s, kalem #%d, adet %d, personel #%d. Otomatik para iadesi veya stok güncellemesi yapılmadı.',
            $key, $item_id, $quantity, $operator_id
        ), 0, true);
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE request_key=%s", $key));
    } finally {
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
    }
}

/**
 * Manager records physical inspection outcome; acceptance never restocks,
 * pays back, cancels or changes the existing WooCommerce sale.
 */
function adim_return_inspect(string $key, string $decision, string $note, int $manager_id)
{
    global $wpdb;
    if (!function_exists('adim_pos_can_reconcile') || !adim_pos_can_reconcile()
        || $manager_id !== get_current_user_id()) {
        return new WP_Error('adim_return_review_forbidden', 'Yönetici inceleme yetkisi gerekli.');
    }
    if (!in_array($decision, ['accepted', 'rejected'], true)
        || !preg_match('/^[0-9a-f-]{36}$/i', $key)) {
        return new WP_Error('adim_return_review_bad_input', 'İnceleme kararı veya kayıt kimliği geçersiz.');
    }
    $note = trim($note);
    if (mb_strlen($note) < 15 || mb_strlen($note) > 1000) {
        return new WP_Error('adim_return_review_note', '15–1000 karakter açıklama gerekli.');
    }
    $table = adim_return_table();
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE request_key = %s", $key
    ));
    if (!$row || $row->state !== 'quarantine') {
        return new WP_Error('adim_return_not_pending', 'İnceleme bekleyen kayıt bulunamadı.');
    }
    $order = wc_get_order((int) $row->order_id);
    if (!($order instanceof WC_Order) || $order->get_created_via() !== 'adim-pos'
        || !($order->get_item((int) $row->item_id) instanceof WC_Order_Item_Product)) {
        return new WP_Error('adim_return_review_mismatch', 'Sipariş ile iade kaydı uyuşmuyor.');
    }
    // A manager's review note is saved BEFORE changing ledger status.
    $saved_note = $order->add_order_note(sprintf(
        'ADIM iade ürün kontrolü: %s. İade kaydı %s, personel #%d. Not: %s. Bu işlem para iadesi, stok artışı veya sipariş durum değişikliği yapmaz.',
        $decision, $key, $manager_id, $note
    ), 0, true);
    if (!$saved_note) {
        return new WP_Error('adim_return_review_note_failed', 'Sipariş inceleme notu kaydedilemedi.');
    }
    $changed = $wpdb->query($wpdb->prepare(
        "UPDATE {$table} SET state=%s, reviewed_by=%d, decision_note=%s, updated_at=%s
         WHERE request_key=%s AND state='quarantine'",
        $decision, $manager_id, $note, current_time('mysql', true), $key
    ));
    if ($changed !== 1) {
        return new WP_Error('adim_return_review_race', 'Kayıt başka bir yönetici tarafından değiştirildi.');
    }
    return true;
}
