# ADIM AYAKKABI — cross-chat handoff

Date: 2026-10-20
Repository: https://github.com/emrekrt221-ship-it/adimaykkabi
Default branch: `main`
Feature branch: `feat/mobile-storefront-foundation`
Draft PR: https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1

## START HERE in next ChatGPT session
1. Connect GitHub and open THIS repository.
2. Read `AGENTS.md` and `docs/STATUS.md` before any edits.
3. Read original 21-section requirements in `docs/MASTER_PLAN_FULL.md`, and then `docs/MASTER_PLAN.md`, `docs/ARCHITECTURE.md` if present, `docs/DECISIONS.md`, `docs/ROADMAP.md`, `docs/RELEASE_CHECKLIST.md`.
4. Inspect PR #1 and CI, and read GitHub issues #2, #3 and #4. Confirm branch/merge state; never rely on this handoff alone if GitHub changed.
5. Work on the next unfinished task in a small PR; run actual tests and record results. Update this file and STATUS as last step.
6. The user can ask a new chat "GitHub'daki adimaykkabi projesine devam et; AGENTS.md ve SESSION_HANDOFF.md'yi oku". Work happens only while a chat is actively executing; no automatic background continuity.

## Product
Single physical footwear shop in Reyhanlı, selling eligible products through a mobile-first WooCommerce site across Turkey. Stock by model/color/size shared with physical retail. WooCommerce HPOS/MySQL, lean custom PHP theme, isolated operations plugin. Black #151515, orange #F57C00, off-white #F7F7F5. Cloudflare deliberately omitted in v1. Low cost but reliable SSL, backup, gateway sandbox and compliant release.

## What changed in PR #1
- Theme: functions, header, homepage, footer, new `assets/css/storefront.css`, `assets/js/navigation.js`, `woocommerce.php`, `page.php`.
- Plugin: `wp-content/plugins/adim-operations/adim-operations.php` first prototype product-review/purchasability guard (not a full compliance mechanism).
- CI: `.github/workflows/quality.yml`.
- Docs: `docs/MASTER_PLAN_FULL.md`, `docs/RELEASE_CHECKLIST.md`, updated ROADMAP/STATUS/DECISIONS/DEVELOPMENT/HANDOFF.
- Issues: #2 staging + responsive QA; #3 variant stock + store POS; #4 legal approval gate/API hardening.

## Verified and unverified
- GitHub Actions workflow run 39343272480 PASSED PHP and JS lint checks.
- Full WordPress/WooCommerce staging and browser test NOT DONE. Last-stock concurrency, card payment and shipping NOT DONE.
- PR #1 draft, not merged. Always check current CI status and review.
- User acquired replica footwear; their legal trademark authorization is unresolved. No counterfeit-marked merchandise may be advertised/listed/sold. No real checkout enablement before merchant, regulatory and payment approvals.
- Never store secrets, production customer data, actual cards, or payment keys in repo.

## Next precise action
Open PR #1, ensure latest CI is green, perform WooCommerce staging QA with authorized sample products. Review catalog restriction in WC Store API, product REST and direct URL before launch. Then implement physical POS inventory with idempotent stock operation and tests.

## Always update
`docs/STATUS.md`: truthful completed/blocked/tested.
`docs/SESSION_HANDOFF.md`: context, current branch/PR and next concrete step.
`docs/DECISIONS.md`: changes to architecture.
`docs/ROADMAP.md`: only check off when accepted/tested.
