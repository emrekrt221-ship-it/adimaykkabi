# Architecture decision log

ADR-001: WooCommerce rather than custom ecommerce backend for v1; mature catalog, variations and order functionality.
ADR-002: Lightweight custom theme, avoid heavy page builders and unnecessary plugins.
ADR-003: No Cloudflare for v1; rely on quality hosting, TLS and server caching.
ADR-004: MySQL + WooCommerce HPOS for orders; Redis optional after measurement.
ADR-005: Shared inventory for in-store and online sales, with variation-level stock.
ADR-006: No custom payment-card storage; use vetted provider integration.
ADR-007: GitHub docs are canonical continuity source between chats; update after every session.
ADR-008: Legal authorization of products is a prerequisite to publishing inventory or activating sales.
ADR-009 (2026-10-20): Extend existing `adim` classic Woo-compatible theme instead of replacing it with a block theme; reduce churn while improving mobile design.
ADR-010 (2026-10-20): Keep WooCommerce-specific business logic in `adim-operations`, not theme; product approval gate must be tested against public APIs.
ADR-011 (2026-10-20): PHP/JS syntax CI plus mocked and real WooCommerce tests; browser/payment and crash tests still needed.
ADR-012 (2026-10-20): Work in feature branches with reviewable PRs; other chats must inspect current branch/PR state and continue from updated handoff.
ADR-013 (2026-10-20): Fail-closed internet checkout switch `ADIM_ENABLE_SALES`; private approval and verified stock/legal/financial controls required.
ADR-014 (2026-10-20): Separate physical store switch `ADIM_ENABLE_POS` default OFF. Use real WooCommerce orders + `wc_reserve_stock_for_order` / `wc_reduce_stock_levels` and unique request-key ledger. No custom stock SQL or external card processing. Stock-reduction uncertainty must retain order/ledger for manual reconciliation, not delete or blindly retry.
ADR-015 (2026-10-20): POS prototype and CI are stacked in draft PR #5 on PR #1. Actual production release is blocked until cashier role, interruption recovery, reconciliation, tax/receipt and payment/source-rights reviews.
