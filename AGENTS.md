# ADIM AYAKKABI — agent/developer instructions

1. On **every new session** first read `docs/STATUS.md`, `docs/SESSION_HANDOFF.md` and `docs/MASTER_PLAN_FULL.md`, then `docs/DECISIONS.md`, `docs/ROADMAP.md`, `docs/DEVELOPMENT.md`, `docs/RELEASE_CHECKLIST.md`.
2. Inspect main, open branches, PRs and Github Issues before editing; the repo is the canonical state across ChatGPT conversations.
3. Work in focused branches/PRs. Do not overwrite other contributors' changes. Do not assert tests passed unless their result was actually checked.
4. WordPress core/WooCommerce vendor code, customer uploads, secrets and live configuration must NOT be in Git. Custom theme and isolated plugin only.
5. Keep WooCommerce product variation stock authoritative. Validate permissions/nonces, avoid over-selling, verify webhook idempotency; no direct SQL stock manipulation without deliberate reviewed design.
6. **No listing, advertising or checkout for counterfeit trademarked products.** Only legally authorized inventory, independently verified; no misleading brand claims. The current prototype approval gate still requires Store API/REST/direct URL audit. Launch also needs business, ETBIS, privacy, consumer and payment provider approvals.
7. Cloudflare is explicitly not part of v1. PHP/JS lint is not a replacement for WordPress/WooCommerce runtime, browser, payment and concurrency testing.
8. At session end update `docs/STATUS.md` and `docs/SESSION_HANDOFF.md` with branch/PR, changed files, exact test outcomes, blockers and next action. Update road map and ADR if decisions changed.
9. Read `docs/RELEASE_CHECKLIST.md` before a production-related change. Never merge/release unverified checkout behavior or assume work happens between chat turns.
