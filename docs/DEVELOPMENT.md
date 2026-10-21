# ADIM WordPress/WooCommerce development

## Repo scope
Version custom WordPress theme `wp-content/themes/adim` and operations plugin `wp-content/plugins/adim-operations`. Do not commit WordPress core, WooCommerce vendor files, `wp-config.php`, uploads or secrets.

## Set up private staging
1. Install supported WordPress, WooCommerce, PHP/MySQL on isolated, non-public staging.
2. Copy theme and operations plugin to WordPress `wp-content`, enable WooCommerce first, HPOS (where compatible), then theme and plugin.
3. Create default Woo pages, menu, categories and legally authorized synthetic test products. Include size/color variation stocks.
4. By default `ADIM_ENABLE_SALES` is not defined; **nobody can purchase**. For *staging test only*, define `ADIM_ENABLE_SALES` as true in private server configuration `wp-config.php`. Never commit this value or actual payment keys.
5. Test both switch settings, product review flag on/off, parent/variation, cart/checkout and access via direct product URL, Store API, REST API and other enabled plugins. No real payment until legal and provider approval.
6. Configure test gateway, shipping and transactional email, then run full release checklist. Do not deploy without approvals.

## Automated isolated checks (PHP and Node required)
```bash
find wp-content tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
find wp-content -type f -name '*.js' -print0 | xargs -0 -n1 node --check
php tests/operations-smoke.php
php tests/operations-smoke.php --enabled
```
The mock tests validate hook-level logic, NOT native WooCommerce integration. GitHub Actions repeats these checks on PR updates.

## Required staging checks
- Product with no review: ordinary catalog, shortcodes, direct URL, Woo Store API, WC REST and all add-to-cart routes must not disclose/permit purchase (test explicitly).
- Approved product: visible only if otherwise Woo-visible; purchasable only when staging sales switch is true and its stock is positive.
- Product/variation approval inherited from parent; revoked approval blocks populated cart.
- Mobile 360/390/430/768/1280 and keyboard focus, local caching exclusions, real gateway sandbox and refund tests.
- Run Woo integration/load/security tests and record evidence; never infer from mocked checks.

## Session protocol
Read `AGENTS.md`, `docs/STATUS.md`, `docs/SESSION_HANDOFF.md` before starting and update both handoff/status at the end. GitHub is canonical across chats.
