# ADIM AYAKKABI — In-store POS foundation

**Status:** Staging prototype in draft PR #5. NOT approved for production or real merchandise.

## Purpose
The cashier enters one WooCommerce SKU (for footwear variants, the *specific size and color child SKU*), reviews actual stock, confirms **payment already collected physically**, and records an order. Cash and an existing external card terminal are recording methods only. This plugin does not connect to a bank, settle payments, print fiscal receipts, or prepare legal invoices.

## Implementation
- WordPress admin: WooCommerce → Mağaza Satışı, permission `manage_woocommerce`.
- POST through `admin-post.php?action=adim_pos_sale`, WordPress nonce + capability validation.
- A UUID in a hidden field identifies the sale request. The `wp_adim_pos_requests` table enforces a unique request key across PHP workers; repeat requests do not create duplicate orders. It tracks order ID, status and UTC timestamps.
- The customer-facing online sales switch `ADIM_ENABLE_SALES` and the physical POS switch `ADIM_ENABLE_POS` are separate. **Both OFF by default.** POS will not work until an operator knowingly sets `ADIM_ENABLE_POS` to true in a private server-only configuration after approvals.
- Product must be published, authorized for sale, have positive price and stock, use per-SKU stock management and no backorders. Approval is a manual record, not legal clearance.
- Creates an actual `WC_Order` and adds WooCommerce order line items. Reserves stock with `wc_reserve_stock_for_order` to coordinate with online-checkout holds, then performs core `wc_reduce_stock_levels`. Verifies persisted order-item stock reduction before completing.
- *No direct SQL changes to inventory*. SQL is used only for the small POS idempotency ledger.
- On a failure before a stock-reduction attempt, the temporary order is removed and the ledger records `failed`. After stock reduction is attempted, the order remains for **manual reconciliation** and the ledger records `needs_review`; never retry with a new request key until the stock/order has been checked.
- The cashier action is intentionally single-line/one SKU. Bundled discount/promotion, multi-item checkout, tax/fiscal integrations and staff-specific roles are not yet implemented.

## Safety and operational preconditions
- Verify all product sourcing/trademark/design rights. Never publish/sell unauthorized trademarked replicas.
- Complete business registration, applicable ETBIS/KVKK/consumer requirements, physical invoicing/receipt responsibilities and cashier training.
- Enable server-side Woo stock management and Woo stock reservations. Never disable stock holds while this POS is in use.
- PHP/MySQL and WooCommerce version compatibility, durable backups including the POS ledger, email, and order management must be tested on persistent staging.
- **A green CI does not mean cashier transactions can be used in a real shop yet.**

## Known gaps for further work
- Human review screen to reconcile `needs_review`/stale `processing` entries and handle cash exception/refunds without accidental stock restoration.
- Dedicated least-privilege `adim_cashier` role, proper audit details, receipt/tax integration.
- Persistent staging installation, real phone browser UX, barcode hardware keyboard test, backup/restore and Woo updates.
- Performance tests with more concurrent online-checkout and physical-POS workloads; additional online Store API/checkout testing.
- Test power/network failures between reserve, stock decrement, completed order and ledger update.
- No auto-restocking when a physical payment is refunded: manager confirmation and condition inspection are required.

## Tests (in CI)
- `tests/pos-integration.php`: reviewed, unreviewed, duplicate, last stock, cash vs external terminal, actual WooCommerce size variants.
- `tests/pos-admin-smoke.php`: disabled screen, reviewed form/nonce, blocked form for unapproved product.
- `tests/pos-concurrency.sh`: two independent WP-CLI processes and MySQL competing for one unit; same request key replay.
- `.github/workflows/woocommerce-integration.yml`: installs temporary WordPress, WooCommerce, MySQL 8 and runs the above.

## Deployment
1. Review and merge prerequisite storefront PR #1 first, then PR #5 after QA.
2. Deploy theme and operations plugin only to persistent staging.
3. Enable POS switch **only in staging**, use approved synthetic demo products, check cash/terminal workflow.
4. Approve legal, financial, stock, backups and audit gaps before any real-world POS enablement.
