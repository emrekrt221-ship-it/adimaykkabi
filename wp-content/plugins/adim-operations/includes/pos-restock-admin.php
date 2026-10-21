<?php
/**
 * Manager-only explicit restock authorization screen.
 * No customer-facing checkout, refunds, or stock change on page view.
 */
defined('ABSPATH') || exit;

add_action('admin_menu', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }
    add_submenu_page(
        'woocommerce',
        __('ADIM Stoğa Geri Alma', 'adim-operations'),
        __('Stoğa Geri Alma', 'adim-operations'),
        'manage_woocommerce',
        'adim-return-restock',
        'adim_restock_render_screen'
    );
});

add_action('admin_post_adim_restock_approved', static function (): void {
    if (!adim_pos_can_reconcile()) {
        wp_die(esc_html__('Yönetici yetkisi gerekli.', 'adim-operations'), '', ['response' => 403]);
    }
    check_admin_referer('adim_restock_approved');
    if (empty($_POST['adim_confirm_condition']) || empty($_POST['adim_confirm_stock_count'])) {
        $error = 'adim_restock_confirmation_required';
    } else {
        $key = isset($_POST['adim_return_key']) && is_string($_POST['adim_return_key'])
            ? sanitize_text_field(wp_unslash($_POST['adim_return_key'])) : '';
        $reason = isset($_POST['adim_restock_reason']) && is_string($_POST['adim_restock_reason'])
            ? sanitize_textarea_field(wp_unslash($_POST['adim_restock_reason'])) : '';
        $result = adim_return_restock($key, $reason, get_current_user_id());
        $error = is_wp_error($result) ? sanitize_key($result->get_error_code()) : 'completed';
    }
    wp_safe_redirect(add_query_arg([
        'page' => 'adim-return-restock',
        'adim_restock_result' => $error,
    ], admin_url('admin.php')));
    exit;
});

function adim_restock_render_screen(): void
{
    global $wpdb;
    if (!adim_pos_can_reconcile()) {
        wp_die(esc_html__('Yönetici yetkisi gerekli.', 'adim-operations'), '', ['response' => 403]);
    }
    echo '<div class="wrap" style="max-width:1080px">';
    echo '<h1>ADIM — İncelenmiş Ürün Stoğa Geri Alma</h1>';
    echo '<p><strong>Yalnızca fiziksel kontrol sonrası tekrar satılabilir olduğu doğrulanan ayakkabılar.</strong> Para iadesi bu ekrandan yapılmaz. Bu ekran stok artırımı yapmadan açılabilir.</p>';

    $result = isset($_GET['adim_restock_result']) ? sanitize_key($_GET['adim_restock_result']) : '';
    if ($result === 'completed') {
        echo '<div class="notice notice-success"><p>Stok hareketi kaydedildi. WooCommerce stok adedi ayrıca kontrol edilmelidir.</p></div>';
    } elseif ($result !== '') {
        echo '<div class="notice notice-error"><p>İşlem tamamlanamadı veya inceleme gerekiyor: '
            . esc_html($result) . '. Yeni bir işlem oluşturmadan stok hareketlerini kontrol edin.</p></div>';
    }
    if (!adim_restock_enabled()) {
        echo '<div class="notice notice-warning"><p>Stoğa geri alma şu anda devre dışı. Canlıya geçiş ve yasal kontrollerden sonra yönetici tarafından açılabilir.</p></div>';
    }
    if (get_option('adim_returns_db_version') !== '1' || get_option('adim_restock_db_version') !== '1') {
        echo '<p>İade ve stok hareketi tabloları henüz kurulmadı.</p></div>';
        return;
    }

    $returns = adim_return_table();
    $movements = adim_restock_table();
    $pending = $wpdb->get_results(
        "SELECT r.request_key,r.order_id,r.item_id,r.quantity,r.updated_at,
                m.status AS movement_status
           FROM {$returns} r
           LEFT JOIN {$movements} m ON m.return_key=r.request_key
          WHERE r.state='accepted'
          ORDER BY r.updated_at DESC LIMIT 50"
    );

    if (!$pending) {
        echo '<p>İncelemesi kabul edilmiş iade bulunamadı.</p></div>';
        return;
    }

    foreach ($pending as $entry) {
        $status = $entry->movement_status ?: 'not_started';
        echo '<section style="background:#fff;border:1px solid #ddd;padding:16px;margin:14px 0;border-radius:7px">';
        echo '<h2>Sipariş #' . esc_html((string)$entry->order_id)
            . ' — Kalem #' . esc_html((string)$entry->item_id) . '</h2>';
        echo '<p>Adet: <strong>' . esc_html((string)$entry->quantity)
            . '</strong> · Stok hareketi: <strong>' . esc_html($status) . '</strong></p>';

        if ($status === 'completed') {
            echo '<p>Bu iade için daha önce stok hareketi kaydedildi. Tekrar eklenemez.</p>';
        } elseif ($status !== 'not_started') {
            echo '<p><strong>Elle inceleme gerekli.</strong> Stok artışı gerçekleşmiş olabilir; ikinci işlem başlatmayın.</p>';
        } elseif (!adim_restock_enabled()) {
            echo '<p>Stok artırma kapalı olduğundan işlem yapılamıyor.</p>';
        } else {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="adim_restock_approved">';
            echo '<input type="hidden" name="adim_return_key" value="' . esc_attr($entry->request_key) . '">';
            wp_nonce_field('adim_restock_approved');
            echo '<p><label><input type="checkbox" name="adim_confirm_condition" value="1" required> Ürünün fiziksel durumunu kontrol ettim; yeniden satışa uygundur.</label></p>';
            echo '<p><label><input type="checkbox" name="adim_confirm_stock_count" value="1" required> Bu numaranın mevcut stok miktarını ve geçmiş hareketlerini kontrol ettim.</label></p>';
            echo '<p><label><strong>İnceleme ve stok sayımı notu (15–1000 karakter)</strong><br>';
            echo '<textarea name="adim_restock_reason" minlength="15" maxlength="1000" required rows="3" style="width:100%"></textarea></label></p>';
            echo '<p><button class="button button-primary" type="submit">Tek Seferlik Stoğa Ekle</button></p></form>';
        }
        echo '</section>';
    }
    echo '</div>';
}
