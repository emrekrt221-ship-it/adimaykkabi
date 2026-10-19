# Session handoff

Project: ADIM AYAKKABI. Repository: emrekrt221-ship-it/adimaykkabi.

## Context
User owns a physical shoe shop and wants a professional, fast, low-cost ecommerce site. Chosen WooCommerce + custom lightweight theme, MySQL, HPOS, HTTPS; Cloudflare explicitly excluded initially. Brand colors black/orange/white. Inventory must sync with in-store sales.

## Important limitation
User mentioned buying replica shoes resembling major brands. Do not assume they are authorized. Do not publish counterfeit trademarked inventory or facilitate their checkout. Work on neutral infrastructure, design and compliance-ready flows only until legal authorization is established.

## Work done
Repository documentation initialized. No tested application code yet.

## Next action
Inspect files, scaffold theme (`wp-content/themes/adim/`) and optional custom plugin (`wp-content/plugins/adim-commerce/`), create local environment instructions and run available checks. Record actual test outcomes.

## Rules
Update this handoff and docs/STATUS.md at the end of every session. Use small PRs; avoid secrets and production changes.
