<?php
/**
 * Manager-only inspection queue for interrupted physical sales.
 * No order stock, payment or inventory quantity is modified here.
 */
defined('ABSPATH') || exit;

/** Return up to 50 uncertain ledger rows; stale means no progress for 15+ minutes. */
function adim_pos_pending_reviews(): array
{
    global $wpdb;
    if (get_option('adim_pos_db_version') !== '1') {
        return [];
    }
    $table = adim_pos_ledger_table();
    $cutoff = gmdate('Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS);
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT request_key, order_id, status, created_at, updated_at
         FROM {$table}
         WHERE status = 'needs_review'
            OR (status = 'processing' AND updated_at < %s)
         ORDER BY updated_at ASC LIMIT 50",
        $cutoff
    ));
    return is_array($rows) ? $rows : [];
}

/**
 * Mark a validated, linked WooCommerce order as MANUALLY REVIEWED.
 * Manager must inspect bank/cash receipt, stock and order before acknowledging.
 * Acknowledging never modifies actual inventory or auto-refunds an order.
 *
 * @return true|WP_Error
 */
function adim_pos_acknowledge_review(string $request_key, string $reason, int $reviewer_id)
{
    global $wpdb;
    if (!adim_pos_can_reconcile() || $reviewer_id !== get_current_user_id()) {
        return new WP_Error('adim_pos_review_forbidden', 'Yönetici inceleme yetkisi gerekli.');
    }
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $request_key)) {
        return new WP_Error('adim_pos_review_bad_key', 'İşlem anahtarı geçersiz.');
    }
    $reason = trim($reason);
    if (function_exists('mb_strlen') ? mb_strlen($reason) < 15 : strlen($reason) < 15) {
        return new WP_Error('adim_pos_review_short_reason', 'Tahsilat, sipariş ve stok kontrolünü açıklayan en az 15 karakterlik not yazın.');
    }
    if (strlen($reason) > 1500) {
        return new WP_Error('adim_pos_review_long_reason', 'İnceleme notu çok uzun.');
    }
    if (get_option('adim_pos_db_version') !== '1') {
        return new WP_Error('adim_pos_review_not_ready', 'POS işlem tablosu mevcut değil.');
    }

    $table = adim_pos_ledger_table();
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT request_key, order_id, status, updated_at FROM {$table} WHERE request_key = %s",
        $request_key
    ));
    if (!$row) {
        return new WP_Error('adim_pos_review_missing', 'İşlem kaydı bulunamadı.');
    }
    if (!in_array($row->status, ['needs_review', 'processing'], true)) {
        return new WP_Error('adim_pos_review_not_pending', 'Bu işlem artık inceleme kuyruğunda değil.');
    }
    $cutoff = gmdate('Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS);
    if ($row->status === 'processing' && $row->updated_at >= $cutoff) {
        return new WP_Error('adim_pos_review_in_progress', 'İşlem hâlâ sürüyor olabilir; en az 15 dakika bekleyin.');
    }
    // Orphaned requests require developer investigation, not a click-to-dismiss.
    if ((int) $row->order_id <= 0) {
        return new WP_Error('adim_pos_review_no_order', 'Bağlı sipariş bulunamadı; teknik inceleme gerekli.');
    }
    $order = wc_get_order((int) $row->order_id);
    if (!($order instanceof WC_Order)
        || $order->get_created_via() !== 'adim-pos'
        || $order->get_meta('_adim_pos_request_key') !== $request_key) {
        return new WP_Error('adim_pos_review_mismatch', 'İşlem ve sipariş kayıtları uyuşmuyor.');
    }

    // Optimistic update: no concurrent review may claim the same record.
    $changed = $wpdb->query($wpdb->prepare(
        "UPDATE {$table}
            SET status = 'reviewed', updated_at = %s
          WHERE request_key = %s AND order_id = %d AND status = %s AND updated_at = %s",
        current_time('mysql', true), $request_key, (int) $row->order_id, $row->status, $row->updated_at
    ));
    if ($changed !== 1) {
        return new WP_Error('adim_pos_review_race', 'İşlem başka bir yönetici tarafından değiştirildi; ekranı yenileyin.');
    }
    // Retain the audit trail in the HPOS-compatible order note store.
    $order->add_order_note(
        sprintf('ADIM manuel POS incelemesi (stok değiştirilmedi). Yönetici #%d. Not: %s',
            $reviewer_id, $reason),
        0,
        true
    );
    return true;
}

add_action('admin_menu', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }
    add_submenu_page(
        'woocommerce',
        __('ADIM POS İnceleme', 'adim-operations'),
        __('POS İnceleme', 'adim-operations'),
        'manage_woocommerce',
        'adim-pos-review',
        'adim_pos_render_review_screen'
    );
});

add_action('admin_post_adim_pos_acknowledge', static function (): void {
    if (!adim_pos_can_reconcile()) {
        wp_die(esc_html__('Yalnızca yönetici inceleme yapabilir.', 'adim-operations'), '', ['response' => 403]);
    }
    check_admin_referer('adim_pos_acknowledge');
    $key = isset($_POST['adim_request_key']) && is_string($_POST['adim_request_key'])
        ? sanitize_text_field(wp_unslash($_POST['adim_request_key'])) : '';
    $reason = isset($_POST['adim_review_reason']) && is_string($_POST['adim_review_reason'])
        ? sanitize_textarea_field(wp_unslash($_POST['adim_review_reason'])) : '';
    $result = adim_pos_acknowledge_review($key, $reason, get_current_user_id());
    wp_safe_redirect(add_query_arg([
        'page' => 'adim-pos-review',
        'adim_review_result' => is_wp_error($result) ? sanitize_key($result->get_error_code()) : 'recorded',
    ], admin_url('admin.php')));
    exit;
});

function adim_pos_render_review_screen(): void
{
    if (!adim_pos_can_reconcile()) {
        wp_die(esc_html__('Bu ekrana erişim yetkiniz yok.', 'adim-operations'), '', ['response' => 403]);
    }
    echo '<div class="wrap" style="max-width:1040px">';
    echo '<h1>ADIM — Kasa İşlemi İnceleme Kuyruğu</h1>';
    echo '<p><strong>Uyarı:</strong> Bu ekran stok, tahsilat, iade ve sipariş durumunu otomatik değiştirmez. Yönetici, kasa fişi, WooCommerce siparişi ve fiziksel stokları karşılaştırmalıdır.</p>';
    $result = isset($_GET['adim_review_result']) ? sanitize_key($_GET['adim_review_result']) : '';
    if ($result === 'recorded') {
        echo '<div class="notice notice-success"><p>İnceleme notu kaydedildi. Stok değiştirilmedi.</p></div>';
    } elseif ($result !== '') {
        echo '<div class="notice notice-error"><p>İşlem kaydedilemedi: ' . esc_html($result) . '. Kayıt yeniden incelenmeli.</p></div>';
    }
    $rows = adim_pos_pending_reviews();
    if (!$rows) {
        echo '<p>Şu anda manuel inceleme gerektiren eski veya başarısız işlem yok.</p></div>';
        return;
    }
    foreach ($rows as $row) {
        $order = (int) $row->order_id > 0 ? wc_get_order((int) $row->order_id) : false;
        $linked = $order instanceof WC_Order && $order->get_created_via() === 'adim-pos'
            && $order->get_meta('_adim_pos_request_key') === $row->request_key;
        echo '<section style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;margin:16px 0">';
        echo '<h2 style="margin-top:0">Kayıt ' . esc_html(substr($row->request_key, 0, 8)) . '…</h2>';
        echo '<p><strong>Durum:</strong> ' . esc_html($row->status)
            . ' · <strong>Sipariş:</strong> ' . esc_html($row->order_id ? '#' . $row->order_id : 'Yok')
            . ' · <strong>Son değişiklik (UTC):</strong> ' . esc_html($row->updated_at) . '</p>';
        if ($linked) {
            echo '<p>WooCommerce sipariş durumu: <strong>' . esc_html($order->get_status())
                . '</strong>. Sipariş ve tahsilat belgelerini manuel kontrol edin.</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="adim_pos_acknowledge">';
            echo '<input type="hidden" name="adim_request_key" value="' . esc_attr($row->request_key) . '">';
            wp_nonce_field('adim_pos_acknowledge');
            echo '<label for="adim-review-' . esc_attr($row->request_key) . '"><strong>İnceleme kanıtları ve sonuç (en az 15 karakter)</strong></label>';
            echo '<textarea id="adim-review-' . esc_attr($row->request_key)
                . '" name="adim_review_reason" rows="3" maxlength="1500" required style="width:100%;margin:6px 0"></textarea>';
            echo '<p><button class="button button-primary" type="submit">İncelendi Olarak İşaretle (Stok Değişmez)</button></p></form>';
        } else {
            echo '<p><strong>Teknik inceleme gerekli:</strong> Sipariş bulunamadı veya işlem kimliği uyuşmuyor. Bu kayıt ekrandan kapatılamaz.</p>';
        }
        echo '</section>';
    }
    echo '</div>';
}
