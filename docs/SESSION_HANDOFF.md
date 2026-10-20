# ADIM AYAKKABI — cross-chat continuation
Updated 2026-10-20. Read GitHub live state first.

## Current repo / PR dependency chain
Repo: https://github.com/emrekrt221-ship-it/adimaykkabi
1. [PR #1](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1): mobile storefront `feat/mobile-storefront-foundation` -> `main`.
2. [PR #5](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5): POS and stock `feat/in-store-pos-reservations` -> `feat/mobile-storefront-foundation`.
3. [PR #6](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/6): limited cashier roles and reconciliation `feat/pos-reconciliation-and-cashier-role` -> `feat/in-store-pos-reservations`.
These are **stacked draft PRs**, NOT merged as of this note; do NOT build new features directly on `main` without reviewing dependency order.

## How the next ChatGPT session resumes
1. GitHub connector: read main `CURRENT_DEVELOPMENT.md`, then THIS branch's `AGENTS.md`, `docs/STATUS.md`, `docs/MASTER_PLAN_FULL.md`, `docs/POS_OPERATIONS.md`, `docs/POS_RECONCILIATION.md`, `docs/ROADMAP.md`, `docs/DECISIONS.md`, `docs/RELEASE_CHECKLIST.md`.
2. Inspect branch, open PRs, commit status, GitHub Actions runs and issue #3. GitHub current state overrides this snapshot.
3. Continue next unfinished task, test with actual WP+WooCommerce/MySQL CI and record truthful status. Never merge unreleased payment/POS flow blindly.
4. Update STATUS and SESSION_HANDOFF before stopping. Changes only happen while chat is actively working.

## Architectural decisions
One Reyhanlı footwear retailer with national web sales; WordPress + WooCommerce/MySQL/HPOS and lightweight responsive theme. Shared per-size/color stock, low-cost single-store architecture, no Cloudflare in v1. Separate operations plugin. Sales are default disabled by `ADIM_ENABLE_SALES` and `ADIM_ENABLE_POS`, set only in private server config after full approvals. ONLY merchandise with legal sales authorization, not counterfeit branded replicas.

## Already implemented on PR #1
Responsive storefront, Woo product eligibility checks, public Store API/HTML product test coverage, PHP/JS and real WP integration CI.

## Already implemented on PR #5
Idempotent physical sale orders with unique MySQL request-key ledger; Woo stock reserve/reduce (not direct SQL stock editing), per-size variation SKU, cash/external terminal record, responsive cashier search/form, nonce and admin permission. Concurrency tests against two independent cashier workers with one remaining unit.

## Just implemented on PR #6
- `pos-roles.php`: `adim_cashier` role (read + adim_pos_sell), no manage_woocommerce rights; admin/shop_manager gain dedicated POS capability.
- `pos-reconciliation.php`: manager-only pending queue; 15-minute stale processing threshold; matching order and 15+ character rationale required for acknowledged review; WooCommerce order notes audit; orphaned requests cannot be dismissed and **no inventory or payment modifications** occur in review.
- Cashier menu changed to independent WordPress admin menu available only with `adim_pos_sell`.
- Test `tests/pos-reconciliation.php` added to real WooCommerce integration workflow.
- Detailed instructions in `docs/POS_RECONCILIATION.md`.

## CI evidence
- [Run 39391233300](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391233300) SUCCESS: real WP/Woo/MySQL, order, cashier, role, reconciliation, POS stock race and HTTP.
- [Run 39391233199](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391233199) SUCCESS: PHP/JS and isolated tests.
- These results precede some documentation-only commits. Always inspect latest checks.

## Remaining, high priority
- Persistent staging and device/browser tests; never assert production readiness based on CI.
- Failure injection after order creation, reserved stock, decrement and status update; ensure ledger/out-of-sync recovery behavior.
- Return/refund stock safety and fiscal receipts; cashier role cannot refund or edit stock.
- Store API POST and online checkout vs physical POS concurrency.
- Payment processor approval, lawful sourcing of real catalog, applicable ETBIS/KVKK/consumer/tax compliance.
- Both switches default OFF, no live seller account or payment keys saved.
