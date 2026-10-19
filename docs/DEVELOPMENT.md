# Development and test setup

## Repo scope
This repository intentionally contains custom project code only. WordPress core, WooCommerce vendor plugin, media uploads and configuration/secrets belong in private runtime environments, not in Git.

- Theme: `wp-content/themes/adim` — versioned storefront templates/CSS/JS.
- Operations plugin: `wp-content/plugins/adim-operations` — WooCommerce customizations; prototype approval gate.
- CI: `.github/workflows/quality.yml`.
- Persistent project state: `docs/STATUS.md` and `docs/SESSION_HANDOFF.md`.

## Manual local/staging install
1. Install up-to-date supported WordPress/PHP/MySQL and a compatible WooCommerce version in an isolated staging environment.
2. Copy `wp-content/themes/adim` and `wp-content/plugins/adim-operations` into that installation.
3. Activate WooCommerce; confirm standard Woo pages were created and HPOS compatibility.
4. Activate the ADIM theme, then activate ADIM Store Operations.
5. Configure product categories and the theme's primary menu. Set shop, cart, checkout and account pages using WooCommerce settings.
6. Add **legally authorized sample goods only** and configure variable size/color stock. Unapproved products are intentionally not purchasable.
7. Keep live gateway API keys and real customer data out of staging. Use gateway sandbox and test orders.
8. Configure TLS, offsite backup and transactional email; use real shipping/legal details only after verification.

## Static checks
```bash
find wp-content -name '*.php' -type f -print0 | xargs -0 -n1 php -l
find wp-content -name '*.js' -type f -print0 | xargs -0 -n1 node --check
```

## Manual smoke checklist
- Header menu keyboard toggles and closes with Escape; search works on desktop/mobile.
- Category links and 8 new-arrivals slots render without warnings.
- Single, variable, out-of-stock product and Woo blocks/shortcodes show consistently.
- Product with no approval cannot purchase; approved test product works; verify REST and Store API endpoints separately.
- No unexpected styling shifts in Woo cart and checkout.
- Checkout pages always bypass public cache.
- No secrets in source, errors logged without customer/card secrets.

## Operational rules
Before every development session: read AGENTS + docs, pull latest, use feature branch. After session: update STATUS/HANDOFF with exact tests and next task. Before release: complete docs/RELEASE_CHECKLIST.md.
