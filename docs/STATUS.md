# ADIM AYAKKABI — current development status
Updated: 2026-10-20
Repo: https://github.com/emrekrt221-ship-it/adimaykkabi
Default: `main`; storefront branch `feat/mobile-storefront-foundation`; POS branch `feat/in-store-pos-reservations`.
PR #1: https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1 (draft; pending merge).
PR #5: https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5 (draft; BASE is storefront branch #1).
**Neither PR merged, no production/staging storefront live, ALL real sales disabled by default.**

## PR #1 (storefront and compliance scaffolding)
- Responsive theme, mobile navigation, search, cart link, WooCommerce templates.
- Reviewed-product catalog and direct Store API/HTTP access gating.
- WordPress/WooCommerce/MySQL CI, real HTTP checks, per-variant stock isolation baseline.
- Full 21-section plan in `docs/MASTER_PLAN_FULL.md`.
- Website checkout only after explicit private constant `ADIM_ENABLE_SALES` and all compliance prerequisites.

## PR #5 (physical store POS foundation, stacked on #1)
- New `wp-content/plugins/adim-operations/includes/pos.php`: real Woo orders, a unique per-request MySQL ledger (not stock SQL), WooCommerce InnoDB stock reservation, core reduction and confirmation.
- `includes/pos-admin.php`: responsive WooCommerce → Mağaza Satışı screen with SKU search, inventory preview, cash/external terminal record, anti-CSRF nonce, capability gate, mandatory physical payment checkbox, latest transaction summary.
- `ADIM_ENABLE_POS` is separate from web switch and defaults OFF; operator approval in private config required. Only reviewed, separately-stocked, non-backorder SKUs allowed.
- Safe catch behavior: after attempting reduction, do not silently delete an order; retain `needs_review` ledger status and order for reconciliation.
- `tests/pos-integration.php`, `tests/pos-admin-smoke.php`, `tests/pos-concurrency.sh` and supporting CLI workers.
- Detailed operations/risks in `docs/POS_OPERATIONS.md`.

## Verified by GitHub Actions
- POS and storefront static checks: run **39385002564 = success**.
- Actual WordPress+WooCommerce+MySQL integration, cashier UI and two-worker stock/idempotency race: run **39385002496 = success**.
- Earlier integration runs #39383645082 and #39383830778 failed during POS development; identified unsafe deletion after stock reduction, fixed conservatively. Later runs passed. Do not suppress known earlier failures.
- Latest documentation-only commit may trigger another CI run; check PR #5 Actions before any review.

## Remaining blockers
- No public production site, live checkout, payment gateway, shipping, ETBIS/business authorization or approved goods catalog.
- No true mobile browser/keyboard accessibility QA or production load/backup restore.
- Need operator/admin staff role separation, reconciliation process for stale POS requests, refunds/returns review, fiscal receipts/tax invoices, power/network failure tests and sustained online-vs-POS races.
- Existing inventory's trademark sourcing and rights unresolved; unauthorized replicas cannot be advertised or sold.
- POS is a **prototype only**, real cash/card transactions should NOT use this branch.

## Next tasks
1. Review stacked PR #1 then PR #5, avoid merge until acceptance; check latest CI.
2. Add POS manual reconciliation/returns/cashier role authorization and end-to-end browser testing (Issue #3).
3. Provision persistent private staging with backups, legal sample products, tax and settlement verification.
4. Test Store API POST cart/checkout, gateway sandbox, concurrent online orders vs physical stock before release.
5. Update STATUS/HANDOFF after each conversation.
