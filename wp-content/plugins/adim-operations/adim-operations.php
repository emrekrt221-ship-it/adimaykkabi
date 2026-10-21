<?php
/**
 * Plugin Name: ADIM Store Operations
 * Description: Product authorization and staged-sales guard for WooCommerce.
 * Version: 0.2.0
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Text Domain: adim-operations
 */
defined('ABSPATH') || exit;

// Keep stock operations isolated from display/business-authorization hooks.
require_once __DIR__ . '/includes/pos-roles.php';
require_once __DIR__ . '/includes/pos.php';
require_once __DIR__ . '/includes/pos-admin.php';
require_once __DIR__ . '/includes/pos-reconciliation.php';
require_once __DIR__ . '/includes/pos-returns.php';
register_activation_hook(__FILE__, static function (): void {
    adim_pos_install();
    adim_pos_install_roles();
    adim_return_install();
});
add_action('init', 'adim_pos_install_roles', 20);
add_action('admin_init', static function (): void {
    if (current_user_can('manage_woocommerce')) {
        if (get_option('adim_pos_db_version') !== '1') { adim_pos_install(); }
        if (get_option('adim_returns_db_version') !== '1') { adim_return_install(); }
    }
});


/**
 * Keep checkout closed until a reviewed operator deployment explicitly enables it.
 * Add define('ADIM_ENABLE_SALES', true); to private server configuration ONLY
 * after inventory, payment gateway and compliance acceptance tests are completed.
 */
function adim_ops_sales_enabled(): bool
{
    return defined('ADIM_ENABLE_SALES') && ADIM_ENABLE_SALES === true;
}

/** Resolve the parent product ID of a variation, otherwise the product ID. */
function adim_ops_parent_id($product): int
{
    if (!($product instanceof WC_Product)) {
        return 0;
    }
    return $product->is_type('variation') ? (int) $product->get_parent_id() : (int) $product->get_id();
}

/** Review flag is fail-closed: missing, unknown and variation-only metadata deny. */
function adim_ops_is_reviewed($product): bool
{
    $id = adim_ops_parent_id($product);
    return $id > 0 && get_post_meta($id, '_adim_sale_approved', true) === 'yes';
}

/** Adds the review condition to query arguments without wiping other conditions. */
function adim_ops_reviewed_query(array $args): array
{
    $existing = isset($args['meta_query']) && is_array($args['meta_query'])
        ? $args['meta_query']
        : [];
    $review = [
        'key' => '_adim_sale_approved',
        'value' => 'yes',
        'compare' => '=',
    ];

    // WooCommerce and third-party filters can supply their own OR relation.
    // Appending the review condition to an OR query would allow unreviewed
    // items through. Preserve the original expression in a nested group, and
    // REQUIRE review by joining the two groups with AND.
    $args['meta_query'] = $existing
        ? ['relation' => 'AND', $existing, $review]
        : [$review];
    return $args;
}

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

    // This flag is a manual review record, NOT automatic trademark clearance.
    add_action('woocommerce_product_options_general_product_data', static function (): void {
        if (function_exists('woocommerce_wp_checkbox')) {
            woocommerce_wp_checkbox([
                'id' => '_adim_sale_approved',
                'label' => __('Satışa uygunluk onayı', 'adim-operations'),
                'description' => __('Yalnızca tedarik ve satış hakları belgeleri kontrol edilen ürünleri onaylayın.', 'adim-operations'),
                'desc_tip' => true,
            ]);
        }
    });

    add_action('woocommerce_admin_process_product_object', static function ($product): void {
        if (!current_user_can('manage_woocommerce') || !($product instanceof WC_Product)) {
            return;
        }
        $approved = isset($_POST['_adim_sale_approved']) && $_POST['_adim_sale_approved'] === 'yes'
            ? 'yes'
            : 'no';
        $product->update_meta_data('_adim_sale_approved', $approved);
    });

    // Parent authorization applies to each size/color variation.
    $purchasable = static function ($allowed, $product): bool {
        return (bool) $allowed && adim_ops_sales_enabled() && adim_ops_is_reviewed($product);
    };
    add_filter('woocommerce_is_purchasable', $purchasable, 99, 2);
    add_filter('woocommerce_variation_is_purchasable', $purchasable, 99, 2);
    add_filter('woocommerce_product_is_visible', static function ($visible, $product_id): bool {
        if (!$visible || !function_exists('wc_get_product')) {
            return false;
        }
        return adim_ops_is_reviewed(wc_get_product((int) $product_id));
    }, 99, 2);

    // Store API single-product routes bypass collection-query filters.
    // Deny direct public requests for products that have not been reviewed.
    add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
        if ($result !== null || !is_object($request) || !method_exists($request, 'get_route')) {
            return $result;
        }
        $route = (string) $request->get_route();
        if (!preg_match('#^/wc/store/v[0-9]+/products/([0-9]+)(?:/|$)#', $route, $matches)) {
            return $result;
        }
        // Admin REST APIs are separate paths; do not disable authenticated management.
        $id = (int) $matches[1];
        if (!function_exists('wc_get_product') || !adim_ops_is_reviewed(wc_get_product($id))) {
            return new WP_Error(
                'adim_product_unavailable',
                __('Ürün bulunamadı.', 'adim-operations'),
                ['status' => 404]
            );
        }
        return $result;
    }, 10, 3);

    // Block add-to-cart requests separately from WooCommerce's own purchasability check.
    add_filter('woocommerce_add_to_cart_validation', static function ($valid, $product_id, $quantity = 1, $variation_id = 0): bool {
        if (!$valid || !function_exists('wc_get_product')) {
            return false;
        }
        $id = $variation_id ? (int) $variation_id : (int) $product_id;
        return adim_ops_sales_enabled() && adim_ops_is_reviewed(wc_get_product($id));
    }, 99, 4);

    // Defense-in-depth for an already populated cart, including a revoked review.
    add_action('woocommerce_check_cart_items', static function (): void {
        if (!function_exists('WC') || !WC()->cart) {
            return;
        }
        foreach (WC()->cart->get_cart() as $item) {
            if (!adim_ops_sales_enabled() || !adim_ops_is_reviewed($item['data'] ?? null)) {
                if (function_exists('wc_add_notice')) {
                    wc_add_notice(__('Sepetteki ürünlerden biri şu anda satışa uygun değil.', 'adim-operations'), 'error');
                }
                return;
            }
        }
    });

    // Traditional WooCommerce archive, shortcode and REST product controller.
    add_action('woocommerce_product_query', static function ($query): void {
        if (!is_object($query) || !method_exists($query, 'get') || !method_exists($query, 'set')) {
            return;
        }
        $args = adim_ops_reviewed_query(['meta_query' => $query->get('meta_query')]);
        $query->set('meta_query', $args['meta_query']);
    });
    add_filter('woocommerce_shortcode_products_query', 'adim_ops_reviewed_query', 99, 1);
    add_filter('woocommerce_rest_product_object_query', 'adim_ops_reviewed_query', 99, 1);

    // Store API product collections use WP_Query. Restrict public product
    // queries even when the classic WooCommerce product-query action is not run.
    add_action('pre_get_posts', static function ($query): void {
        if (!is_object($query) || !method_exists($query, 'get') || !method_exists($query, 'set')) {
            return;
        }
        if (function_exists('is_admin') && is_admin() && !(defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        $type = $query->get('post_type');
        if ($type !== 'product') {
            return;
        }
        $args = adim_ops_reviewed_query(['meta_query' => $query->get('meta_query')]);
        $query->set('meta_query', $args['meta_query']);
    }, 99, 1);

    // Public direct product URLs should not disclose unreviewed product content.
    add_action('template_redirect', static function (): void {
        if (!function_exists('is_singular') || !is_singular('product')) {
            return;
        }
        $id = (int) get_queried_object_id();
        if (current_user_can('edit_post', $id)) {
            return;
        }
        if (function_exists('wc_get_product') && !adim_ops_is_reviewed(wc_get_product($id))) {
            global $wp_query;
            if (is_object($wp_query) && method_exists($wp_query, 'set_404')) {
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
            }
        }
    }, 1);

    add_action('admin_notices', static function (): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        if (!adim_ops_sales_enabled()) {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('ADIM: Canlı satış kapalı. Yasal, ödeme ve test onaylarından önce ADIM_ENABLE_SALES açılmamalıdır.', 'adim-operations');
            echo '</p></div>';
        }
    });
});
