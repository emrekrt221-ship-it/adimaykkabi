# ADIM AYAKKABI – roadmap
Updated 2026-10-20. `[x]` means code and relevant tests exist; features may still be unmerged or unreleased.

- [x] ADIM-01a: Initialize repository, architecture and cross-chat project memory.
- [x] ADIM-02a: Basic WooCommerce theme scaffold.
- [x] ADIM-02b: Mobile-first header/search/cart/home/category templates in draft PR.
- [x] ADIM-11a: Add basic reviewed-inventory sales gate and default-off switch with real WooCommerce tests.
- [x] ADIM-11b: Test reviewed product collections, direct Store API detail, frontend 404 and OR filters.
- [x] ADIM-12a: PHP/JS lint, isolated unit hooks, disposable WooCommerce/MySQL CI, actual HTTP smoke checks.
- [x] ADIM-04a: Verify per-size WooCommerce variation stock isolation (sequential test only).
- [ ] ADIM-01b: Set up persistent private staging and hosted SSL + backup recovery.
- [ ] ADIM-02c: Real mobile browser, keyboard and accessibility audit.
- [ ] ADIM-03: Product categories, filters, search, detailed variation-selection customer UX QA.
- [ ] ADIM-04b: Catalog bulk import, validated SKU, price, photos and audit logs.
- [ ] ADIM-05: Physical shop sale recording, shared variation stock, idempotency and simultaneous last-item concurrency tests.
- [ ] ADIM-06: Guest checkout, cancellation, failed payment and stock reserve/release checks.
- [ ] ADIM-07: Approved payment provider sandbox and secure webhook idempotency.
- [ ] ADIM-08: Shipping, store pickup, order emails and tracking.
- [ ] ADIM-09: Returns, refunds, quarantine/inspection before restocking.
- [ ] ADIM-10: Staff roles, secure mobile operational admin, reports.
- [ ] ADIM-11c: Legal inventory/brand authorization, ETBIS, consumer/KVKK and gateway eligibility.
- [ ] ADIM-12b: Performance/SEO, load tests, backups and production-ready security review.
- [ ] ADIM-13: Consent-controlled analytics and social referrals.
- [ ] ADIM-14: Optional barcode, Redis and shipping API after measured need.

Definition of Done for release: reviewed/merged change, passing meaningful real integration tests, and required business/legal/operator approvals. Green lint alone is not sufficient.

- [x] Draft PR #10: mobile product-card and product-detail CSS, WooCommerce Beden filter and native product variation UX; test with legally neutral WooCommerce fixtures.
- [ ] Draft PR #10: real Chromium mobile 360/390/768/1280 browser screenshot audit and any findings; staging and actual assisted-device verification.

- [x] PR #10 real Chromium viewport tests (360, 390, 768, 1280px) and product-detail mobile smoke; 39565540676 passed, screenshot artifact retained.
- [ ] Operator visual signoff, accessibility keyboard/screen-reader audit, Lighthouse/Core Web Vitals on persistent private staging and genuine checkout.

- [x] PR #11 draft: native cart/checkout progress guide with accurate shipping/payment copy, fail-closed Woo classic checkout and Store API POST checkout, revoked-cart product validation and initial real integration tests.
- [ ] PR #11 Chromium mobile cart/checkout and real guest checkout with configured sandbox/payment and shipping zone; check current CI and screenshot artifacts.
- [ ] Legal product/seller/provider verification and live payment/fulfillment integration; no merchant secrets in repo.
