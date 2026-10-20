# ADIM AYAKKABI — Current Development Pointer
Updated: 2026-10-20. **Read this file first when continuing in a new chat.**

## Where actual development lives
- [Draft PR #1 — responsive storefront, WooCommerce theme and review gate](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/1): `feat/mobile-storefront-foundation` → `main`.
- [Draft PR #5 — POS / shared WooCommerce inventory](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/5): `feat/in-store-pos-reservations` → `feat/mobile-storefront-foundation` (**stacked**, depends on PR #1).

**Neither branch is merged into `main` as of this note.** Check GitHub live PR statuses before acting.

## Authoritative handoff files (read from the POS branch)
- [AGENTS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/in-store-pos-reservations/AGENTS.md)
- [docs/STATUS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/in-store-pos-reservations/docs/STATUS.md)
- [docs/SESSION_HANDOFF.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/in-store-pos-reservations/docs/SESSION_HANDOFF.md)
- [docs/MASTER_PLAN_FULL.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/in-store-pos-reservations/docs/MASTER_PLAN_FULL.md)
- [docs/POS_OPERATIONS.md](https://github.com/emrekrt221-ship-it/adimaykkabi/blob/feat/in-store-pos-reservations/docs/POS_OPERATIONS.md)

## Last verified GitHub Actions
- [Run 39385411043](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39385411043): real temporary WordPress+WooCommerce+MySQL integration, real HTTP, POS, cashier UI and 2 concurrent stock workers — PASS.
- [Run 39385411038](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39385411038): PHP/JS syntax and isolated behavior checks — PASS.
- These are CI results, **not production validation**.

## What is unfinished
- Persistent staging, real browser/device accessibility and recovery tests.
- Physical cash sale reconciliation and audited cancellations/returns; least-privilege cashier role.
- Live payment integration, shipping, tax/invoice workflow and online checkout edge cases.
- Trademark/source authorization, customer/data/commercial requirements; unapproved counterfeit branded products must not be listed or sold.
- `ADIM_ENABLE_POS` and `ADIM_ENABLE_SALES` are OFF by default.

## Next-chat instruction
> GitHub'daki `emrekrt221-ship-it/adimaykkabi` projesine devam et. Ana daldaki CURRENT_DEVELOPMENT.md dosyasını, sonra `feat/in-store-pos-reservations` dalındaki AGENTS.md, docs/STATUS.md ve docs/SESSION_HANDOFF.md dosyalarını oku. Açık PR #1 ve #5'i, Issues #2/#3/#4'ü ve son CI sonuçlarını kontrol et. Aynı işleri tekrarlama; sıradaki eksik işi ayrı küçük PR ile geliştir, test et ve devir belgelerini güncelle. Test edilmemiş kodu birleştirme ve yasal açıdan uygun olmayan ürünlerin satışını açma.
