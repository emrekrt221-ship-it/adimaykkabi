# Session handoff — 2026-10-20

Repository: https://github.com/emrekrt221-ship-it/adimaykkabi
Default branch: `main`
Current work branch: `feat/mobile-storefront-foundation`

## Instruction for next ChatGPT conversation
1. Use GitHub connector to read the **live repo state**, not remembered progress.
2. Read `AGENTS.md`, `docs/MASTER_PLAN.md`, `docs/DECISIONS.md`, `docs/ROADMAP.md`, `docs/STATUS.md`, `docs/RELEASE_CHECKLIST.md`, then this file.
3. Inspect current branch, open PRs and GitHub Actions/checks; never assume a PR is merged.
4. Continue the next unfinished task; prefer small reviewed PRs. Do not overwrite changes made by others.
5. Run tests, record exact results and update STATUS + SESSION_HANDOFF before stopping.
6. No promise of autonomous background execution: another chat continues only when user opens it and requests continuation.

## Invariants and decisions
- Single footwear store in Turkey, physical stock shared with ecommerce by product/color/size.
- Mobile-first WooCommerce + MySQL/HPOS, minimal theme + isolated plugin; no Cloudflare in v1.
- Colors black #151515, orange #F57C00, off-white #F7F7F5.
- Payment through approved provider; never store card data/production credentials in Git.
- Only legally authorized goods can be published and sold. Counterfeit trademarked shoes cannot go live. Payment approval, legal/ETBIS and privacy gates block launch.
- Prior full plan: ADIM AYAKKABI E-ticaret Ana Plan v1.0 (20 Oct 2026); `docs/MASTER_PLAN.md` and `docs/ROADMAP.md` are the repo canonical synopsis.

## Work completed on branch
- Updated theme: `functions.php`, `header.php`, `front-page.php`, `footer.php`.
- New files: `assets/css/storefront.css`, `assets/js/navigation.js`, `woocommerce.php`, `page.php`.
- Added prototype `wp-content/plugins/adim-operations/adim-operations.php` (manual product authorization flag, Woo purchasability and ordinary shop query restrictions). **Do not treat as proven airtight.**
- Added `.github/workflows/quality.yml` (PHP lint + JS syntax), `docs/RELEASE_CHECKLIST.md`.
- Updated documentation and handoff for multi-chat continuity.

## Tests
GitHub writes succeeded. PHP syntax, WordPress installation/integration, mobile browser, gateway, stock race, REST and Store API product gate tests have **not been executed in this session**. Review CI results after PR.

## Next task order
P0: CI results -> correct any errors -> manual/automated WordPress staging smoke test -> strengthen compliance gate through all APIs and URL paths.
P1: Product/catalog/variations and stock tests -> simple in-store POS entry (idempotent, atomic decrement with audit) -> checkout sandbox and shipping, returns.
P2: Performance, backups, privacy, SEO, pre-launch review.

## Files to keep fresh every session
`docs/STATUS.md` = current truthful progress
`docs/SESSION_HANDOFF.md` = copyable context and next concrete action
`docs/DECISIONS.md` = append-only ADR decisions
`docs/ROADMAP.md` = priorities and milestone checkboxes
`AGENTS.md` = binding engineering instructions
