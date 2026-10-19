<?php
/**
 * Plugin Name: ADIM Store Operations
 * Description: Minimal ADIM product publication review and safety gate.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Text Domain: adim-operations
 */
defined('ABSPATH') || exit;

add_action('plugins_loaded', static function (): void {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', static function (): void {
            if (current_user_can('activate_plugins')) {
                echo '<div class="notice notice-warning"><p>';
                echo esc_html__('ADIM Store Operations için WooCommerce etkin olmalı.', 'adim-operations');
                echo '</p></div>';
            }
        });
        return;
    }

    // Approval is OFF by default. This is not a substitute for legal verification.
    add_action('woocommerce_product_options_general_product_data', static function (): void {
        if (function_exists('woocommerce_wp_checkbox')) {
            woocommerce_wp_checkbox([
                'id' => '_adim_sale_approved',
                'label' => __('Satışa uygunluk onayı', 'adim-operations'),
                'description' => __('Tedarik faturası ve satış/marka hakları kontrolünden sonra işaretleyin. Kontrol edilmemiş ürünler satın alınamaz.', 'adim-operations'),
                'desc_tip' => true,
            ]);
        }
    });

    add_action('woocommerce_admin_process_product_object', static function ($product): void {
        if (!current_user_can('manage_woocommerce') || !($product instanceof WC_Product)) {
            return;
        }
        $approved = isset($_POST['_adim_sale_approved']) ? 'yes' : 'no';
        $product->update_meta_data('_adim_sale_approved', $approved);
    });

    add_filter('woocommerce_is_purchasable', static function (bool $purchasable, $product): bool {
        if (!$purchasable || !($product instanceof WC_Product)) {
            return false;
        }
        $id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        return get_post_meta($id, '_adim_sale_approved', true) === 'yes';
    }, 99, 2);

    // Block unchecked products from the default shop/archive query.
    add_action('woocommerce_product_query', static function ($query): void {
        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = [
            'key' => '_adim_sale_approved',
            'value' => 'yes',
            'compare' => '=',
        ];
        $query->set('meta_query', $meta_query);
    });

    // The home-page WooCommerce shortcode also needs the same reviewed-products filter.
    add_filter('woocommerce_shortcode_products_query', static function (array $query_args): array {
        $meta_query = isset($query_args['meta_query']) ? (array) $query_args['meta_query'] : [];
        $meta_query[] = [
            'key' => '_adim_sale_approved',
            'value' => 'yes',
            'compare' => '=',
        ];
        $query_args['meta_query'] = $meta_query;
        return $query_args;
    });

    add_action('admin_notices', static function (): void {
        if (current_user_can('manage_woocommerce') && get_current_screen() && get_current_screen()->id === 'edit-product') {
            echo '<div class="notice notice-info"><p>';
            echo esc_html__('ADIM: Satışa uygunluk onayı olmayan ürünler standart katalogda listelenmez ve satın alınamaz. REST/Store API ve ek eklentiler için uçtan uca test yapılmadan canlı satış açılmamalıdır.', 'adim-operations');
            echo '</p></div>';
        }
    });
});
