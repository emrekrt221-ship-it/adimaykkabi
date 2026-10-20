# ADIM AYAKKABI roadmap
Updated 2026-10-20. [x] means code + focused CI exists; merge/deployment may remain pending.

- [x] ADIM-01a: GitHub repo and persistent project/handoff documentation.
- [x] ADIM-02a/b: Initial responsive WooCommerce theme, header/search/cart/catalog shell (draft PR #1).
- [x] ADIM-11a/b: Product authorization and Store API single-product guard, direct HTML 404, OR catalog filter regression (draft PR #1).
- [x] ADIM-12a: PHP/JS syntax, mock tests, real ephemeral WP/Woo/MySQL and basic HTTP CI.
- [x] ADIM-04a: Real Woo variation 42/43 stock isolation in CI.
- [x] ADIM-05a: Physical store sale prototype: UUID ledger, WooCommerce reservation/reduction, validated stock/order, and reviewed product check (draft PR #5).
- [x] ADIM-05b: POS single vs duplicate requests, two-process same last-unit race, and actual variation SKU CI test (draft PR #5).
- [x] ADIM-10a: Responsive admin cashier SKU search, nonce/capability, payment confirmation and safe default-disabled state (draft PR #5).
- [ ] ADIM-01b: Persistent staging server, SSL, backups and restore test.
- [ ] ADIM-02c: Actual mobile browser and keyboard/accessibility review.
- [ ] ADIM-03: Category/size/price filtering, product detail and full checkout UX.
- [ ] ADIM-04b: Bulk import/validate SKU, photos, cost and supplier reference, audit.
- [ ] ADIM-05c: POS reconciliation of interrupted/stale records, staff roles, refunds and receipt/tax processes.
- [ ] ADIM-06: Online checkout and stock reserve/release under payment failure and mixed online-vs-physical race tests.
- [ ] ADIM-07: Approved payment processor sandbox, verified notifications, idempotency.
- [ ] ADIM-08: Shipping, store pickup, email and tracking.
- [ ] ADIM-09: Returns and refunds with inspection-before-restock.
- [ ] ADIM-10b: Staff role separation and reporting.
- [ ] ADIM-11c: Trademark/legal merchandise rights, ETBIS, KVKK and consumer information; gateway underwriting.
- [ ] ADIM-12b: Backups, load tests, recovery, production security review.
- [ ] ADIM-13: Consent-aware analytics, SEO and Instagram attribution.
- [ ] ADIM-14: Optional barcode scanner, Redis and shipping automation after validation.

Live acceptance criteria: reviewed and merged code; real staging QA including interruption cases; legal merchandise, fiscal, security and payment/merchant approvals. No live sale based on green CI alone.
