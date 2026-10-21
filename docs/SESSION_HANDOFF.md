# ADIM AYAKKABI — next-chat handoff
Updated: 2026-10-21
Repository: https://github.com/emrekrt221-ship-it/adimaykkabi

## Start here
1. **Check current GitHub**, not remembered progress. Main `CURRENT_DEVELOPMENT.md` points to active branch.
2. Four stacked draft PRs, none merged at last check: [#1 storefront](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1) → [#5 POS stock](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5) → [#6 roles/reconciliation](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/6) → [#7 crash audit](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/7). Most complete branch: `test/pos-crash-recovery-audit`, based on `feat/pos-reconciliation-and-cashier-role`.
3. Read `AGENTS.md`, this `docs/SESSION_HANDOFF.md`, `docs/STATUS.md`, `docs/MASTER_PLAN_FULL.md`, `docs/POS_OPERATIONS.md`, `docs/POS_RECONCILIATION.md`, `docs/POS_CRASH_RECOVERY.md`, `docs/ROADMAP.md`, `docs/DECISIONS.md` and `docs/RELEASE_CHECKLIST.md`.
4. Review PR/CI and choose next incomplete task; work on new focused branch; test before claims; update STATUS/HANDOFF at session end. Never merge draft production POS/checkout flows without acceptance.

## Architecture and restrictions
Single-store footwear e-commerce: lightweight WordPress + WooCommerce, MySQL/HPOS, shared model/color/size SKU stock with physical shop. Black/orange/white, no Cloudflare in v1. `ADIM_ENABLE_POS` and `ADIM_ENABLE_SALES` are OFF by default. POS uses Woo order + Woo reserve/reduce + UUID idempotency ledger; cashier has least-privilege role, Woo manager can review interrupted requests. No live payments or shipping. Only legally authorized merchandise; user mentioned branded replicas with unverified legal entitlement. No selling/listing counterfeit trademarked products.

## Prior PRs
- #1 mobile storefront/theme, product guard, real WP/Woo HTTP integration.
- #5 Woo-backed physical POS, cashier screen, per-size stock and concurrent two-worker last-unit tests.
- #6 `adim_cashier` role, manager-only reconciliation, audit notes and late worker protection. Run 39392125050 SUCCESS.

## New PR #7 work
- `wp-content/plugins/adim-operations/includes/pos.php` has test-only crash checkpoints (requires explicit WP CLI/test flag), enforces order/ledger link success.
- `wp-content/plugins/adim-operations/includes/pos-reconciliation.php` adds manager-only read-only orphan order candidate detection; persists review evidence in WC order notes BEFORE ledger acknowledgement.
- `tests/pos-crash-setup.php`, `tests/pos-crash-worker.php`, `tests/pos-crash-verify.php`, `tests/pos-crash-tests.sh` run six separate abrupt process termination scenarios at ledger/order/reservation/stock/status boundaries. CI checks durable MySQL state, no duplicate orders, stock quantities, manager review and orphan handling.
- Added tests for forbidden cashier lookup in `tests/pos-reconciliation.php` and Woo orphan diagnosis in crash tests.
- `docs/POS_CRASH_RECOVERY.md` details expected results and manual recovery.

## Confirmed CI
- 39559907569 real WooCommerce + six crash points: SUCCESS.
- 39560297893 real WooCommerce integration, orphan query and note-first review: SUCCESS.
- 39560297923 PHP/JS and isolated checks: SUCCESS.
- Documentation-only latest commits may start newer runs; check before merge.

## Next precise tasks
1. Validate current #7 CI and review diff; keep all four PRs DRAFT.
2. Create guarded, audited refund/return + inspect-before-restock design and tests; manager only, no automatic refund or stock changes until explicit process.
3. Run mixed Woo Store API online checkout vs physical POS last-stock tests, including new UUID client retry and hold expiry.
4. Plan persistent staging, device browser tests, backup/restore, merchant/legal inventory checks.
5. Update repo documents. New chat prompt: "GitHub'daki adimaykkabi projesine devam et. CURRENT_DEVELOPMENT.md ve PR #7 dalındaki docs/SESSION_HANDOFF.md dosyalarını oku; açık PR/CI'ı doğrula."

## 2026-10-21 newest development — PR #8
- Check [draft PR #8](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/8), branch `feat/pos-return-inspection`, base `test/pos-crash-recovery-audit`. Its base PR #7 depends on #6 -> #5 -> #1. All draft/unmerged.
- New `includes/pos-returns.php`: cashier intake of WooCommerce physical POS return, idempotency UUID, per-order-item named MySQL lock, sold-quantity upper bound, starts as quarantined. Manager only can record accepted/rejected review, with persisted Woo order note; **does not refund or restock**.
- `includes/pos-return-admin.php`: cashier and manager protected WordPress screens. `ADIM_ENABLE_RETURN_INTAKE` must explicitly be true to receive items; default OFF.
- Test `tests/pos-returns.php` covers positive/negative roles, duplicate, quantity, no stock/payment changes and UI markup. See `docs/POS_RETURNS.md`.
- Initial real WooCommerce test run 39561531178 was green before UI; verify latest PR HEAD CI and fix failures. Do not merge/deploy unfinished functionality.
- Next: secure real-browser flows for POS and returns, payment/fiscal and audited refund/restock separately. Merchandise licensing/brand sourcing and launch prerequisites outstanding.

## Most recent PR #9: manager-confirmed inspected stock restoration
- Open [draft PR #9](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/9), branch `feat/pos-reviewed-restock`, based on PR #8 branch `feat/pos-return-inspection`. Stacked chain #1 → #5 → #6 → #7 → #8 → #9, NONE merged at last check.
- Manager-only `includes/pos-restock.php`: explicit separate `ADIM_ENABLE_RETURN_RESTOCK` flag (default OFF), unique return claim, WooCommerce native stock increment for accepted inspected SKU; replays yield existing result; unknown outcome never automatically retries. Woo refund entries and unauthorized/stock-untracked goods are rejected.
- Manager screen `includes/pos-restock-admin.php` requires stock-count & condition checkboxes, 15+ character note, nonce; completed or uncertain movement is read-only. Cashier cannot restock.
- Tests `tests/pos-restock.php`: true WooCommerce sale → return intake → manager inspection → explicit stock increase; repeats, blocked permissions, refunded order unchanged, denied/revoked product, failed movement and admin screen. Added to workflow. [CI 39563649293](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39563649293) SUCCESS (pre-doc commits). See `docs/POS_RESTOCK.md`.
- Next: recheck latest PR #9 tests; focus customer-facing mobile WooCommerce storefront improvements as a separate PR based on `feat/mobile-storefront-foundation`, not on deep POS stack; add real phone/browser tests and Woo checkout sandbox only for legally authorized goods.
