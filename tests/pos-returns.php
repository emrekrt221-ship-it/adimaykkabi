<?php
/** End-to-end safe return intake and manager inspection on actual WooCommerce. */
defined('ABSPATH') || exit;
function return_check(bool $ok, string $msg): void {
    if (!$ok) { throw new RuntimeException('FAIL RETURNS: ' . $msg); }
    echo 'PASS RETURNS: ' . $msg . PHP_EOL;
}
$admin = get_users(['role'=>'administrator','number'=>1])[0] ?? null;
return_check($admin instanceof WP_User, 'administrator exists');
$cashierId = wp_create_user('ci_returns_' . strtolower(wp_generate_password(8,false,false)),
    wp_generate_password(), 'ci-returns@example.test');
return_check(!is_wp_error($cashierId), 'test cashier created');
$user = new WP_User((int) $cashierId);
$user->set_role('adim_cashier');
wp_set_current_user((int)$cashierId);
adim_pos_install();
adim_return_install();
return_check(get_option('adim_returns_db_version')==='1','returns table installed');
define('ADIM_ENABLE_POS', true);

$p = new WC_Product_Simple();
$p->set_name('Legal synthetic sample shoe');
$p->set_status('publish');
$p->set_sku('ADIM-RETURN-' . strtoupper(wp_generate_password(9,false,false)));
$p->set_regular_price('950');
$p->set_manage_stock(true);
$p->set_stock_quantity(6);
$p->set_backorders('no');
$p->save();
update_post_meta($p->get_id(), '_adim_sale_approved', 'yes');
$order=adim_pos_record_sale($p->get_sku(), 2, wp_generate_uuid4(), (int)$cashierId);
return_check($order instanceof WC_Order,'physical sale for return fixture');
return_check((int)wc_get_product($p->get_id())->get_stock_quantity()===4,'starting stock after sale is 4');
$item = current($order->get_items('line_item'));
return_check($item instanceof WC_Order_Item_Product,'Woo item exists');

$key=wp_generate_uuid4();
$disabled=adim_return_intake($order->get_id(), $item->get_id(), 1, $key, (int)$cashierId);
return_check(is_wp_error($disabled) && $disabled->get_error_code()==='adim_returns_disabled','intake default OFF');
define('ADIM_ENABLE_RETURN_INTAKE', true);
$entry=adim_return_intake($order->get_id(), $item->get_id(), 1, $key, (int)$cashierId);
if(is_wp_error($entry)){throw new RuntimeException('Intake error: '.$entry->get_error_message());}
return_check($entry->state==='quarantine','first returned item quarantined');
$replayed=adim_return_intake($order->get_id(), $item->get_id(), 1, $key, (int)$cashierId);
return_check(!is_wp_error($replayed) && $replayed->request_key===$key,'idempotent retry');
$other=adim_return_intake($order->get_id(), $item->get_id(), 1, wp_generate_uuid4(), (int)$cashierId);
return_check(!is_wp_error($other),'partial return of second sold item');
$over=adim_return_intake($order->get_id(), $item->get_id(), 1, wp_generate_uuid4(), (int)$cashierId);
return_check(is_wp_error($over) && $over->get_error_code()==='adim_return_over_quantity','cannot accept more than sold quantity');
return_check((int)wc_get_product($p->get_id())->get_stock_quantity()===4,'intake never increases stock');
$wrong=adim_return_inspect($key,'accepted','The shoes were inspected and verified intact', (int)$cashierId);
return_check(is_wp_error($wrong)&&$wrong->get_error_code()==='adim_return_review_forbidden','cashier cannot approve inspection');

wp_set_current_user((int)$admin->ID);
$bad=adim_return_inspect($key,'accepted','short',(int)$admin->ID);
return_check(is_wp_error($bad),'manager note required');
$ok=adim_return_inspect($key,'accepted','The returned pair is intact after manual inspection',(int)$admin->ID);
return_check($ok===true,'manager inspection recorded');
global $wpdb;
$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.adim_return_table().' WHERE request_key=%s',$key));
return_check($row->state==='accepted' && (int)$row->reviewed_by===(int)$admin->ID,'review row manager decision retained');
return_check((int)wc_get_product($p->get_id())->get_stock_quantity()===4,'inspection does NOT automatically restock');
return_check($order->get_status()==='completed','inspection does NOT cancel order');
return_check($order->get_total()>0,'inspection does NOT automatically refund payment');
$repeat=adim_return_inspect($key,'rejected','Someone attempted to change the already approved review',(int)$admin->ID);
return_check(is_wp_error($repeat)&&$repeat->get_error_code()==='adim_return_not_pending','already reviewed immutable');

$notes=wc_get_order_notes(['order_id'=>$order->get_id(),'limit'=>30]);
return_check((bool)array_filter($notes,static fn($x)=>str_contains($x->content,'ADIM iade ürün kontrolü')),'review audit note stored');
echo 'ADIM WooCommerce return intake/inspection tests passed.' . PHP_EOL;
