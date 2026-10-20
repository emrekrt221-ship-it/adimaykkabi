<?php
/** ADIM store cashier admin UI. All POST actions require a WooCommerce manager. */
defined('ABSPATH') || exit;

add_action('admin_menu', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }
    add_menu_page(
        __('ADIM Mağaza Satışı', 'adim-operations'),
        __('Mağaza Satışı', 'adim-operations'),
        'adim_pos_sell',
        'adim-pos',
        'adim_pos_render_screen',
        'dashicons-store',
        57
    );
});

add_action('admin_post_adim_pos_sale', 'adim_pos_handle_admin_sale');

function adim_pos_handle_admin_sale(): void
{
    if (!adim_pos_can_sell()) {
        wp_die(esc_html__('Bu işlem için yetkiniz yok.', 'adim-operations'), '', ['response' => 403]);
    }
    check_admin_referer('adim_pos_sale');

    if (empty($_POST['adim_confirm_paid'])) {
        wp_safe_redirect(add_query_arg(['page' => 'adim-pos', 'adim_status' => 'confirm'], admin_url('admin.php')));
        exit;
    }
    $sku = isset($_POST['adim_sku']) && is_string($_POST['adim_sku'])
        ? sanitize_text_field(wp_unslash($_POST['adim_sku'])) : '';
    $key = isset($_POST['adim_request_key']) && is_string($_POST['adim_request_key'])
        ? sanitize_text_field(wp_unslash($_POST['adim_request_key'])) : '';
    $method = isset($_POST['adim_payment_method']) && is_string($_POST['adim_payment_method'])
        ? sanitize_key(wp_unslash($_POST['adim_payment_method'])) : '';
    $quantity = isset($_POST['adim_quantity']) ? absint($_POST['adim_quantity']) : 0;

    $result = adim_pos_record_sale($sku, $quantity, $key, get_current_user_id(), $method);
    if (is_wp_error($result)) {
        // Show only the stable error code in GET params, not exception content.
        wp_safe_redirect(add_query_arg([
            'page' => 'adim-pos',
            'adim_status' => 'error',
            'adim_error' => sanitize_key($result->get_error_code()),
        ], admin_url('admin.php')));
        exit;
    }
    wp_safe_redirect(add_query_arg([
        'page' => 'adim-pos',
        'adim_status' => 'success',
        'adim_order' => (int) $result->get_id(),
    ], admin_url('admin.php')));
    exit;
}

function adim_pos_render_screen(): void
{
    if (!adim_pos_can_sell()) {
        wp_die(esc_html__('Mağaza satış yetkiniz yok.', 'adim-operations'));
    }

    $sku = isset($_GET['adim_sku']) && is_string($_GET['adim_sku'])
        ? sanitize_text_field(wp_unslash($_GET['adim_sku'])) : '';
    $status = isset($_GET['adim_status']) ? sanitize_key($_GET['adim_status']) : '';
    $error = isset($_GET['adim_error']) ? sanitize_key($_GET['adim_error']) : '';
    $id = $sku !== '' && function_exists('wc_get_product_id_by_sku')
        ? (int) wc_get_product_id_by_sku($sku) : 0;
    $product = $id ? wc_get_product($id) : false;
    $eligible = $product instanceof WC_Product
        && adim_ops_is_reviewed($product)
        && $product->get_status() === 'publish'
        && $product->managing_stock()
        && !$product->backorders_allowed()
        && (int) $product->get_stock_managed_by_id() === (int) $product->get_id()
        && (int) $product->get_stock_quantity() > 0;
    $ready = adim_pos_enabled();

    echo '<div class="wrap adim-pos-wrap">';
    echo '<style>
    .adim-pos-wrap{max-width:960px;margin:20px auto}.adim-pos-header{background:#151515;color:#fff;border-radius:10px;padding:22px 26px}.adim-pos-header h1{color:#fff;margin:0}.adim-pos-header p{color:#eee;margin:6px 0 0}.adim-pos-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:18px;margin-top:20px}.adim-pos-panel{background:#fff;border:1px solid #ddd;border-radius:10px;padding:22px}.adim-pos-panel h2{margin-top:0}.adim-pos-input{width:100%;max-width:100%;margin:8px 0 14px;min-height:42px}.adim-pos-primary{border:0;border-radius:6px;background:#151515;color:#fff;padding:12px 20px;font-weight:700;cursor:pointer}.adim-pos-primary:hover{background:#d86c00;color:#111}.adim-pos-stock{font-size:1.3rem;font-weight:700}.adim-pos-alert{background:#fff7e7;border-left:4px solid #f57c00;padding:12px 14px;margin:14px 0}.adim-pos-preview{display:flex;gap:14px;align-items:center}.adim-pos-preview img{width:84px;height:84px;object-fit:contain;background:#f5f5f5}.adim-pos-label{font-weight:600;display:block;margin-top:10px}.adim-pos-history{margin-top:18px}.adim-pos-history td,.adim-pos-history th{padding:10px;text-align:left;border-bottom:1px solid #eee}@media(max-width:720px){.adim-pos-grid{grid-template-columns:1fr}.adim-pos-header,.adim-pos-panel{padding:16px}.adim-pos-wrap{margin:12px}}
    </style>';
    echo '<div class="adim-pos-header"><h1>ADIM AYAKKABI — Mağaza Satışı</h1>';
    echo '<p>Stok, numara ve fiziksel kasa satışlarını WooCommerce üzerinden kaydetme.</p></div>';

    if (!$ready) {
        echo '<div class="adim-pos-alert"><strong>Mağaza satışları kapalı.</strong> Ürün ve işletme uygunluğu, testler ve yönetici onayı tamamlanmadan işlem yapılamaz.</div>';
    }
    if ($status === 'success') {
        $order_id = isset($_GET['adim_order']) ? absint($_GET['adim_order']) : 0;
        $order = $order_id ? wc_get_order($order_id) : false;
        if ($order && $order->get_created_via() === 'adim-pos') {
            echo '<div class="notice notice-success"><p>Mağaza satışı kaydedildi. Sipariş #' . esc_html((string) $order_id) . '. Stok WooCommerce üzerinden güncellendi.</p></div>';
        }
    } elseif ($status === 'error') {
        echo '<div class="notice notice-error"><p>İşlem tamamlanamadı (' . esc_html($error) . '). Aynı işlemi tekrar denemeden önce stok ve işlem geçmişini kontrol edin.</p></div>';
    } elseif ($status === 'confirm') {
        echo '<div class="notice notice-warning"><p>Tahsilat onayı olmadan satış kaydedilemez.</p></div>';
    }

    echo '<div class="adim-pos-grid"><section class="adim-pos-panel"><h2>1. Ürün seçimi</h2>';
    echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '">';
    echo '<input type="hidden" name="page" value="adim-pos">';
    echo '<label class="adim-pos-label" for="adim-sku-search">SKU / barkod</label>';
    echo '<input id="adim-sku-search" class="adim-pos-input" type="search" name="adim_sku" value="' . esc_attr($sku) . '" maxlength="100" placeholder="Örn: ADM-001-SYH-42" required>';
    echo '<button class="button button-primary" type="submit">Ürünü Bul</button></form>';
    if ($sku !== '') {
        if ($product instanceof WC_Product) {
            echo '<div class="adim-pos-preview" style="margin-top:20px">';
            echo wp_kses_post($product->get_image('thumbnail'));
            echo '<div><strong>' . esc_html($product->get_name()) . '</strong><p>SKU: ' . esc_html($product->get_sku()) . '</p><p class="adim-pos-stock">Stok: ' . esc_html((string) $product->get_stock_quantity()) . '</p>';
            echo '<p>' . wp_kses_post(wc_price((float) $product->get_price())) . '</p></div></div>';
            if (!$eligible) {
                echo '<div class="adim-pos-alert">Bu ürünün satışı için gerekli stok veya ürün uygunluk şartları sağlanmıyor.</div>';
            }
        } else {
            echo '<div class="adim-pos-alert">Ürün kodu bulunamadı. Satış kaydı oluşturulamaz.</div>';
        }
    }
    echo '</section><section class="adim-pos-panel"><h2>2. Satışı kaydet</h2>';
    if ($ready && $eligible) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="adim_pos_sale">';
        echo '<input type="hidden" name="adim_sku" value="' . esc_attr($product->get_sku()) . '">';
        echo '<input type="hidden" name="adim_request_key" value="' . esc_attr(wp_generate_uuid4()) . '">';
        wp_nonce_field('adim_pos_sale');
        echo '<label class="adim-pos-label" for="adim-qty">Adet</label>';
        echo '<input id="adim-qty" class="adim-pos-input" type="number" name="adim_quantity" value="1" min="1" max="' . esc_attr((string) min(20, (int) $product->get_stock_quantity())) . '" required>';
        echo '<label class="adim-pos-label" for="adim-payment">Kasada tahsilat</label>';
        echo '<select id="adim-payment" class="adim-pos-input" name="adim_payment_method"><option value="cash">Nakit</option><option value="external_terminal">Harici POS terminali</option></select>';
        echo '<p><label><input type="checkbox" name="adim_confirm_paid" value="1" required> Bu ürünün ödemesi fiziksel kasada alındı ve ürün yasal olarak satışa uygundur.</label></p>';
        echo '<button class="adim-pos-primary" type="submit">Satışı Kaydet</button></form>';
        echo '<p class="description">Bu ekran banka kartı tahsilatı yapmaz; tahsilatı yalnızca kaydeder. Mali belge ve fatura süreçleri ayrıca yürütülmelidir.</p>';
    } else {
        echo '<p>Önce aktif ve uygun bir SKU bulunmalı; kasa satış özelliği yönetici tarafından açılmalıdır.</p>';
    }
    echo '</section></div>';

    global $wpdb;
    if (get_option('adim_pos_db_version') === '1') {
        $table = adim_pos_ledger_table();
        $rows = $wpdb->get_results("SELECT order_id, status, updated_at FROM {$table} ORDER BY updated_at DESC LIMIT 8");
        echo '<section class="adim-pos-panel adim-pos-history"><h2>Son kasa işlemleri</h2><div style="overflow-x:auto"><table class="widefat striped"><thead><tr><th>Sipariş</th><th>Durum</th><th>Zaman (UTC)</th></tr></thead><tbody>';
        if (!$rows) {
            echo '<tr><td colspan="3">Henüz mağaza işlemi yok.</td></tr>';
        } else {
            foreach ($rows as $row) {
                echo '<tr><td>' . esc_html($row->order_id ? '#' . $row->order_id : '—') . '</td><td>' . esc_html($row->status) . '</td><td>' . esc_html($row->updated_at) . '</td></tr>';
            }
        }
        echo '</tbody></table></div></section>';
    }
    echo '</div>';
}
