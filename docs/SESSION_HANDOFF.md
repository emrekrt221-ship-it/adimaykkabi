# ADIM AYAKKABI — cross-chat continuation handoff
Updated: 2026-10-20
Repository: https://github.com/emrekrt221-ship-it/adimaykkabi
Branch under development: `feat/mobile-storefront-foundation`
Draft PR #1: https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1 (targets `main`; not merged as of this handoff).

## For the next ChatGPT session
1. Connect GitHub; read `AGENTS.md`, `docs/STATUS.md`, this file, `docs/MASTER_PLAN_FULL.md`, `docs/DECISIONS.md`, `docs/ROADMAP.md`, and `docs/RELEASE_CHECKLIST.md`.
2. Check GitHub branch and PR status, CI results and open issues #2, #3, #4 before editing. GitHub's current state overrides these historical statements.
3. Continue the next unfinished task, commit tested changes on a feature branch and maintain an auditable PR. No background execution between chat sessions.
4. Update `docs/STATUS.md` + this handoff with exact test evidence and next step every session.

## Product scope and decisions
Single physical shoe shop, mobile-first WooCommerce storefront and shared physical/online variation stock by model/color/size, Turkish consumer sales, black/orange/white. MySQL + HPOS. Lightweight custom PHP theme + isolated operations plugin. No Cloudflare initially. Avoid unnecessary plugins and cost.
Sell **only legally authorized goods**. User mentioned replica branded footwear; trademark compliance unresolved, so no advertising/listing/sale of counterfeit goods. No production payment until legal and provider approval. No secrets, card data or customer PII in repo.

## Changes already present in PR #1
- Theme `wp-content/themes/adim` with responsive header/navigation/search/cart, category/new arrival landing, product and WordPress page templates.
- Operations plugin v0.2.0: manual product-review flag, parent variation check, disable purchase unless reviewed and global sales switch enabled, product visibility/queries, add-to-cart and existing-cart checks, unreviewed direct URL 404 handling. **Default switch OFF**; requires private server constant `ADIM_ENABLE_SALES` set to true for test/launch only after acceptance.
- `tests/operations-smoke.php` and Actions workflow execute isolated PHP/Woo mock tests with switch both OFF and ON.
- Complete source plan in `docs/MASTER_PLAN_FULL.md`; release checklist, decisions and roadmap.

## Test evidence
- **Run 39380197239 = success**: PHP syntax, JS syntax, disabled/enabled isolated operations tests, secrets filename check.
- PHP 8.4 local mock tests: pass both switch paths.
- No real WordPress/WooCommerce, WC Store API/REST, browser, payments, deployment or concurrency tests have happened. Cannot call current system production ready.
- All PR #1 checks should be re-read after further commits.

## Immediate next steps
A. Review/QA PR #1, then provision a private WordPress+WooCommerce staging environment.
B. Exercise unauthorized/approved products in native Woo catalog, Store API/REST, product direct URL, variations, carts, all payment paths. Correct any bypass; manual approval is not legal verification.
C. Implement inventory/POS and test last-unit stock race and idempotency; only then payment gateway sandbox/shipping.
D. Keep master plan and status synchronized. User can start next chat: "GitHub'daki adimaykkabi projesine devam et, AGENTS.md ve SESSION_HANDOFF.md'yi oku."
