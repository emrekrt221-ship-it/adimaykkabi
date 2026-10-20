<?php
/** Isolated ADIM operation-hook smoke tests. Not WordPress integration tests. */
declare(strict_types=1);

if (($argv[1] ?? '') === '--enabled') {
    define('ADIM_ENABLE_SALES', true);
}
define('ABSPATH', '/tmp/');
class WooCommerce {}
class WC_Product {
    public array $changes = [];
    public function __construct(private int $id, private int $parent = 0) {}
    public function is_type(string $type): bool { return $type === 'variation' && $this->parent > 0; }
    public function get_parent_id(): int { return $this->parent; }
    public function get_id(): int { return $this->id; }
    public function update_meta_data(string $key, $value): void { $this->changes[$key] = $value; }
}
class ADIM_Query_Mock {
    private array $args;
    public function __construct(array $args = []) { $this->args = $args; }
    public function get($name) { return $this->args[$name] ?? null; }
    public function set($name, $value) { $this->args[$name] = $value; }
}
$hooks = [];
$products = [
    101 => new WC_Product(101),
    102 => new WC_Product(102),
    201 => new WC_Product(201, 101),
    202 => new WC_Product(202, 102),
];
$meta = [101 => 'yes', 102 => 'no'];
$notices = [];
$cartItems = [];
function add_action($name, $callback, $priority = 10, $argc = 1) {
    global $hooks;
    $hooks[$name][] = compact('callback', 'priority', 'argc');
}
function add_filter($name, $callback, $priority = 10, $argc = 1) { add_action($name, $callback, $priority, $argc); }
function do_action($name, ...$args) {
    global $hooks;
    $items = $hooks[$name] ?? [];
    usort($items, fn($a, $b) => $a['priority'] <=> $b['priority']);
    foreach ($items as $h) {
        $h['callback'](...array_slice($args, 0, min(count($args), $h['argc'])));
    }
}
function apply_filters($name, $value, ...$args) {
    global $hooks;
    $items = $hooks[$name] ?? [];
    usort($items, fn($a, $b) => $a['priority'] <=> $b['priority']);
    foreach ($items as $h) {
        $value = $h['callback'](...array_slice([$value, ...$args], 0, $h['argc']));
    }
    return $value;
}
function get_post_meta($id, $key, $single) {
    global $meta;
    return $key === '_adim_sale_approved' ? ($meta[$id] ?? '') : '';
}
function current_user_can($cap, $id = null) { return in_array($cap, ['manage_woocommerce', 'activate_plugins'], true); }
function esc_html__($s, $domain = '') { return $s; }
function __($s, $domain = '') { return $s; }
function wc_get_product($id) { global $products; return $products[$id] ?? false; }
function is_admin(): bool { return false; }
function wc_add_notice($message, $type) { global $notices; $notices[] = compact('message', 'type'); }
function WC() {
    return new class {
        public $cart;
        public function __construct() {
            $this->cart = new class {
                public function get_cart(): array { global $cartItems; return $cartItems; }
            };
        }
    };
}
function check($expected, $actual, $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException("FAIL {$message}: expected " . var_export($expected, true) . ", got " . var_export($actual, true));
    }
    echo "PASS {$message}\n";
}
require dirname(__DIR__) . '/wp-content/plugins/adim-operations/adim-operations.php';
do_action('plugins_loaded');
$enabled = defined('ADIM_ENABLE_SALES') && ADIM_ENABLE_SALES;
check($enabled, adim_ops_sales_enabled(), 'global sales switch');
check(true, adim_ops_is_reviewed($products[101]), 'reviewed parent');
check(true, adim_ops_is_reviewed($products[201]), 'reviewed variation inherits parent');
check(false, adim_ops_is_reviewed($products[102]), 'unreviewed product rejected');
check(false, adim_ops_is_reviewed($products[202]), 'unreviewed variation rejected');
check(false, adim_ops_is_reviewed(null), 'missing product rejected');
check($enabled, apply_filters('woocommerce_is_purchasable', true, $products[101]), 'reviewed purchase depends on launch switch');
check($enabled, apply_filters('woocommerce_variation_is_purchasable', true, $products[201]), 'reviewed variation purchase');
check(false, apply_filters('woocommerce_is_purchasable', true, $products[102]), 'unreviewed purchase denied');
check(false, apply_filters('woocommerce_is_purchasable', false, $products[101]), 'native Woo denial preserved');
check(false, apply_filters('woocommerce_product_is_visible', true, 102), 'unreviewed not visible');
check(true, apply_filters('woocommerce_product_is_visible', true, 101), 'reviewed visible');
check(false, apply_filters('woocommerce_product_is_visible', false, 101), 'hidden native product remains hidden');
check($enabled, apply_filters('woocommerce_add_to_cart_validation', true, 101, 1, 201), 'reviewed variation add-to-cart');
check(false, apply_filters('woocommerce_add_to_cart_validation', true, 102, 1, 202), 'unreviewed add-to-cart');
check(false, apply_filters('woocommerce_add_to_cart_validation', false, 101, 1, 201), 'existing cart failure preserved');
$args = apply_filters('woocommerce_shortcode_products_query', ['meta_query' => [['key' => 'size', 'value' => '42']]]);
check('AND', $args['meta_query']['relation'], 'shortcode query requires reviewed AND original filter');
check('size', $args['meta_query'][0][0]['key'], 'original shortcode filter retained');
check('_adim_sale_approved', $args['meta_query'][1]['key'], 'shortcode query review gate');
$orArgs = apply_filters('woocommerce_shortcode_products_query', [
    'meta_query' => [
        'relation' => 'OR',
        ['key' => 'shoe_size', 'value' => '42'],
        ['key' => 'color', 'value' => 'black'],
    ],
]);
check('AND', $orArgs['meta_query']['relation'], 'OR filter is guarded by AND');
check('OR', $orArgs['meta_query'][0]['relation'], 'nested OR logic preserved');
check('_adim_sale_approved', $orArgs['meta_query'][1]['key'], 'review check cannot be bypassed through OR');

$args = apply_filters('woocommerce_rest_product_object_query', ['post_type' => 'product']);
check('_adim_sale_approved', $args['meta_query'][0]['key'], 'REST query review gate');
$query = new ADIM_Query_Mock(['post_type' => 'product', 'meta_query' => [['key' => 'color', 'value' => 'black']]]);
do_action('pre_get_posts', $query);
check('AND', $query->get('meta_query')['relation'], 'public product WP_Query review gate');
check('color', $query->get('meta_query')[0][0]['key'], 'public query original filter preserved');
$query = new ADIM_Query_Mock(['post_type' => 'page']);
do_action('pre_get_posts', $query);
check(null, $query->get('meta_query'), 'normal pages unchanged');
$query = new ADIM_Query_Mock(['meta_query' => []]);
do_action('woocommerce_product_query', $query);
check('_adim_sale_approved', $query->get('meta_query')[0]['key'], 'classic archive review gate');
$cartItems = [['data' => $products[101]]];
$notices = [];
do_action('woocommerce_check_cart_items');
check(!$enabled, count($notices) > 0, 'global switch existing cart');
$cartItems = [['data' => $products[102]]];
$notices = [];
do_action('woocommerce_check_cart_items');
check(1, count($notices), 'revoked product prevents checkout');
$_POST = ['_adim_sale_approved' => 'yes'];
do_action('woocommerce_admin_process_product_object', $products[101]);
check('yes', $products[101]->changes['_adim_sale_approved'], 'review checkbox saved');
$_POST = [];
do_action('woocommerce_admin_process_product_object', $products[101]);
check('no', $products[101]->changes['_adim_sale_approved'], 'uncheck revokes approval');
echo "Isolated operations smoke tests passed; real WooCommerce integration still required.\n";
