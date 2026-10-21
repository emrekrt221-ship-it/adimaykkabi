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
- [PR #11 — WooCommerce cart/checkout guidance + Store API guard](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/11): `feat/checkout-journey-and-guards` -> `feat/mobile-catalog-shopping-ux` (#10). This is the most advanced CUSTOMER UI branch; distinct from POS #5–#9.
- [PR #12 — Synthetic guest cart/shipping/browser QA](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/12): `test/guest-cart-shipping-flow` -> `feat/checkout-journey-and-guards` (#11). **Newest customer flow QA branch**; no real payment or shipping charges.
- [PR #13 — Woo native guest address/field validation](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/13): `test/guest-address-validation` -> `test/guest-cart-shipping-flow` (#12). **Latest customer flow QA**; shipping/payment orders remain disabled in production.

**All are drafts and unmerged at this snapshot.** GitHub's latest status takes precedence. Do not merge/rebase branches blindly; plan dependency order.

## Where to read current state
- On PR #9: [STATUS](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reviewed-restock/docs/STATUS.md), [SESSION_HANDOFF](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reviewed-restock/docs/SESSION_HANDOFF.md), [POS_RESTOCK](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-reviewed-restock/docs/POS_RESTOCK.md).
- On PR #10: [STATUS](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/mobile-catalog-shopping-ux/docs/STATUS.md), [SESSION_HANDOFF](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/mobile-catalog-shopping-ux/docs/SESSION_HANDOFF.md), [CATALOG_UX](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/mobile-catalog-shopping-ux/docs/CATALOG_UX.md).
- [Master plan (21 sections)](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/mobile-storefront-foundation/docs/MASTER_PLAN_FULL.md); [AGENTS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/main/AGENTS.md).

## Test evidence
- PR #9 real WooCommerce + MySQL once-only restock and previous POS/crash suite: [run 39563929519](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39563929519) PASS; [code checks 39563929497](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39563929497) PASS.
- PR #10 WooCommerce Beden filter and real Chromium responsive browser checks: [run 39565540676](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39565540676) **PASS** (360/390/768/1280px, menu, no horizontal overflow, product detail). Screenshots artifact ID 12329925612. Initial Chromium CI failures were caused by incorrectly configured temporary test-site asset URLs and have been corrected; this is automated test coverage, not production or operator visual approval.
- These are disposable CI environments, NOT actual shop, merchant account or continuous staging.

- PR #11 real WordPress+WooCommerce/MySQL + Chromium cart/checkout and native empty-cart redirect, plus closed-sales classic cart button removal: [run 39569174873](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39569174873) **PASS**; [code checks 39569174869](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39569174869) **PASS**. Customer checkout blocked by default; no payment gateway/merchant account configured.
- Newest UI handoff: [docs/SESSION_HANDOFF.md on PR #11](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/checkout-journey-and-guards/docs/SESSION_HANDOFF.md) and [CHECKOUT_JOURNEY.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/checkout-journey-and-guards/docs/CHECKOUT_JOURNEY.md).

- PR #12 synthetic Woo guest cart/TRY domestic shipping (CI-only ₺3,000 subtotal + ₺59 shipping), 390px Chromium populated cart & address and zero orders/gateways: [run 39573581625](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39573581625) **PASS**; [code checks 39573581461](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39573581461) **PASS**. Newer documentation-only HEAD CI may still be in progress.
- Newest client handoff: [PR #12 SESSION_HANDOFF.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/test/guest-cart-shipping-flow/docs/SESSION_HANDOFF.md) and [GUEST_SHIPPING_QA.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/test/guest-cart-shipping-flow/docs/GUEST_SHIPPING_QA.md).

- PR #13: native WC_Checkout required first/last name/street/email and malformed email/phone/country validation inside disposable WP CLI; Chromium first-name Woo-required markup and invalid email, with **no Place Order**; [PR #13](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/13). Latest CI should be reviewed at PR head before merge.
- [PR #13 current handoff](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/test/guest-address-validation/docs/SESSION_HANDOFF.md) and [field validation QA](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/test/guest-address-validation/docs/GUEST_ADDRESS_VALIDATION_QA.md).

## Release blockers
- Both POS and online sales are **OFF** by default; `ADIM_ENABLE_POS`, `ADIM_ENABLE_SALES`, `ADIM_ENABLE_RETURN_INTAKE`, `ADIM_ENABLE_RETURN_RESTOCK` must stay OFF for live use until acceptance.
- Product legal sourcing and trademark/design rights; no listings or sale of unauthorized counterfeit-marked goods.
- Payment provider, fiscal tax invoice/receipt, ETBIS/consumer/KVKK requirements, kargo/returns/payment reconciliation, sustained load, offsite backup/restore and independent security audit.
- Genuine payable web checkout/order/shipment/customer-facing workflows still incomplete; PR #12 only simulates a guest checkout without submitting an order.

## New-chat resume prompt
> GitHub'daki `emrekrt221-ship-it/adimaykkabi` projesine devam et. Önce ana dal `CURRENT_DEVELOPMENT.md` oku, açık PR #1/#5/#6/#7/#8/#9 ve paralel UI PR #10/#11/#12/#13'ü ve Actions sonuçlarını kontrol et. PR #9'daki stok işi ile PR #10'daki müşteri vitrini ayrı dallar; yapılanları tekrarlama. `docs/STATUS.md` ve `docs/SESSION_HANDOFF.md` dosyalarını ilgili aktif daldan oku. Eksik olan en yakın işi küçük PR'da geliştir, gerçek testleri çalıştır, devir belgelerini güncelle; gerçek ürünlerin satışını ve ödeme işlemlerini onaysız açma.
