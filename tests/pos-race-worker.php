<?php
/** One concurrent cashier transaction in an independent WP-CLI process. */
defined('ABSPATH') || exit;
$sku = (string) getenv('ADIM_RACE_SKU');
$key = (string) getenv('ADIM_RACE_KEY');
$gate = (string) getenv('ADIM_RACE_GATE');
if ($sku === '' || $key === '' || $gate === '') {
    throw new RuntimeException('Race test environment missing');
}
$users = get_users(['role' => 'administrator', 'number' => 1]);
if (!$users) {
    throw new RuntimeException('CI operator absent');
}
wp_set_current_user((int) $users[0]->ID);
add_filter('pre_wp_mail', '__return_true');
define('ADIM_ENABLE_POS', true);
$ready = microtime(true) + 12;
while (!file_exists($gate) && microtime(true) < $ready) {
    usleep(10000);
}
if (!file_exists($gate)) {
    throw new RuntimeException('Timed out waiting for concurrency gate');
}
$result = adim_pos_record_sale($sku, 1, $key, get_current_user_id());
if (is_wp_error($result)) {
    echo 'ERR:' . $result->get_error_code() . PHP_EOL;
    exit(2);
}
if (!($result instanceof WC_Order)) {
    throw new RuntimeException('Unexpected POS result type');
}
echo 'OK:' . $result->get_id() . PHP_EOL;
