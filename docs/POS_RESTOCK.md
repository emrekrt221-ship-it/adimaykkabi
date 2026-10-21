# ADIM — Manager-approved reviewed physical return restock

**Draft PR #9 (not live).** Depends on draft #8 → #7 → #6 → #5 → #1.

## Objective and absolute constraints
After cashier return intake and manager inspection, **a second explicit manager action** can increase the EXACT sold size/color SKU stock. This action is independently disabled by default and does **not** issue a refund, undo fiscal documents, change order status or collect money. Real deployment remains blocked pending legally authorized goods, Turkish tax/consumer and payment requirements, permanent staging and QA.

## Safety controls
1. Only `manage_woocommerce` (manager/admin), matching logged-in user.
2. Only `ADIM_ENABLE_RETURN_RESTOCK === true`, defined privately on an approved deployment; default OFF. Internet/POS/return switches remain separate.
3. Require inspected `accepted` return with `reviewed_by`, exact POS order origin and item, originally reduced quantity, sale-approved product, positive SKU-managed stock and no backorders.
4. Deny recorded WooCommerce refunded quantity to avoid known parallel refund-based stock restore. **Race with external refund plugins still requires operational reconciliation and staging testing.**
5. Manager enters a 15–1000 character physical product/stock verification reason. Form has CSRF nonce and two separate checkboxes.
6. Durable `adim_return_stock_movements` table with UNIQUE `return_key`: at most one application attempt per return. Existing `completed` claim returns the prior receipt; existing `applying`/`needs_review` claim blocks automatic retry.
7. Record Woo order note and use WooCommerce `wc_update_product_stock(..., 'increase')`; never raw inventory SQL.
8. If PHP dies after claim or status update fails, outcome may be UNKNOWN. Record `needs_review` where possible and stop; **do not retry or automatically decrement compensation**. Manual stock count and order check required.
9. Finished `completed` return is never offered again on manager screen. Uncertain claims display warning, no action.

## Files
- `wp-content/plugins/adim-operations/includes/pos-restock.php`
- `wp-content/plugins/adim-operations/includes/pos-restock-admin.php`
- `tests/pos-restock.php`
- `.github/workflows/woocommerce-integration.yml`

## Tests
Disposable actual WordPress+WooCommerce+MySQL tests in `tests/pos-restock.php` verify switch default-off, denied cashier, invalid justification, accepted inspection and exact stock restoration, same-key replay, no refund/order cancellation, rejected review denial, product approval revoked, uncertain movement refused and manager screen before/after state.

## Remaining before any real stock change
- Failure injection at EVERY restock persistence boundary; simultaneous REST/admin stock adjustments and online checkout reservation; backup/restore.
- Financial refund/idempotency coordination and audited refunds/cancellations; fiscal receipts/invoices and multi-channel returns.
- Staff audit, device/mobile browser testing, physical item identification, product barcode verification and legally compliant sourcing.
- Legal/tax/payment-provider approval and authorization to sell the specific branded goods.
- Never merge all stacked drafts blindly; review base PRs and CI one-by-one.
