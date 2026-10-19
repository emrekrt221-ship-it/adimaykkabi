# ADIM AYAKKABI master plan v1.0

## Business
Single physical footwear store plus online sales across Turkey. Mobile-first website, Instagram acquisition, web checkout, physical store pickup, shipment and returns.

## Visual design
Black #151515, orange #F57C00, off-white #F7F7F5. Fast responsive storefront, accessible controls, high-quality optimized photos.

## Storefront
Home, categories, filters (size/color/price/availability), product details, size guide, cart, guest checkout, tracking, returns, contact and store information.

## Inventory
WooCommerce variable products: model, color, size; track stock per purchasable variation. Physical point-of-sale entry must update the same inventory. Test concurrency and payment failures.

## Administration
Mobile-friendly product, inventory, order, shipment, return, promotion and reporting tools.

## Architecture
WordPress + WooCommerce with HPOS, MySQL, lean custom theme and isolated custom plugin. Quality hosting, HTTPS, backups, monitoring, server cache excluding cart/checkout/account. Redis only if measured need. No Cloudflare initially.

## Integrations
Approved Turkish payment provider subject to underwriting; initial manual tracking numbers with future carrier API. No custom storage of card details.

## Compliance gate
Only goods legally authorized for sale. Verify sourcing and trademark/design rights before catalog publication. Complete applicable ETBIS, tax/invoicing, distance selling, consumer rights, privacy and cookie obligations before accepting real orders.

## Performance goals
At 75th percentile mobile field data: LCP <=2.5s, INP <=200ms, CLS <=0.1. Validate with Lighthouse, browser tests and load tests; never promise unmeasured performance.
