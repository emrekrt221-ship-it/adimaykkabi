# ADIM AYAKKABI — Active development index

Updated: 2026-10-21. **Read this file first in a new development chat.**

## Stacked unmerged draft PRs (bottom → top)
1. [#1 Storefront](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1): `feat/mobile-storefront-foundation` → `main`.
2. [#5 Shared physical/online stock POS](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5): `feat/in-store-pos-reservations` → `feat/mobile-storefront-foundation`.
3. [#6 Cashier roles and manager reconciliation](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/6): `feat/pos-reconciliation-and-cashier-role` → `feat/in-store-pos-reservations`.
4. [#7 Six POS crash boundaries](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/7): `test/pos-crash-recovery-audit` → `feat/pos-reconciliation-and-cashier-role`.
5. [#8 Return quarantine & inspection](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/8): `feat/pos-return-inspection` → `test/pos-crash-recovery-audit`.

**None are merged to main as of the last check.** Verify live GitHub before continuing.

## The canonical documents live on the latest PR #8 branch
- [AGENTS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-return-inspection/AGENTS.md)
- [docs/STATUS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-return-inspection/docs/STATUS.md)
- [docs/SESSION_HANDOFF.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-return-inspection/docs/SESSION_HANDOFF.md)
- [docs/MASTER_PLAN_FULL.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-return-inspection/docs/MASTER_PLAN_FULL.md)
- [docs/POS_CRASH_RECOVERY.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-return-inspection/docs/POS_CRASH_RECOVERY.md)
- [docs/POS_RETURNS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/pos-return-inspection/docs/POS_RETURNS.md)

## Latest engineering
PR #7 completed real WooCommerce/MySQL abrupt PHP exit tests at six stages; see branch docs and CI. PR #8 added a quarantined POS return intake ledger, UUID idempotency, order-item locks, manager-only inspection, cashier/manager WordPress screens and tests. Both `ADIM_ENABLE_POS` and `ADIM_ENABLE_SALES` remain **OFF**; `ADIM_ENABLE_RETURN_INTAKE` also defaults OFF. Review the current PR #8 CI head before considering merge.

## Open release blockers
- Refunds, automated stock restores and tax receipts NOT implemented; return inspection never refunds or restocks.
- Persistent private staging, real device UX, backups/restore, mixed checkout stock/online gateway testing.
- Product sourcing/trademark legality, merchant/payment approval, applicable ETBIS/consumer/KVKK and tax requirements.
- Only legally authorized goods can be sold. Do not list or sell counterfeit trademarked footwear.

## Resume prompt
> GitHub'daki `emrekrt221-ship-it/adimaykkabi` projesine devam et. Main dalındaki CURRENT_DEVELOPMENT.md'yi oku, ardından PR #8 dalındaki docs/STATUS.md ve SESSION_HANDOFF.md dosyalarını ve açık PR #1, #5, #6, #7, #8 testlerini kontrol et. Sonraki eksik işi odaklı PR'la geliştir, test et ve devir belgelerini güncelle. Test edilmemiş/uygunsuz ürünlerin gerçek satışını açma.
