# ADIM AYAKKABI — cross-chat handoff
Last updated 2026-10-20

## Project and current branch
Repo: https://github.com/emrekrt221-ship-it/adimaykkabi
Default branch: `main`
Active code branch: `feat/mobile-storefront-foundation`
Draft PR #1: https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1 (unmerged at last check)

## Instructions for the next conversation
1. Inspect live GitHub repo first; read `AGENTS.md`, `docs/STATUS.md`, this handoff, `docs/MASTER_PLAN_FULL.md`, `docs/DECISIONS.md`, `docs/ROADMAP.md`, `docs/RELEASE_CHECKLIST.md`.
2. Inspect draft PR #1, open issues #2/#3/#4 and latest GitHub Actions runs before editing; GitHub has precedence over stale chat summaries.
3. Continue with the next uncompleted QA or development task; make focused commits/PRs, verify tests and update STATUS/HANDOFF.
4. Never assume continued execution in the background. A new session only develops when explicitly requested.

## Architecture
Mobile-first WordPress + WooCommerce, MySQL, HPOS, lightweight custom theme `wp-content/themes/adim` and isolated `wp-content/plugins/adim-operations`. Single physical shoe store and online shop, shared size/color stock. Black/orange/light palette. No Cloudflare in v1. Remote payment provider after approval. GitHub stores only custom source and docs, never secrets/customer data.

## Implementation to date
- Responsive theme with menu, search, cart, shop, category/new arrival, Woo pages.
- Authorization flag `_adim_sale_approved`; parent status governs child variations. Sale switch `ADIM_ENABLE_SALES` defaults disabled, must be set only in private server configuration AFTER approvals.
- Catalog/shortcode/REST/Store API guard and denial of unreviewed single product detail; OR meta filters nested under required AND approval, cart/checkout revalidation.
- `tests/operations-smoke.php` plus actual `tests/integration-wordpress.php` and `tests/storefront-http.sh` run with `.github/workflows/quality.yml` and `.github/workflows/woocommerce-integration.yml`.
- Size-42 and size-43 stock isolation tested via real WooCommerce stock adjustment (sequential only).
- Baseline plan `docs/MASTER_PLAN_FULL.md`, this handoff, release checklist and roadmap.

## Exact tests verified
- 39381250677 SUCCESS: direct Store API block for unauthorized product.
- 39381497455 SUCCESS: nested AND authorization with OR existing meta query.
- 39381786625 SUCCESS: real Woo size variation separate stock.
- 39382267922 SUCCESS: WordPress/WooCommerce integration and real HTTP homepage/CSS/allowed/denied products (canonical redirects followed).
- 39382267912: confirm latest static status before merge.
- Previous runs 39380968767 and 39382080868 FAILED and led to code/test corrections; do not describe those as passed.

## Safety and launch blockers
The user mentioned sourcing branded replica shoes. Do not list or enable checkout for counterfeit trademarked products; sourcing/rights must be verified. Manual flag does NOT establish legal entitlement. ETBIS, consumer law, KVKK, gateway approval remain pending. Sales OFF by default. No production hosting or customer payment set up.

## Next precise task
1. Recheck latest CI for PR #1.
2. Add browser integration tests (desktop/mobile) and Store API cart/checkout bypass tests. Explore possible admin API access boundaries.
3. Implement in-store variation stock sync with atomic/idempotent transactions and concurrency tests, not just sequential stock adjustment.
4. Review draft PR #1 rather than auto-merging, then start small separate PR for POS.
5. At every stopping point update STATUS and SESSION_HANDOFF with truthful tests/blocked tasks.

Copy/paste into new ChatGPT conversation: "GitHub'daki emrekrt221-ship-it/adimaykkabi projesine devam et. Önce AGENTS.md ve docs/SESSION_HANDOFF.md'yi oku, açık PR ve testlere bak, sonra sıradaki işi geliştir."

## Parallel storefront UI branch (2026-10-21)
- PR #10: https://github.com/emrekrt221-ship-it/adimaykkabi/pull/10 (`feat/mobile-catalog-shopping-ux` -> `feat/mobile-storefront-foundation`). **Independent from** PR #9 return-restock branch based on PR #8. Do not merge/rebase one blindly on the other; inspect live PR states.
- New theme `inc/catalog.php`: WooCommerce global Beden / `pa_beden` taxonomy size filtering, validated request, AND safety of existing filters, accessible no-JavaScript form and neutral fitting guidance.
- CSS improves mobile product cards, gallery/summary, variation/select, Woo sort and accessible controls. Tests: `tests/catalog-ux.php` plus Chromium `tests/browser-mobile.mjs` in updated Woo integration workflow. See `docs/CATALOG_UX.md`.
- [Real Woo catalog CI 39564596521](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39564596521) PASS. Browser workflow install/run was pending at this snapshot; check current Actions and artifacts before making claims.
- Next: fix any real browser UI failure; add real-product detail variation/filter/checkout flow tests with legally authorized synthetic products, then persist staging and performance audit. Keep switches OFF.

## Final browser QA status of PR #10
- [Run 39565540676](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39565540676) SUCCESS for PHP/WordPress/Woo integration, Chromium mobile+desktop widths, navigation and product-detail disclosure. Screenshots artifact ID 12329925612.
- Root cause of earlier browser failures: disposable WP CLI installation wrote script URLs under unexpected `/adim-wordpress/`; the CI script sets `home` and `siteurl` to `http://127.0.0.1:8888`, and navigation initialization waits for DOM readiness. Do not change production URLs based on these test-specific corrections.
- Browser CI uses Playwright 1.56.1 Chromium, checks 360/390/768/1280px, no horizontal page overflow and zero script errors; it is an automated smoke test, not full usability/SEO/performance certification.
- Follow-up task: inspect captured screenshots, refine actual product photography and mobile category/checkout layout on staging; keep sales disabled.
