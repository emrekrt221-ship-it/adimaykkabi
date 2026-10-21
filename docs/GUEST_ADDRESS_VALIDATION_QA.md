# ADIM — Native guest checkout address-validation QA (draft PR #13)

Date: 2026-10-21. This is development-only testing of WooCommerce's built-in checkout form validation. **No card charge, actual delivery, order placement, personal customer account, or legal merchant activation.**

## Purpose
Catch common guest checkout errors before integrating an approved payment provider:
- First name and street address left empty
- Invalidly formatted email
- Invalid phone input
- Nonexistent country code
- Valid fictional email and first name can be corrected without submitting a transaction

## Testing method
1. `tests/guest-address-validation.php` runs inside an explicitly flagged disposable WordPress+WooCommerce/MySQL WP-CLI environment after PR #12's synthetic product and shipping zone are created.
2. The script temporarily defines `ADIM_ENABLE_SALES` only **inside its PHP process**, constructs a guest `WC_Cart`, and uses `WC_Checkout::validate_posted_data` by PHP Reflection. Reflection is a **test-only technique** to check the actual protected WooCommerce field-validation implementation WITHOUT calling `process_checkout`. No production PHP uses reflection.
3. Each malformed field is tested with `WP_Error` codes produced by WooCommerce itself; missing checkout keys are explicitly set to blank to avoid WooCommerce skipping absent request fields.
4. The script compares WooCommerce order IDs before and after validation; payment gateways remain disabled.
5. `tests/browser-guest-checkout.mjs` runs Chromium 390px, checks browser-side email format and required first-name field, corrects synthetic values and checks real shipping method/checkout UI. **Never clicks the Place Order button.**
6. All PR #12 full shipping/checkout/browser tests are retained. Country availability comes from WooCommerce configuration, not guessed unsupported shipping package behavior.

## Security and honesty
- Personal fields use disposable fictional identities and addresses, not real customer information.
- The test-only sample shoe and ₺59 flat-rate are NOT valid production catalog/product/shipping tariffs.
- Green CI does NOT prove payment authorization, Turkish address deliverability, shipping price correctness for real carriers, returns legality or VAT/fiscal compliance.
- Never list counterfeit trademarked goods or turn on payment without verified right to sell. Online sales remain off in persistent deployment config.

## Next engineering priorities
- Verify current PR #13 integration and browser CI at HEAD and inspect screenshot artifacts.
- Extend coverage to WC checkout/cart Store API invalid-address handling, stock changes between cart and pay, and real delivery rules when merchant/carrier pricing is known.
- Persistent private HTTPS staging, actual screen-reader/keyboard QA, backups/restore and an approved gateway sandbox are release blockers.

## Branches
PR #13 `test/guest-address-validation` → PR #12 `test/guest-cart-shipping-flow` → PR #11 → PR #10 → PR #1. Physical-store POS/returns PRs #5–#9 are a separate track; all remain draft/unmerged at snapshot.
