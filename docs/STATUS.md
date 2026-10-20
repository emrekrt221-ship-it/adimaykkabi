# ADIM AYAKKABI — Project status
Updated: 2026-10-20
Repository: https://github.com/emrekrt221-ship-it/adimaykkabi

## GitHub branches and pull requests
- Draft [PR #1](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1): `feat/mobile-storefront-foundation` -> `main` (mobile WooCommerce storefront and compliance guard).
- Draft [PR #5](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5): `feat/in-store-pos-reservations` -> `feat/mobile-storefront-foundation` (stock-backed in-store checkout).
- Draft [PR #6](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/6): `feat/pos-reconciliation-and-cashier-role` -> `feat/in-store-pos-reservations` (this session, cashier role and manual reconciliation).
- **All three are still draft and unmerged at the last check.** The main branch does NOT yet contain these feature implementations.

## Implemented on PR #6
- Dedicated `adim_cashier` role with `read` and `adim_pos_sell`, no WooCommerce manager rights.
- Manager/admin allowed in-store sales through dedicated permission; cashier POS menu is available without opening WooCommerce management.
- `POS İnceleme` manager-only page shows old processing entries (15+ minutes) and needs-review entries.
- Manager can acknowledge a matching POS WooCommerce order only with an explanatory review note. Request ID + WooCommerce order origin must match; orphan request cannot be dismissed. Notes are saved to WooCommerce order history.
- Review does not modify inventory, charge a customer, mark order paid, refund or restore stock.
- Real WooCommerce tests for roles, cashier order creation, denied manager actions, manager review, audit notes and orphan/stale processing safeguards.
- Documentation: `docs/POS_RECONCILIATION.md`.

## Verified
- GitHub Actions run [39391233300](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391233300): **SUCCESS**, disposable WordPress+WooCommerce+MySQL, website HTTP, physical sale, cashier rights, manager reconciliation, stock race.
- GitHub Actions run [39391233199](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391233199): **SUCCESS**, PHP/JS syntax and isolated hooks.
- Above results apply to implementation commit `c555b145...`; subsequent documentation commits may have new runs to check.
- Previous POS test failures in development were resolved; refer to prior handoff for details.

## Still missing
- Live deployment, persistent staging, real mobile browser and security/restore testing.
- Power/network interruption recovery, orphan ledger investigation process, audited refund/return/stock adjustment.
- Online checkout stock reservation failure/reversal, Store API POST coverage and mixed online+physical stock races.
- Customer payments, verified gateway callbacks, fiscal receipt/invoice, shipping, operator authentication/audit.
- Legally authorized product stock and rights review; ETBIS/tax/consumer/KVKK and merchant provider onboarding.

## Critical launch rule
Both `ADIM_ENABLE_SALES` and `ADIM_ENABLE_POS` are disabled by default. Real cashier and customer sales must NOT be enabled before the full launch checklist is accepted. Unlicensed/counterfeit branded merchandise must not be listed or sold.

## Next engineering task
1. Check latest PR #6 CI and review stacked PR diffs (PR #1 -> #5 -> #6).
2. Build failure-injection integration tests that interrupt each stage and ensure recovery/no duplicate decrement.
3. Plan audited returns/refunds with stock inspection; cashier must not have refund/manager rights.
4. Add persistent private staging and actual mobile browser QA. Keep docs synchronized after every coding session.

## Latest reconciliation-race hardening (2026-10-20)
- Sale-worker ledger completion/failure now only updates requests still in `processing`, so a delayed worker cannot replace a manager's `reviewed` status.
- [CI 39391923740](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391923740): real WooCommerce integration and concurrent POS stock tests **SUCCESS** for this hardening commit.
- Documentation-only commits after this run require final PR check before merge.
