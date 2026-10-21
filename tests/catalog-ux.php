<?php
/** Real WooCommerce catalog-size filter and accessibility smoke checks. */
defined('ABSPATH') || exit;
function catalog_assert(bool $pass, string $label): void
{
    if (!$pass) {
        throw new RuntimeException('FAIL ADIM CATALOG: ' . $label);
    }
    echo 'PASS ADIM CATALOG: ' . $label . PHP_EOL;
}
if (!taxonomy_exists('pa_beden')) {
    register_taxonomy('pa_beden', 'product', [
        'public' => true, 'show_ui' => true, 'hierarchical' => false,
        'label' => 'Beden',
    ]);
}
$size42 = term_exists('42', 'pa_beden');
if (!$size42) {
    $size42 = wp_insert_term('42', 'pa_beden');
}
$size43 = term_exists('43', 'pa_beden');
if (!$size43) {
    $size43 = wp_insert_term('43', 'pa_beden');
}
catalog_assert(!is_wp_error($size42) && !is_wp_error($size43), 'size taxonomy has two categories');

function catalog_fixture(string $label, string $size, bool $reviewed): int
{
    $p = new WC_Product_Simple();
    $p->set_name('ADIM catalog test ' . $label);
    $p->set_status('publish');
    $p->set_regular_price('1250');
    $p->set_stock_status('instock');
    $p->save();
    wp_set_object_terms($p->get_id(), [$size], 'pa_beden');
    if ($reviewed) {
        update_post_meta($p->get_id(), '_adim_sale_approved', 'yes');
    }
    return $p->get_id();
}
$a = catalog_fixture('size 42 approved', '42', true);
$b = catalog_fixture('size 43 approved', '43', true);
$c = catalog_fixture('size 42 blocked', '42', false);

$_GET['adim_beden'] = '42';
catalog_assert(adim_catalog_requested_size() === '42', 'valid requested size recognized');
$query = new WP_Query();
$query->set('post_type', 'product');
$query->set('posts_per_page', 40);
do_action('woocommerce_product_query', $query);
$tax = $query->get('tax_query');
catalog_assert(is_array($tax) && count($tax) >= 1, 'size taxonomy added to native Woo query');
$ids = array_map(static fn($p) => (int)$p->ID, $query->get_posts());
catalog_assert(in_array($a, $ids, true), 'selected approved 42 is visible');
catalog_assert(!in_array($b, $ids, true), '43 does not appear under 42 filter');
catalog_assert(!in_array($c, $ids, true), 'unapproved 42 does not leak into size-filter results');

$_GET['adim_beden'] = '<script>alert(1)</script>';
catalog_assert(adim_catalog_requested_size() === '', 'unrecognized injected size ignored');

$_GET['adim_beden'] = '42';
ob_start();
do_action('woocommerce_before_shop_loop');
$html = ob_get_clean();
catalog_assert(str_contains($html, 'Numaraya göre filtrele')
    && str_contains($html, '<option value="42" selected')
    && str_contains($html, 'Filtrele')
    && str_contains($html, 'Temizle'),
    'accessible selected-size filter form renders');

ob_start();
do_action('woocommerce_single_product_summary');
$single = ob_get_clean();
catalog_assert(str_contains($single, 'adim-size-help')
    && str_contains($single, 'Numaramı nasıl seçmeliyim?'),
    'neutral fitting guidance on product detail');

unset($_GET['adim_beden']);
echo 'ADIM real WooCommerce catalog UX smoke passed.' . PHP_EOL;
