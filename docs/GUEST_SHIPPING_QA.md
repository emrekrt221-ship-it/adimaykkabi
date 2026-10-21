# ADIM — Synthetic guest cart and shipping QA (draft PR #12)

This work is a **disposable test harness**, not a public shipping tariff, live product list, real order flow, gateway, refund or merchant approval.

## Purpose
Verify that native WooCommerce can calculate a guest basket containing two neutral test shoes, select a real (but **test-only**) domestic shipping zone and show a filled native checkout with guest delivery/address fields.

## Synthetic-only test settings
- Product: `ADIM CI Synthetic Guest Shoe`, SKU `ADIM-CI-LEGAL-DEMO-SHOE`, price **₺1,500** each, five units, demonstrably fake/neutral CI product.
- Shipping destination: Türkiye, synthetic Reyhanlı address and arbitrary example postcode. Woo zone named `ADIM CI Türkiye Shipping`, configured core `flat_rate` method labelled `ADIM CI Fixed Delivery`, **₺59 purely for the test**.
- No real carrier, store pickup, delivery estimate, production tax policy or actual fee is implied.
- Woo settings `woocommerce_calc_taxes=no`, currency TRY, guest checkout allowed **only in disposable CI**.
- BACS/COD/cheque/other bundled offline payment methods disabled in CI.
- `ADIM_ENABLE_SALES` is set TRUE only for the **second isolated localhost browser test** in temporary `/tmp/adim-wordpress/wp-config.php` and explicitly deleted afterward. Original source/config and live sites stay default OFF. The earlier safety tests first run with sales OFF.

## Automated test boundary
1. `tests/guest-shipping-fixture.php` initializes test product, domestic shipping zone/rate and denies execution without `ADIM_CI_FIXTURE=yes` + WP CLI.
2. `tests/guest-shipping.php` runs a native Woo guest cart with quantity two, computes subtotal/selected shipping/total and verifies WooCommerce exposes **only Türkiye** in the allowed shipping-country selector. Unsupported direct-package tests are not used because they bypass the configured location policy.
3. `tests/browser-guest-checkout.mjs` uses Chromium mobile 390px to visit the synthetic product add-to-cart URL, real cart and checkout, fill fictional customer name/address and assert native flat-rate option without clicking Place Order.
4. `tests/guest-no-orders.php` asserts **zero WooCommerce orders and zero enabled payment gateways** after the browser preview.
5. All other established storefront, catalog, closed-sales checkout guard and browser smoke tests still run.

## Operational caution
Passing this CI does not establish delivery coverage, a shipping contract, consumer checkout accessibility, live order acceptance, tax/fiscal invoicing, card payments or compliance. Configure real domestic shipping rates and legally authorized merchandise in a permanent private staging setup before any production launch. Never list/sell unauthorized branded counterfeit footwear.

## Current state
Draft PR #12 on `test/guest-cart-shipping-flow` targeting draft PR #11 on `feat/checkout-journey-and-guards`, then #10 and #1. Physical POS/returns PR #5–#9 are a separate chain. Inspect latest GitHub Actions run before claiming actual success.

## Suggested next step
Expand the CI test to a genuine Woo checkout validation phase with **no payment transaction**, add shipping-zone boundary tests and approved/revoked product test cases. Then configure private staging HTTPS/backups and an authorized payment provider's sandbox only after actual merchant approval.
