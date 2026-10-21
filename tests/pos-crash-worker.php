<?php
/** Kill a WP CLI cashier execution at a known persisted checkpoint. Test-only. */
defined('ABSPATH') || exit;
$stage = (string) getenv('ADIM_CRASH_STAGE');
$key = (string) getenv('ADIM_CRASH_KEY');
$sku = (string) getenv('ADIM_CRASH_SKU');
if (!$stage || !$key || !$sku) {
    throw new RuntimeException('Missing crash simulation settings');
}
$admins = get_users(['role' => 'administrator', 'number' => 1]);
if (!$admins) {
    throw new RuntimeException('CI admin user missing');
}
wp_set_current_user((int) $admins[0]->ID);
define('ADIM_ENABLE_POS', true);
define('ADIM_POS_TEST_MODE', true);
add_filter('pre_wp_mail', '__return_true');
add_action('adim_pos_test_checkpoint',
    static function ($current, $request_key, $order_id) use ($stage, $key): void {
        if ($stage === $current && $request_key === $key) {
            echo 'ADIM_FAULT_INJECTED stage=' . $stage . ' order_id=' . (int) $order_id . PHP_EOL;
            fflush(STDOUT);
            // Abrupt process exit without running the sale's catch block.
            exit(86);
        }
    }, 1, 3
);
$order = adim_pos_record_sale($sku, 1, $key, get_current_user_id());
throw new RuntimeException('Fault point was not reached: ' . $stage . ' result=' .
    (is_wp_error($order) ? $order->get_error_message() : gettype($order)));
