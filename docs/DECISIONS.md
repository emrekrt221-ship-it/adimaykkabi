# ADIM AYAKKABI architecture decision log

ADR-001: WooCommerce rather than custom ecommerce backend for v1; mature catalog, variations and order functionality.
ADR-002: Lightweight custom theme, avoid heavy page builders and unnecessary plugins.
ADR-003: No Cloudflare for v1; rely on quality hosting, TLS and server caching.
ADR-004: MySQL + WooCommerce HPOS for orders; Redis optional after measurement.
ADR-005: Shared inventory for in-store and online sales, with variation-level stock.
ADR-006: No custom payment-card storage; use vetted provider integration.
ADR-007: GitHub docs are canonical continuity source between chats; update after every session.
ADR-008: Legal authorization of products is a prerequisite to publishing inventory or activating sales.
ADR-009 (2026-10-20): Extend existing `adim` classic Woo-compatible theme rather than replacing with a block theme.
ADR-010 (2026-10-20): Woo business logic stays in `adim-operations`, not the theme; public Store API/REST/checkout requires tests.
ADR-011 (2026-10-20): CI syntax/mock and real disposable WordPress/WooCommerce/MySQL integration, not a substitute for production testing.
ADR-012 (2026-10-20): Small stacked feature PRs; GitHub docs and current branch are canonical continuity.
ADR-013 (2026-10-20): Default OFF public checkout `ADIM_ENABLE_SALES`; no payment/sales until merchant and product authorization.
ADR-014 (2026-10-20): Default OFF in-store POS `ADIM_ENABLE_POS`. Unique request-key ledger, Woo reserve/reduce instead of direct SQL stock edits. Uncertain reductions retain order and need manual review.
ADR-015 (2026-10-20): POS prototype in draft PR #5 depends on storefront PR #1; manager and accountant must reconcile fiscal and failed-order cases before production.
ADR-016 (2026-10-20): Least-privilege cashier role `adim_cashier` has only `read` and `adim_pos_sell`. WooCommerce manager/admin owns reconciliation; cashiers cannot review, refund, manage product rights or alter settings.
ADR-017 (2026-10-20): Review queue is **acknowledgment only** and cannot modify inventory/payment/refund state. Only orders matching POS request ID/origin can be resolved after 15+ character evidence note and optimistic ledger update. Stale processing without order must be escalated; do not auto-delete.

ADR-018 (2026-10-21): POS hard-stop tests are **WP-CLI only** and require explicit `ADIM_POS_TEST_MODE`; no fault injection in production HTTP checkout. Test all six ledger/order/hold/reduction/completion persistence boundaries with real MySQL, no automatic replay.
ADR-019 (2026-10-21): Orphan order discovery is manager-only and read-only. No automatic linking or cancellation. Persist the WooCommerce investigation note before marking the POS ledger reviewed; note write failure leaves the ledger unresolved. Still no stock/refund mutations in review.

ADR-018 (2026-10-21): POS returns use an independent Woo-linked intake ledger, MySQL per-order-item lock, UUID replay and max sold-quantity enforcement; new return begins in quarantine. Cashier has intake only, manager records inspection only. Neither step refunds, changes order status or adds salable stock. Separate server-only return-intake flag defaults disabled.

ADR-019 (2026-10-21): Returned stock can re-enter salable WooCommerce inventory only after manual accepted physical inspection and a *separate* manager action. Default OFF `ADIM_ENABLE_RETURN_RESTOCK`, unique per-return claim and unknown-outcome fail-closed. No automatic refund or fiscal adjustment; do not auto-retry or compensate uncertain increments. This is draft PR #9 pending QA.
