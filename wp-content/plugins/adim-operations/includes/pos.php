<?php
/**
 * ADIM in-store checkout foundation.
 *
 * Uses WooCommerce orders, atomic core stock reservation and stock reduction.
 * No external card collection or payment-provider integration.
 */
defined('ABSPATH') || exit;

/** Dedicated POS switch. Website checkout and physical sales are independent. */
function adim_pos_enabled(): bool
{
    return defined('ADIM_ENABLE_POS') && ADIM_ENABLE_POS === true;
}

/** Explicit migration for idempotent physical transactions (not product stock). */
function adim_pos_install(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = $wpdb->prefix . 'adim_pos_requests';
    $charset = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$table} (
request_key varchar(36) NOT NULL,
order_id bigint(20) unsigned NOT NULL DEFAULT 0,
status varchar(20) NOT NULL DEFAULT 'processing',
created_at datetime NOT NULL,
updated_at datetime NOT NULL,
PRIMARY KEY  (request_key),
KEY order_id (order_id)
) {$charset};");
    update_option('adim_pos_db_version', '1', false);
}

function adim_pos_ledger_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'adim_pos_requests';
}

/**
 * Test-only crash checkpoint.
 *
 * Available only in explicit WP-CLI runs with ADIM_POS_TEST_MODE=true.
 * Never invoked during browser/production sales.
 */
function adim_pos_test_checkpoint(string $step, string $request_key, int $order_id = 0): void
{
    if (defined('WP_CLI') && WP_CLI
        && defined('ADIM_POS_TEST_MODE') && ADIM_POS_TEST_MODE === true) {
        do_action('adim_pos_test_checkpoint', $step, $request_key, $order_id);
    }
}

/**
 * Physical sale with request-key idempotency and WooCommerce order-backed stock.
 *
 * The cashier confirms payment occurred outside the website, not via an online
 * gateway. Business receipts and statutory invoices are handled separately.
 *
 * @return WC_Order|WP_Error
 */
function adim_pos_record_sale(string $sku, int $quantity, string $request_key, int $operator_id, string $payment_method = 'cash')
{
    global $wpdb;

    if (!adim_pos_enabled()) {
        return new WP_Error('adim_pos_disabled', 'Mağaza satış işlemleri etkin değil.');
    }
    if (!function_exists('wc_create_order') || !function_exists('wc_reserve_stock_for_order')) {
        return new WP_Error('adim_pos_unavailable', 'WooCommerce stok rezervasyonu kullanılamıyor.');
    }
    if (!adim_pos_can_sell() || $operator_id !== get_current_user_id()) {
        return new WP_Error('adim_pos_forbidden', 'Mağaza satış yetkiniz yok.');
    }
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $request_key)) {
        return new WP_Error('adim_pos_bad_key', 'Geçersiz işlem kimliği.');
    }
    if ($quantity < 1 || $quantity > 20 || !in_array($payment_method, ['cash', 'external_terminal'], true)) {
        return new WP_Error('adim_pos_bad_input', 'Adet veya tahsilat yöntemi geçersiz.');
    }
    $sku = trim($sku);
    if ($sku === '' || strlen($sku) > 100) {
        return new WP_Error('adim_pos_bad_sku', 'Ürün kodu geçersiz.');
    }
    if (get_option('adim_pos_db_version') !== '1'
        || get_option('woocommerce_manage_stock', 'yes') !== 'yes'
        || !apply_filters('woocommerce_hold_stock_for_checkout', true)) {
        return new WP_Error('adim_pos_not_ready', 'Stok rezervasyonu veya işlem kaydı hazır değil.');
    }

    // MySQL unique request_key is an idempotency barrier across PHP processes.
    $table = adim_pos_ledger_table();
    $now = current_time('mysql', true);
    $created = $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$table} (request_key, order_id, status, created_at, updated_at) VALUES (%s, 0, 'processing', %s, %s)",
        $request_key, $now, $now
    ));
    if ($created !== 1) {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT order_id, status FROM {$table} WHERE request_key = %s", $request_key
        ));
        if ($existing && $existing->status === 'completed' && (int) $existing->order_id > 0) {
            $recorded = wc_get_order((int) $existing->order_id);
            if ($recorded instanceof WC_Order && $recorded->get_meta('_adim_pos_request_key') === $request_key) {
                return $recorded;
            }
        }
        return new WP_Error('adim_pos_duplicate', 'Bu işlem daha önce kaydedildi veya inceleme bekliyor.');
    }

    // Abrupt process termination at this point must leave the idempotency key
    // claimed, so a retry cannot create a second sale without reconciliation.
    adim_pos_test_checkpoint('ledger_claimed', $request_key);
    $order = null;
    $stock_reduced = false;
    $reduce_attempted = false;
    try {
        $product_id = (int) wc_get_product_id_by_sku($sku);
        $product = $product_id ? wc_get_product($product_id) : false;
        if (!($product instanceof WC_Product)
            || !adim_ops_is_reviewed($product)
            || $product->get_status() !== 'publish'
            || (float) $product->get_price() <= 0
            || !$product->managing_stock()
            || $product->backorders_allowed()
            || (int) $product->get_stock_managed_by_id() !== (int) $product->get_id()
            || (int) $product->get_stock_quantity() < $quantity
        ) {
            throw new RuntimeException('Bu ürün veya numara satışa uygun değil ya da stok yetersiz.');
        }
        if ($product instanceof WC_Product_Variable) {
            throw new RuntimeException('Üst model değil, numara/renk SKU kodu seçilmeli.');
        }

        $order = wc_create_order(['status' => 'pending', 'created_via' => 'adim-pos']);
        if (is_wp_error($order) || !($order instanceof WC_Order)) {
            throw new RuntimeException('WooCommerce satış siparişini oluşturamadı.');
        }
        $order->add_product($product, $quantity);
        $order->set_created_via('adim-pos');
        $order->set_payment_method('adim_pos_' . $payment_method);
        $order->set_payment_method_title($payment_method === 'cash'
            ? 'Mağazada nakit tahsil edildi'
            : 'Harici kart terminalinden tahsil edildi');
        $order->update_meta_data('_adim_channel', 'in_store');
        $order->update_meta_data('_adim_pos_request_key', $request_key);
        $order->update_meta_data('_adim_pos_operator_id', $operator_id);
        $order->calculate_totals();
        $order->save();
        adim_pos_test_checkpoint('order_saved_before_link', $request_key, (int) $order->get_id());

        $linked = $wpdb->update($table, ['order_id' => $order->get_id(), 'updated_at' => current_time('mysql', true)],
            ['request_key' => $request_key, 'status' => 'processing'], ['%d', '%s'], ['%s', '%s']);
        if ($linked !== 1) {
            throw new RuntimeException('POS işlem anahtarı siparişe bağlanamadı.');
        }
        adim_pos_test_checkpoint('order_linked', $request_key, (int) $order->get_id());

        // WooCommerce ReserveStock performs InnoDB row locking and accounts for
        // stock held by online checkout; do NOT modify the _stock meta directly.
        wc_reserve_stock_for_order($order);
        adim_pos_test_checkpoint('stock_reserved', $request_key, (int) $order->get_id());

        $reduce_attempted = true;
        wc_reduce_stock_levels($order->get_id());
        // Read the persisted order-item metadata, not a possibly stale in-memory item.
        $fresh_order = wc_get_order($order->get_id());
        $line_items = $fresh_order ? $fresh_order->get_items('line_item') : [];
        if (count($line_items) !== 1) {
            throw new RuntimeException('Sipariş satırı doğrulanamadı; kayıt inceleme bekliyor.');
        }
        foreach ($line_items as $item) {
            $reduced = (int) wc_get_order_item_meta($item->get_id(), '_reduced_stock', true);
            if ($reduced !== $quantity) {
                throw new RuntimeException('Stok düşümü doğrulanamadı; kayıt inceleme bekliyor.');
            }
        }
        $stock_reduced = true;
        adim_pos_test_checkpoint('stock_reduced', $request_key, (int) $order->get_id());
        $order->update_status('completed', 'ADIM fiziki mağaza satışı; harici tahsilat personel tarafından teyit edildi.');
        adim_pos_test_checkpoint('order_completed', $request_key, (int) $order->get_id());
        $saved = $wpdb->update($table,
            ['order_id' => $order->get_id(), 'status' => 'completed', 'updated_at' => current_time('mysql', true)],
            ['request_key' => $request_key, 'status' => 'processing'], ['%d', '%s', '%s'], ['%s', '%s']);
        if ($saved !== 1) {
            throw new RuntimeException('İşlem kaydı sonlandırılamadı; sipariş elle incelenmeli.');
        }
        return $order;
    } catch (Throwable $e) {
        // Never delete an order after an attempted stock reduction. Even if
        // order-item metadata is stale or missing, stock may have changed.
        // Keep the order and request key for reconciliation, never silently retry.
        if ($order instanceof WC_Order) {
            $items = $order->get_items('line_item');
            foreach ($items as $item) {
                if ((int) $item->get_meta('_reduced_stock', true) > 0) {
                    $stock_reduced = true;
                    break;
                }
            }
            if ($stock_reduced || $reduce_attempted) {
                $order->add_order_note('ADIM: fiziksel satış başarısız sonuçlandı; stok düşülmüş olabilir. Elle inceleyin.');
            } else {
                wc_release_stock_for_order($order);
                $order->delete(true);
            }
        }
        // Never overwrite a manager's already-recorded review of a stale
        // request if a delayed worker unexpectedly resumes after 15 minutes.
        $wpdb->update($table, [
            'status' => ($stock_reduced || $reduce_attempted) ? 'needs_review' : 'failed',
            'updated_at' => current_time('mysql', true),
        ], ['request_key' => $request_key, 'status' => 'processing'], ['%s', '%s'], ['%s', '%s']);
        return new WP_Error('adim_pos_failed', $e->getMessage());
    }
}
