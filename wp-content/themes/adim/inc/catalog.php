<?php
/**
 * Lightweight WooCommerce catalog UX.
 * Uses native variation and checkout paths rather than creating another cart.
 */
defined('ABSPATH') || exit;

/** Safe requested size slug; unsupported values are ignored. */
function adim_catalog_requested_size(): string
{
    if (!isset($_GET['adim_beden']) || !is_string($_GET['adim_beden'])) {
        return '';
    }
    $slug = sanitize_title(wp_unslash($_GET['adim_beden']));
    if ($slug === '' || strlen($slug) > 40 || !taxonomy_exists('pa_beden')) {
        return '';
    }
    return term_exists($slug, 'pa_beden') ? $slug : '';
}

/** Restrict main WooCommerce archive to the requested size attribute. */
add_action('woocommerce_product_query', static function ($query): void {
    if (!is_object($query) || !method_exists($query, 'get') || !method_exists($query, 'set')) {
        return;
    }
    $size = adim_catalog_requested_size();
    if ($size === '') {
        return;
    }
    $old = $query->get('tax_query');
    $current = is_array($old) ? $old : [];
    $filter = [
        'taxonomy' => 'pa_beden',
        'field' => 'slug',
        'terms' => [$size],
        'operator' => 'IN',
    ];
    // Preserve any existing OR conditions but still REQUIRE size via AND.
    $query->set('tax_query', $current
        ? ['relation' => 'AND', $current, $filter]
        : [$filter]);
}, 20);

/** Accessible, no-JavaScript size toolbar for WooCommerce archives. */
add_action('woocommerce_before_shop_loop', static function (): void {
    if (!taxonomy_exists('pa_beden')) {
        return; // WooCommerce size attribute has not been created yet.
    }
    $sizes = get_terms([
        'taxonomy' => 'pa_beden',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
        'number' => 40,
    ]);
    if (is_wp_error($sizes) || empty($sizes)) {
        return;
    }
    $selected = adim_catalog_requested_size();
    $target = remove_query_arg(['adim_beden', 'paged', 'product-page'], get_pagenum_link(1));
    echo '<div class="adim-catalog-filter" role="region" aria-label="Ürün numarası filtresi">';
    echo '<form method="get" action="' . esc_url($target) . '">';
    echo '<label for="adim-size-filter">' . esc_html__('Numaraya göre filtrele', 'adim') . '</label>';
    echo '<select id="adim-size-filter" name="adim_beden">';
    echo '<option value="">' . esc_html__('Tüm numaralar', 'adim') . '</option>';
    foreach ($sizes as $term) {
        echo '<option value="' . esc_attr($term->slug) . '"'
            . selected($selected, $term->slug, false) . '>'
            . esc_html($term->name) . '</option>';
    }
    echo '</select>';
    // Keep native Woo filters (price, sort) while safely limiting GET fields.
    foreach (['min_price', 'max_price', 'orderby'] as $field) {
        if (!isset($_GET[$field]) || !is_string($_GET[$field])) {
            continue;
        }
        $value = sanitize_text_field(wp_unslash($_GET[$field]));
        if ($field === 'orderby') {
            $value = sanitize_key($value);
        } elseif (!is_numeric($value) || (float) $value < 0) {
            continue;
        }
        echo '<input type="hidden" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '">';
    }
    echo '<button type="submit">' . esc_html__('Filtrele', 'adim') . '</button>';
    if ($selected !== '') {
        echo '<a class="adim-filter-reset" href="' . esc_url(remove_query_arg('adim_beden', $target))
            . '">' . esc_html__('Temizle', 'adim') . '</a>';
    }
    echo '</form></div>';
}, 15);

/** Accessible, neutral fitting guidance — avoid making unsupported size promises. */
add_action('woocommerce_single_product_summary', static function (): void {
    echo '<details class="adim-size-help">';
    echo '<summary>' . esc_html__('Numaramı nasıl seçmeliyim?', 'adim') . '</summary>';
    echo '<p>' . esc_html__('Ayak uzunluğunu topuktan en uzun parmağa kadar ölçün. '
        . 'Her modelin kalıbı farklı olabilir; ürün açıklamasındaki ölçü ve numara bilgilerini kontrol edin.', 'adim') . '</p>';
    echo '<p>' . esc_html__('Numara, stok veya kalıpla ilgili sorularınız için mağazayla iletişime geçebilirsiniz.', 'adim') . '</p>';
    echo '</details>';
}, 35);
