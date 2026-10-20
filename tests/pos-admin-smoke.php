<?php
/** Staff cashier screen smoke test against real WordPress/WooCommerce. */
defined('ABSPATH') || exit;
if (!function_exists('adim_pos_render_screen')) {
    throw new RuntimeException('POS admin screen not loaded.');
}
$admins = get_users(['role' => 'administrator', 'number' => 1]);
if (!$admins) {
    throw new RuntimeException('No admin user for cashier screen test.');
}
wp_set_current_user((int) $admins[0]->ID);
$_GET = ['page' => 'adim-pos'];
ob_start();
adim_pos_render_screen();
$closedHtml = ob_get_clean();
if (!str_contains($closedHtml, 'Mağaza satışları kapalı.')
    || str_contains($closedHtml, 'type="submit">Satışı Kaydet')) {
    throw new RuntimeException('Closed cashier page did not fail closed.');
}
echo "PASS POS: cashier page remains inactive by default.\n";

$p = new WC_Product_Simple();
$p->set_name('ADIM cashier UI sample');
$p->set_status('publish');
$p->set_sku('ADIM-SCREEN-' . wp_generate_password(9, false, false));
$p->set_regular_price('1500');
$p->set_manage_stock(true);
$p->set_stock_quantity(4);
$p->set_backorders('no');
$p->save();
update_post_meta($p->get_id(), '_adim_sale_approved', 'yes');

define('ADIM_ENABLE_POS', true);
$_GET = ['page' => 'adim-pos', 'adim_sku' => $p->get_sku()];
ob_start();
adim_pos_render_screen();
$openHtml = ob_get_clean();
foreach (['Mağaza Satışı', $p->get_sku(), 'Stok:', 'name="adim_request_key"',
    'name="_wpnonce"', 'name="adim_confirm_paid"', 'type="submit">Satışı Kaydet'] as $part) {
    if (!str_contains($openHtml, $part)) {
        throw new RuntimeException("Cashier screen missing expected field: {$part}");
    }
}
echo "PASS POS: reviewed SKU cashier form, nonce, payment confirmation rendered.\n";

$unapproved = new WC_Product_Simple();
$unapproved->set_name('ADIM blocked cashier sample');
$unapproved->set_status('publish');
$unapproved->set_sku('ADIM-SCREEN-NO-' . wp_generate_password(8, false, false));
$unapproved->set_regular_price('1500');
$unapproved->set_manage_stock(true);
$unapproved->set_stock_quantity(4);
$unapproved->set_backorders('no');
$unapproved->save();

$_GET = ['page' => 'adim-pos', 'adim_sku' => $unapproved->get_sku()];
ob_start();
adim_pos_render_screen();
$blockedHtml = ob_get_clean();
if (str_contains($blockedHtml, 'type="submit">Satışı Kaydet')
    || !str_contains($blockedHtml, 'satışı için gerekli stok')) {
    throw new RuntimeException('Unreviewed product showed an actionable cashier form.');
}
echo "PASS POS: unreviewed product cannot use cashier form.\n";
