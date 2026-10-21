<?php
/** Synthetic authorized test product for POS abrupt-termination scenarios. */
defined('ABSPATH') || exit;
$stage = (string) getenv('ADIM_CRASH_STAGE');
if (!preg_match('/^[a-z_]{5,40}$/', $stage)) {
    throw new RuntimeException('Invalid crash stage');
}
adim_pos_install();
$p = new WC_Product_Simple();
$p->set_name('ADIM crash simulation product ' . $stage);
$p->set_status('publish');
$p->set_sku('ADIM-CRASH-' . strtoupper(wp_generate_password(15, false, false)));
$p->set_regular_price('1000');
$p->set_manage_stock(true);
$p->set_stock_quantity(2);
$p->set_backorders('no');
$p->save();
update_post_meta($p->get_id(), '_adim_sale_approved', 'yes');
echo $p->get_sku() . PHP_EOL;
