<?php
/** Create a legally neutral synthetic SKU for CI's simultaneous-sale test. */
defined('ABSPATH') || exit;
$qty = (int) getenv('ADIM_RACE_STOCK');
if ($qty < 1 || $qty > 3) {
    throw new RuntimeException('Invalid synthetic test stock');
}
adim_pos_install();
$p = new WC_Product_Simple();
$p->set_name('ADIM concurrency test shoe');
$p->set_status('publish');
$p->set_sku('ADIM-RACE-' . strtoupper(wp_generate_password(12, false, false)));
$p->set_regular_price('1000');
$p->set_manage_stock(true);
$p->set_stock_quantity($qty);
$p->set_backorders('no');
$p->save();
update_post_meta($p->get_id(), '_adim_sale_approved', 'yes');
echo $p->get_sku() . PHP_EOL;
