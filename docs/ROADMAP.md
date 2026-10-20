# ADIM AYAKKABI roadmap (2026-10-20)
A checked task means implementation + focused test exists in a draft PR; it does NOT mean merged, deployed or legally approved.

- [x] Repo/architecture/hand-off files and full 21-section master plan (PR #1).
- [x] Woo mobile theme: search/cart/category/home/catalog starter (PR #1).
- [x] Product approval guard, Store API detail/HTTP checks and CI (PR #1).
- [x] Woo variation size stock isolation and basic contention tests (PR #5).
- [x] Woo physical sale via order + stock reservation with request-key idempotency (PR #5).
- [x] Responsive cashier admin screen, SKU search, collection method and nonce (PR #5).
- [x] Isolated POS cashier role and manager-only reconciliation with Woo audit notes (PR #6).
- [x] Two-process sale concurrency and role/reconciliation integration tests (PRs #5/6).
- [ ] POS interruption and crash recovery tests, stale processing investigation/repair workflow.
- [ ] Audited refunds, returns and stock inspection before replenishment.
- [ ] Permanent secure staging with SSL, offsite backup and restore tests.
- [ ] Real browser/mobile accessibility and performance QA.
- [ ] Authorized/legal products data model, category filters, photos/SKU import and search.
- [ ] Online cart and checkout with failure/reversal/Store API POST and physical stock mixed contention.
- [ ] Approved card gateway sandbox and verified callback idempotency.
- [ ] Shipping, in-store pickup, tracking, notifications and order history.
- [ ] Tax/fiscal receipt/invoice and merchant, trademark, ETBIS, consumer and privacy readiness.
- [ ] Staff operations reporting and stock reconciliation dashboard.
- [ ] Optional Redis/CDN only with measured need; no Cloudflare for v1.

Launch definition: approved legal inventory and financial/legal terms, production QA & security, reviewed/merged PRs, tested staging, proper merchant payment and fulfillment.
