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
