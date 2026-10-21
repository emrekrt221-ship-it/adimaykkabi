# ADIM AYAKKABI — Development index

Updated: 2026-10-21. **Read here before continuing in a new ChatGPT session.**

## IMPORTANT: two PARALLEL work tracks, not one linear branch
**Core store & physical POS / returns (stacked DRAFT PRs, unmerged):**
1. [PR #1 — responsive WooCommerce storefront foundation](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1): `feat/mobile-storefront-foundation` -> `main`.
2. [PR #5 — shared per-size physical store POS](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5): `feat/in-store-pos-reservations` -> `feat/mobile-storefront-foundation`.
3. [PR #6 — cashier role + manager reconciliation](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/6): `feat/pos-reconciliation-and-cashier-role` -> `feat/in-store-pos-reservations`.
4. [PR #7 — crash-recovery audit](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/7): `test/pos-crash-recovery-audit` -> `feat/pos-reconciliation-and-cashier-role`.
5. [PR #8 — quarantined return intake + inspection](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/8): `feat/pos-return-inspection` -> `test/pos-crash-recovery-audit`.
6. [PR #9 — manager-approved, once-only return restock](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/9): `feat/pos-reviewed-restock` -> `feat/pos-return-inspection`.

**Customer-facing web UI (independent DRAFT PR):**
- [PR #10 — mobile catalog and size filtering](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/10): `feat/mobile-catalog-shopping-ux` -> `feat/mobile-storefront-foundation` (PR #1). This branch deliberately excludes deep POS/returns code.

**All are drafts and unmerged at this snapshot.** GitHub's latest status takes precedence. Do not merge/rebase branches blindly; plan dependency order.

## Where to read current state
- On PR #9: [STATUS](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reviewed-restock/docs/STATUS.md), [SESSION_HANDOFF](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reviewed-restock/docs/SESSION_HANDOFF.md), [POS_RESTOCK](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reviewed-restock/docs/POS_RESTOCK.md).
- On PR #10: [STATUS](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/mobile-catalog-shopping-ux/docs/STATUS.md), [SESSION_HANDOFF](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/mobile-catalog-shopping-ux/docs/SESSION_HANDOFF.md), [CATALOG_UX](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/mobile-catalog-shopping-ux/docs/CATALOG_UX.md).
- [Master plan (21 sections)](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/mobile-storefront-foundation/docs/MASTER_PLAN_FULL.md); [AGENTS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/main/AGENTS.md).

## Test evidence
- PR #9 real WooCommerce + MySQL once-only restock and previous POS/crash suite: [run 39563929519](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39563929519) PASS; [code checks 39563929497](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39563929497) PASS.
- PR #10 WooCommerce Beden/size query and reviewed inventory tests: [run 39564596521](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39564596521) PASS. Real Chromium mobile browser tests have been added; **check the latest PR #10 run and fix any failures before claiming visual/browser QA passed**.
- These are disposable CI environments, NOT actual shop, merchant account or continuous staging.

## Release blockers
- Both POS and online sales are **OFF** by default; `ADIM_ENABLE_POS`, `ADIM_ENABLE_SALES`, `ADIM_ENABLE_RETURN_INTAKE`, `ADIM_ENABLE_RETURN_RESTOCK` must stay OFF for live use until acceptance.
- Product legal sourcing and trademark/design rights; no listings or sale of unauthorized counterfeit-marked goods.
- Payment provider, fiscal tax invoice/receipt, ETBIS/consumer/KVKK requirements, kargo/returns/payment reconciliation, sustained load, offsite backup/restore and independent security audit.
- Genuine web checkout/order/shipment/customer-facing workflows still incomplete.

## New-chat resume prompt
> GitHub'daki `emrekrt221-ship-it/adimaykkabi` projesine devam et. Önce ana dal `CURRENT_DEVELOPMENT.md` oku, açık PR #1/#5/#6/#7/#8/#9 ve paralel UI PR #10'u ve Actions sonuçlarını kontrol et. PR #9'daki stok işi ile PR #10'daki müşteri vitrini ayrı dallar; yapılanları tekrarlama. `docs/STATUS.md` ve `docs/SESSION_HANDOFF.md` dosyalarını ilgili aktif daldan oku. Eksik olan en yakın işi küçük PR'da geliştir, gerçek testleri çalıştır, devir belgelerini güncelle; gerçek ürünlerin satışını ve ödeme işlemlerini onaysız açma.
