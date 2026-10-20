#!/usr/bin/env bash
# Real HTTP checks against the disposable WP/WooCommerce installation in CI.
set -euo pipefail

WP_DIR="${WP_DIR:-/tmp/adim-wordpress}"
WPCLI="${WPCLI:-/tmp/wp-cli.phar}"
BASE="http://127.0.0.1:8888"

cat > /tmp/adim-router.php <<'PHP'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($_SERVER['DOCUMENT_ROOT'] . $path)) {
    return false;
}
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
PHP

php -S 127.0.0.1:8888 -t "$WP_DIR" /tmp/adim-router.php > /tmp/adim-http.log 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT

ready=0
for attempt in $(seq 1 20); do
    if curl --silent --fail "$BASE/" > /tmp/adim-home.html; then
        ready=1
        break
    fi
    sleep 1
done

if [[ "$ready" != "1" ]]; then
    echo "WordPress server did not become ready"
    cat /tmp/adim-http.log
    exit 1
fi

grep -q 'Tarzına Yeni Bir Adım' /tmp/adim-home.html
grep -q 'adim-menu-toggle' /tmp/adim-home.html
grep -q 'adim-primary-navigation' /tmp/adim-home.html
echo "PASS: home page, mobile menu and catalog entrypoint render over HTTP"

curl --silent --show-error --fail "$BASE/wp-content/themes/adim/assets/css/storefront.css" -o /tmp/adim-style.css
grep -q 'adim-category-grid' /tmp/adim-style.css
echo "PASS: bundled CSS is served directly"

products=$(php "$WPCLI" post list --post_type=product --fields=ID,post_title --format=json --path="$WP_DIR" --allow-root)
approved=$(printf '%s' "$products" | jq -r '.[] | select(.post_title == "ADIM TEST approved sample") | .ID')
blocked=$(printf '%s' "$products" | jq -r '.[] | select(.post_title == "ADIM TEST blocked sample") | .ID')

if [[ -z "$approved" || -z "$blocked" ]]; then
    echo "Integration products were not created"
    exit 1
fi

approved_http=$(curl --silent --show-error --output /tmp/adim-approved.html --write-out '%{http_code}' "$BASE/?post_type=product&p=$approved")
if [[ "$approved_http" != "200" ]]; then
    echo "Approved product expected 200; got $approved_http"
    exit 1
fi
grep -q 'ADIM TEST approved sample' /tmp/adim-approved.html
echo "PASS: reviewed product detail is visible to buyer"

blocked_http=$(curl --silent --show-error --output /tmp/adim-blocked.html --write-out '%{http_code}' "$BASE/?post_type=product&p=$blocked")
if [[ "$blocked_http" != "404" ]]; then
    echo "Unreviewed product expected 404; got $blocked_http"
    exit 1
fi
if grep -q 'ADIM TEST blocked sample' /tmp/adim-blocked.html; then
    echo "Unreviewed product details leaked in 404 page"
    exit 1
fi
echo "PASS: unreviewed product detail is not visible over HTTP"

echo "ADIM live HTTP storefront checks passed."
