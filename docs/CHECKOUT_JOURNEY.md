# ADIM — Customer cart, delivery and checkout (draft PR #11)

> **Status:** development-only, no live buyer payments. Based on draft customer UI PR #10 → storefront PR #1. Independent of the physical POS/return PRs #5–#9.

## Native WooCommerce path
1. Visitor finds a **legally authorized** item and selects a real size/color WooCommerce variation with valid managed stock.
2. WooCommerce core/cart provides item quantity, discounts, totals and tax calculations. The ADIM theme adds a 3-step guide, not another cart system.
3. WooCommerce's own shipping/payment checkout provides address and delivery methods. ADIM cannot promise free delivery, rates, shop pickup or fulfillment before real shipping-zone/method configuration.
4. WooCommerce's official, vetted payment integration must be configured separately in a safe sandbox after seller eligibility is verified. No payment information, card form, webhook or invented sandbox gateway is committed here.
5. On the cart and checkout WordPress page (classic shortcode or blocks), the theme shows an accessible progress guide. While sales is OFF it explains why no online order can be placed. The classic checkout review also shows a reminder to check actual address, size, delivery option and final total.

## Closed-sales cart behavior
- With `ADIM_ENABLE_SALES` OFF, the classic WooCommerce cart removes its default "proceed to checkout" button using the native Woo action; a clear notice explains online purchasing is not yet open.
- WooCommerce block cart UI may render a different button; never assume a cosmetic control alone is secure. The Store API POST checkout guard remains authoritative.
- Empty checkout can redirect back to cart under WooCommerce's normal behavior. Browser tests accept and verify that redirect or the on-page checkout explanation, rather than forcing a checkout view without an item.

## Fail-closed server boundaries
- `ADIM_ENABLE_SALES` is **OFF** by default, only enabled in a short-lived test process after constructing synthetic products.
- `adim-operations/includes/checkout-guard.php` rejects `POST /wc/store/vN/checkout` with HTTP 403 and `adim_checkout_disabled` **before the Store API controller can create an order** when disabled. A normal GET product list continues to work.
- Both classic Woo checkout process validation and REST checkout reject items whose sale approval was revoked after an item was placed in cart (REST responds 409 `adim_checkout_product_blocked`). WooCommerce still applies its own stock and quantity checks; these guards are supplemental.
- No payment gateway, hosted checkout, bank, fake order, fake free delivery or hardcoded fee in this PR.
- The guard checks the existing Woo `WC()->cart` and does not duplicate WooCommerce stock or session functionality.

## Changed files
- `wp-content/plugins/adim-operations/includes/checkout-guard.php` — Store API/classic checkout defense-in-depth.
- `wp-content/plugins/adim-operations/adim-operations.php` — includes guard.
- `wp-content/themes/adim/inc/checkout.php` — stages, delivery copy and status.
- `wp-content/themes/adim/page.php` — renders steps even for empty carts or Woo blocks.
- `wp-content/themes/adim/functions.php`, `assets/css/storefront.css` — theme inclusion and mobile cart/checkout UI.
- `tests/checkout-journey.php` — real WooCommerce session/cart tests (no orders created).
- `tests/checkout-pages-setup.php` — disposable WP CLI-only cart/checkout pages.
- `tests/browser-mobile.mjs` — browser cart/checkout screens, sales-off message, overflow.
- `.github/workflows/woocommerce-integration.yml` — automated integration/browser QA.

## Test acceptance
- Real WooCommerce/MySQL: closed Store API POST rejects before order creation; normal product GET works; approved synthetic cart passes only supplemental checks; approval revoked after add to cart prevents REST POST; order count unchanged.
- Real Chromium 390px: empty cart and checkout stage information and truthful blocked-sales message; no horizontal overflow on checkout; screenshots included with existing home/product captures.
- **Check PR #11 latest GitHub Actions status before claiming pass.** Green CI does not certify real payment integration or legal trading readiness.

## Next work
- Test with a real configured shipping zone and local pickup *only when the merchant has established the fulfillment policy*; do not expose options not actually supported.
- Payment-provider sandbox and signed webhook validation after formal merchant/provider account approval.
- Verify Woo blocks vs classic checkout with real browser cart populated; perform guest shipping/address validation and concurrent stock reservation vs physical POS, then backups and real device/keyboard/screen-reader QA.
- Audit WP/Woo theme page routing and consistency before final design approval.
- Keep sales/physical POS/returns/restock flags OFF until acceptance and lawful, authorized products. Unauthorized trademarked replicas must not be listed or sold.
