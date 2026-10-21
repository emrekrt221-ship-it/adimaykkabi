# ADIM mobile product catalog UX (draft PR #10)

Branch `feat/mobile-catalog-shopping-ux` is based on `feat/mobile-storefront-foundation` (draft PR #1). It is a **parallel UI branch** to deep stock/POS PRs #5–#9. Do not assume its code includes POS or return changes.

## User experience
- Lightweight WooCommerce native catalog view: card/grid images, prices, sorting and responsive tap targets, 360px+ layout.
- A no-JavaScript `Beden / Numara` selector appears only when WooCommerce has a registered global product attribute with slug `beden` (taxonomy `pa_beden`) and at least one non-empty term.
- The selected number is validated against actual taxonomy terms. Product query joins the size tax condition with existing conditions using **AND**, including any existing OR group, and retains independent legal product-approval guards from the operations plugin.
- Preserve WooCommerce price and sorting query parameters when submitting the size filter.
- Product detail uses native WooCommerce image gallery, variations, available stock and add-to-cart; custom CSS adds responsive look and accessible disclosure about measuring foot size, with no unsupported shoe-size promises.
- No external fonts, heavy page builders, CDN or Cloudflare in v1.
- Real data must use product and variant SKUs, legally authorized supplier descriptions, true photos and accurate pricing. Unapproved branded counterfeits must not be listed or sold.

## Setup
1. In WooCommerce admin, create global product attribute named **Beden**, slug **beden**.
2. Add terms e.g. 40, 41, 42, 43, 44 and assign terms to approved catalog products. For variable footwear, use Woo variations with per-size stock.
3. Confirm the main WooCommerce shop page, category URLs, search, and price/sort links.
4. Release **only** after staging legal and merchant compliance gates are met. Customer checkout is `ADIM_ENABLE_SALES` default OFF.
5. Retain the WooCommerce built-in variation selector; never override its inventory or payment logic in theme code.

## Validation
- `tests/catalog-ux.php`: real WP/Woo DB query for approved 42, approved 43 and unapproved 42; selected size, missing/invalid input, taxonomy guard, filter markup and fit guidance.
- `tests/browser-mobile.mjs`: real Chromium at 360, 390, 768, 1280px, menu open/close via Escape, no horizontal overflow, homepage and mobile product detail, save screenshots.
- GitHub workflow: `.github/workflows/woocommerce-integration.yml`; inspect latest PR #10 CI and artifacts.

## Remaining
- Photo-led design review by actual operator, improved catalog typography/product photography, cart, guest checkout, product stock/display accessibility, filtering UX across categories, actual 3G mobile performance and load.
- Real payments/kargo/delivery legal documents and the external acquiring-provider review are separate projects; no live selling yet.
- Browser CI and synthetic Woo products are not a substitute for real device, assistive technology or final commercial QA.
