# ADIM AYAKKABI — cross-chat handoff
Updated 2026-10-20. Read **live GitHub state first**.

## Current branches / PR
Repo https://github.com/emrekrt221-ship-it/adimaykkabi
Default `main`
PR #1: https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1 — `feat/mobile-storefront-foundation` -> `main`, draft.
PR #5: https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5 — `feat/in-store-pos-reservations` -> `feat/mobile-storefront-foundation`, draft/stacked.
Both were UNMERGED on last check. Never branch only from main assuming POS changes are merged.

## Next conversation steps
1. Read `AGENTS.md`, `docs/STATUS.md`, `docs/MASTER_PLAN_FULL.md`, `docs/POS_OPERATIONS.md`, `docs/DECISIONS.md`, `docs/ROADMAP.md` and `docs/RELEASE_CHECKLIST.md`.
2. Confirm branches/open PRs and GitHub Actions runs; the repository, not this summary, is authoritative.
3. Continue next incomplete approved infrastructure task in a small PR. Record **actual** test evidence and outstanding blockers. Update STATUS and SESSION_HANDOFF at end.
4. ChatGPT does not automatically continue work after a chat ends; user can ask "GitHub'daki adimaykkabi projesine devam et".

## Product/architecture
Single physical footwear shop in Turkey + national web sales, mobile-first WordPress/WooCommerce + MySQL/HPOS, lightweight dark/orange theme, optional Redis later; Cloudflare explicitly not used in v1. Size/color SKU stock is common between online and physical sales. Only legally authorized merchandise may be published/sold. User mentioned replica branded footwear; trademark authorization unresolved and no counterfeit goods may be listed or processed.

## Implemented and tested
PR #1: responsive storefront, catalog eligibility gate and Store API/HTTP tests with `ADIM_ENABLE_SALES` OFF by default.
PR #5: `includes/pos.php` creates WooCommerce cash/external-terminal order, reserves stock via `wc_reserve_stock_for_order`, reduces with `wc_reduce_stock_levels`, and records unique UUID/idempotency ledger in `wp_adim_pos_requests`. POS only enabled via private `ADIM_ENABLE_POS`, OFF by default.
`includes/pos-admin.php` creates WooCommerce → Mağaza Satışı secure responsive form, SKU/stock preview, checkout with confirmed physical collection, nonce/capability, admin sale history.
All failures after stock-reduction attempt preserve order and ledger `needs_review` status for inspection, not automatic delete or retry.
- Run **39385002564**: PHP/JS/isolated tests PASS.
- Run **39385002496**: real WordPress+WooCommerce+MySQL integration PASS, including POS 42/43 variant stock, duplicate idempotency, single last stock, admin form disabled/unapproved behavior, two concurrent independent workers competing over last stock and same request key.
- Initial POS failures **39383645082**, **39383830778** are fixed in later runs and documented; keep fail-closed logic.
- GitHub workflows: `.github/workflows/quality.yml` and `.github/workflows/woocommerce-integration.yml`.

## Unfinished, do not enable real selling
- Neither PR merged; staging not persistent, no real gateway/hosting, no mobile browser/visual QA, no automatic invoice/receipt.
- POS operational gaps: reconciliation for `needs_review`/crash/stale `processing` records, refund/return handling, least-privilege cashier role, robust interruption/backup tests.
- Online checkout gaps: POST Store API, payment sandbox, load tests, shipping/notifications.
- Legal rights/fiscal, consumer, data protection and banking approval are unmet.

## Immediate next tasks
1. Review latest CI and PR #1 and #5; merge only after real QA/approval.
2. Work on issue #3: human-readable POS reconciliation view + adjustment safeguards, crash consistency, audited returns.
3. Then private staging and mobile real-browser acceptance tests; Store API POST cart/checkout denial regressions.
4. Keep decisions, ROADMAP, STATUS and this file fresh after the session.
