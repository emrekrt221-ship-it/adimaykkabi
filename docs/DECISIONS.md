# Architecture decision log

ADR-001: WooCommerce rather than custom ecommerce backend for v1; mature catalog, variations and order functionality.
ADR-002: Lightweight custom theme, avoid heavy page builders and unnecessary plugins.
ADR-003: No Cloudflare for v1; rely on quality hosting, TLS and server caching.
ADR-004: MySQL + WooCommerce HPOS for orders; Redis optional after measurement.
ADR-005: Shared inventory for in-store and online sales, with variation-level stock.
ADR-006: No custom payment-card storage; use vetted provider integration.
ADR-007: GitHub docs are canonical continuity source between chats; update after every session.
ADR-008: Legal authorization of products is a prerequisite to publishing inventory or activating sales.
