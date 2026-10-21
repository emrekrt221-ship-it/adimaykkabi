<?php
/**
 * Dedicated admin views for return intake and manager inspection.
 * The forms never trigger refunds or salable-stock restoration.
 */
defined('ABSPATH') || exit;

add_action('admin_menu', static function (): void {
    add_menu_page('ADIM İade Teslim Alma', 'ADIM İade Teslim', 'adim_pos_sell',
        'adim-return-intake', 'adim_return_render_intake', 'dashicons-backup', 58);
    add_submenu_page('woocommerce', 'ADIM İade Ürün Kontrolü', 'İade Ürün Kontrolü',
        'manage_woocommerce', 'adim-return-inspection', 'adim_return_render_inspection');
});

add_action('admin_post_adim_return_intake', static function (): void {
    if (!adim_pos_can_sell()) {
        wp_die('İade teslim yetkisi gerekli.', '', ['response' => 403]);
    }
    check_admin_referer('adim_return_intake');
    $orderId = absint($_POST['adim_order_id'] ?? 0);
    $itemId = absint($_POST['adim_item_id'] ?? 0);
    $qty = absint($_POST['adim_quantity'] ?? 0);
    $key = isset($_POST['adim_return_key']) && is_string($_POST['adim_return_key'])
        ? sanitize_text_field(wp_unslash($_POST['adim_return_key'])) : '';
    $result = adim_return_intake($orderId, $itemId, $qty, $key, get_current_user_id());
    wp_safe_redirect(add_query_arg([
        'page' => 'adim-return-intake',
        'adim_status' => is_wp_error($result) ? sanitize_key($result->get_error_code()) : 'quarantine',
    ], admin_url('admin.php')));
    exit;
});

add_action('admin_post_adim_return_inspect', static function (): void {
    if (!adim_pos_can_reconcile()) {
        wp_die('Yönetici inceleme yetkisi gerekli.', '', ['response' => 403]);
    }
    check_admin_referer('adim_return_inspect');
    $key = isset($_POST['adim_return_key']) && is_string($_POST['adim_return_key'])
        ? sanitize_text_field(wp_unslash($_POST['adim_return_key'])) : '';
    $decision = isset($_POST['adim_decision']) && is_string($_POST['adim_decision'])
        ? sanitize_key(wp_unslash($_POST['adim_decision'])) : '';
    $note = isset($_POST['adim_note']) && is_string($_POST['adim_note'])
        ? sanitize_textarea_field(wp_unslash($_POST['adim_note'])) : '';
    $result = adim_return_inspect($key, $decision, $note, get_current_user_id());
    wp_safe_redirect(add_query_arg([
        'page' => 'adim-return-inspection',
        'adim_status' => is_wp_error($result) ? sanitize_key($result->get_error_code()) : 'inspected',
    ], admin_url('admin.php')));
    exit;
});

function adim_return_render_intake(): void
{
    if (!adim_pos_can_sell()) {
        wp_die('İade teslim yetkisi gerekli.', '', ['response' => 403]);
    }
    echo '<div class="wrap" style="max-width:850px"><h1>ADIM AYAKKABI — İade Teslim Alma</h1>';
    echo '<p><strong>Ürün inceleme/karantinaya alınır.</strong> Bu işlem kart/nakit para iadesi yapmaz ve ürünü satış stoğuna geri eklemez.</p>';
    if (!defined('ADIM_ENABLE_RETURN_INTAKE') || ADIM_ENABLE_RETURN_INTAKE !== true) {
        echo '<div class="notice notice-warning"><p>İade teslim modülü şu anda kapalıdır.</p></div></div>';
        return;
    }
    if (isset($_GET['adim_status'])) {
        echo '<div class="notice notice-info"><p>İşlem durumu: '
            . esc_html(sanitize_key($_GET['adim_status'])) . '</p></div>';
    }
    echo '<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:8px">';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="adim_return_intake">';
    echo '<input type="hidden" name="adim_return_key" value="' . esc_attr(wp_generate_uuid4()) . '">';
    wp_nonce_field('adim_return_intake');
    echo '<p><label for="adim-return-order">WooCommerce POS sipariş numarası</label><br>';
    echo '<input type="number" min="1" required name="adim_order_id" id="adim-return-order" style="width:100%;max-width:440px"></p>';
    echo '<p><label for="adim-return-item">Sipariş kalemi numarası (order item ID)</label><br>';
    echo '<input type="number" min="1" required name="adim_item_id" id="adim-return-item" style="width:100%;max-width:440px"></p>';
    echo '<p><label for="adim-return-qty">Teslim alınan adet</label><br>';
    echo '<input type="number" min="1" max="20" value="1" required name="adim_quantity" id="adim-return-qty"></p>';
    echo '<button class="button button-primary" type="submit">Karantina Kaydı Oluştur</button>';
    echo '</form></div></div>';
}

function adim_return_render_inspection(): void
{
    if (!adim_pos_can_reconcile()) {
        wp_die('Yönetici inceleme yetkisi gerekli.', '', ['response' => 403]);
    }
    echo '<div class="wrap" style="max-width:950px"><h1>ADIM — İade Ürün Kontrolü</h1>';
    echo '<p>Onay veya ret yalnız inceleme kaydıdır; para iadesi ve stok artışı ayrı yetkili süreçlerdir.</p>';
    if (isset($_GET['adim_status'])) {
        echo '<div class="notice notice-info"><p>İşlem durumu: '
            . esc_html(sanitize_key($_GET['adim_status'])) . '</p></div>';
    }
    if (get_option('adim_returns_db_version') !== '1') {
        echo '<p>İade modülü veritabanı henüz kurulmamış.</p></div>';
        return;
    }
    global $wpdb;
    $table = adim_return_table();
    $rows = $wpdb->get_results("SELECT request_key, order_id, item_id, quantity, created_at
        FROM {$table} WHERE state='quarantine' ORDER BY created_at ASC LIMIT 50");
    if (!$rows) {
        echo '<p>İnceleme bekleyen ürün bulunmuyor.</p></div>';
        return;
    }
    foreach ($rows as $r) {
        echo '<section style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:18px;margin:14px 0">';
        echo '<h2>Sipariş #' . esc_html((string) $r->order_id) . ' — Kalem #' . esc_html((string) $r->item_id) . '</h2>';
        echo '<p>Adet: ' . esc_html((string)$r->quantity) . '. Teslim zamanı (UTC): '
            . esc_html($r->created_at) . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="adim_return_inspect">';
        echo '<input type="hidden" name="adim_return_key" value="' . esc_attr($r->request_key) . '">';
        wp_nonce_field('adim_return_inspect');
        echo '<p><label>Kontrol sonucu <select required name="adim_decision"><option value="">Seçiniz</option>'
            . '<option value="accepted">Ürün kontrolü kabul</option><option value="rejected">Ürün kontrolü ret</option></select></label></p>';
        echo '<p><label>İnceleme kanıtları / açıklama (en az 15 karakter)<br><textarea name="adim_note"'
            . ' rows="3" minlength="15" maxlength="1000" required style="width:100%"></textarea></label></p>';
        echo '<button class="button button-primary" type="submit">Kontrol Sonucunu Kaydet (Stok ve Ödeme Değişmez)</button>';
        echo '</form></section>';
    }
    echo '</div>';
}
