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
ADR-010 (2026-10-20): Keep WooCommerce-specific business logic in `adim-operations`, not theme; initial product approval gate must be tested against public APIs.
ADR-011 (2026-10-20): PHP/JS syntax CI plus mocked hook smoke tests; functional/browser/payment tests remain required and cannot be inferred from lint or mocks.
ADR-012 (2026-10-20): Work in feature branches with reviewable PRs; other chats must check GitHub branch and PR state and continue from updated handoff.
ADR-013 (2026-10-20): Fail-closed sales deployment gate: `ADIM_ENABLE_SALES` defaults to disabled. Explicit private server configuration and launch approval required. An administrative review flag marks internal sourcing checks, but is never itself legal certification. WooCommerce API and direct URL behavior remains a staging test requirement.

ADR-020 (2026-10-21): Customer catalog UX developed separately from stacked POS/returns work as draft PR #10 based on storefront PR #1. Native WooCommerce variation/cart paths, optional `pa_beden` filter with taxonomy validation, lean responsive CSS and real browser QA; no frontend reimplementation of inventory or payments.

ADR-021 (2026-10-21): Keep native WooCommerce classic/blocks cart, shipping, total, stock and payment infrastructure. Provide only lightweight UI guidance at theme level and early checkout policy guards in operations plugin. Reject Store API POST checkout before order creation while default OFF or after item approval revocation. No custom card collection or invented shipping rates; customer checkout work is parallel UI PR #11 based on PR #10.

ADR-022 (2026-10-21): Validate customer guest cart and delivery without custom checkout or real payments via a disposable localhost WordPress/WooCommerce+MySQL fixture. Use only neutral synthetic products and WooCommerce's real shipping-zone API, test-only rates and tax settings. Enable public-sale flag ONLY inside temporary CI wp-config for isolated browser preview after default-OFF regression; never commit a true flag or gateway credentials. Assert no orders/payment gateways after demonstration.

ADR-023 (2026-10-21): Guest billing validation should be tested using WooCommerce's own protected `WC_Checkout::validate_posted_data` directly inside disposable WP-CLI CI (Reflection test-only) with WP_Error and zero-order assertions. Do not invent a separate browser-only validator or call `process_checkout` without a merchant-safe sandbox. Chromium must inspect Woo `validate-required` markup rather than assuming every Woo required field has an HTML `required` attribute. Always remove any temporary test-only sales flag via shell EXIT cleanup.
