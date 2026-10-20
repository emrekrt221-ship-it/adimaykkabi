# ADIM POS — Roles and interrupted-sale reconciliation

**Status:** Prototype on draft PR #6, NOT available on a live store.

## Roles and separation of duties

| Role | POS sale (only if ADIM_ENABLE_POS=true) | WooCommerce product/price/settings | POS reconciliation |
| --- | --- | --- | --- |
| Administrator | Yes | Yes | Yes |
| Shop manager | Yes | Yes | Yes |
| `adim_cashier` | Yes | No | No |
| Customer/Subscriber | No | No | No |

The `adim_cashier` role has only `read` and `adim_pos_sell`. It does not have `manage_woocommerce` or `manage_options`. Plugin activation and `init` install/repair the POS capability assignments on administrators and WooCommerce shop managers.

The cashier opens **ADIM Mağaza Satışı** on the WordPress admin navigation and can enter a SKU and quantity, choose cash or an external terminal, and confirm that external payment was collected. This does NOT charge cards. Stock and orders remain owned by WooCommerce.

## Why reconciliation exists

A process can stop after WooCommerce reserves or reduces stock but before recording a successful response. Retrying automatically with a *new* request ID may double-decrement or generate an extra order. The unique request-key ledger therefore holds `processing` and `needs_review` records for operator investigation.

The **WooCommerce → POS İnceleme** page lists:
- `needs_review` immediately;
- `processing` if no update occurred for at least 15 minutes;
- first 50 oldest entries, latest timestamp in UTC.

The page is restricted to `manage_woocommerce`. A cashier cannot dismiss an exception.

## Manual manager steps

1. Do **not** repeat payment or POS sale under a new request ID while the prior transaction is uncertain.
2. Compare WooCommerce order ID, status and order line quantities, POS ledger entry, terminal/cash receipt, and physically counted SKU/size stock.
3. If a payment was taken but order incomplete, resolve the accounting and delivery issue by an authorized independent process.
4. If order stock is wrong, adjust by the regular reviewed WooCommerce stock workflow, with a record of reason and stock count; NEVER alter stock from the review screen.
5. Only after evidence is checked, enter a **15+ character reconciliation note**. The screen validates that the linked WooCommerce order has the same request key and POS origin.
6. The system atomically changes ledger status to `reviewed` and records the manager note on the WooCommerce order. It does **not** cancel, refund, fulfill, reserve or restore stock.
7. If the ledger has no linked order or the order ID/request key does not match, the record cannot be cleared in the UI. Escalate to technical investigation.

## Safety notes
- Marking `reviewed` records an acknowledgment, **not proof** that payment/stock were correct. It must never automatically resume checkout.
- `ADIM_ENABLE_POS` remains OFF by default, even if cashier role exists.
- Any goods must be legally authorized for sale. Manually checking a product checkbox is not legal authorization.
- All cashier/manager tests use synthetic products in disposable WordPress + MySQL GitHub Actions.
- No live fiscal device, official receipt, merchant payment settlement or refunds in this implementation.

## CI evidence
- [Run 39391233300](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391233300): real WooCommerce integration with cashier, permissions, successful sale, review acknowledgment, audit note, stale orphan and fresh processing protections.
- [Run 39391233199](https://github.com/emrekrt221-ship-it/adimaykkabi/actions/runs/39391233199): PHP/JS lint and isolated hook checks.

## Next prerequisites before production
- Additional destructive-failure injection between order creation, stock hold, decrement and ledger completion;
- Real browser/mobile/keyboard accessibility review;
- Cashier/manager authentication and full access-control audit under WooCommerce upgrades;
- Audited refund/return and stock inspection flows; tax/fiscal receipt integration;
- Persistent staging, offsite backups and restore, HTTPS and monitoring; online/cart mixed-concurrency test;
- Product/brand legality, seller and payment-provider approval, applicable ETBIS/consumer/privacy compliance.
