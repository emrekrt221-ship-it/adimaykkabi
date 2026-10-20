# ADIM AYAKKABI — Current Development Pointer
Updated: 2026-10-20. **Read this file FIRST in any new ChatGPT development conversation.**

## Actual code is in stacked draft PRs (NOT in main)
1. [PR #1 — Storefront](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1): `feat/mobile-storefront-foundation` → `main`.
2. [PR #5 — In-store POS and shared variation inventory](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5): `feat/in-store-pos-reservations` → `feat/mobile-storefront-foundation`.
3. [PR #6 — Cashier role + manager reconciliation](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/6): `feat/pos-reconciliation-and-cashier-role` → `feat/in-store-pos-reservations`.

**All three are draft/unmerged as of this snapshot.** Inspect live GitHub status and current base branches before editing or merging. Do not restart coding on main or duplicate already completed work.

## Authoritative docs on deepest development branch
- [AGENTS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reconciliation-and-cashier-role/AGENTS.md)
- [docs/STATUS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reconciliation-and-cashier-role/docs/STATUS.md)
- [docs/SESSION_HANDOFF.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reconciliation-and-cashier-role/docs/SESSION_HANDOFF.md)
- [docs/MASTER_PLAN_FULL.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reconciliation-and-cashier-role/docs/MASTER_PLAN_FULL.md)
- [docs/POS_RECONCILIATION.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reconciliation-and-cashier-role/docs/POS_RECONCILIATION.md)

## Last confirmed test evidence
- [Run 39391233300](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391233300) — SUCCESS real WordPress/WooCommerce/MySQL product, cashier, manager review, concurrency and HTTP tests.
- [Run 39391233199](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391233199) — SUCCESS PHP/JS syntax and isolated hook tests.
- These were checked before the latest documentation-only edits; check latest PR head before QA merge. Tests are **not** a live staging or sales authorization.

## Explicit unfinished / blocked
- Crash injection/repair of interrupted POS writes, refunds/returns with restock inspection and complete fiscal/accounting support.
- Browser/mobile/device and accessibility QA, persistent private staging with backup/restore, online+POS checkout contention.
- Verified online payment gateway and shipping/invoice integrations, lawful inventory/brand rights, applicable ETBIS/KVKK/consumer compliance and merchant approval.
- **Both `ADIM_ENABLE_POS` and `ADIM_ENABLE_SALES` are OFF by default.** This code is not production-ready. Counterfeit branded goods must NOT be listed or sold.

## Next-chat message
> GitHub'daki `emrekrt221-ship-it/adimaykkabi` projesine devam et. Önce ana daldaki CURRENT_DEVELOPMENT.md dosyasını, sonra PR #6 dalındaki AGENTS.md, docs/STATUS.md, docs/SESSION_HANDOFF.md ve POS_RECONCILIATION.md dosyalarını oku. Açık PR #1/#5/#6 ve CI testlerini kontrol et, en yakın eksik geliştirmeyi yeni küçük bir PR ile yap, gerçek testleri çalıştır ve devir dosyalarını güncelle. Canlı satış veya yasal uygunluğu belirsiz ürünleri açma.
