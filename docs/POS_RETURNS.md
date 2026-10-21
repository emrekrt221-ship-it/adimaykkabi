# ADIM — POS Return Intake and Quarantine

**Experimental / draft PR #8. NOT production-ready.** Only legally authorized, synthetic sample shoes are used in tests.

## Scope
A cashier may receive a returned item from an existing `adim-pos` WooCommerce order, record the order line/quantity in an independent inspection ledger and quarantine the item. A WooCommerce manager may record a subsequent physical inspection decision. **Neither operation refunds money, restores salable stock, cancels an order or prints any statutory invoice/receipt.**

## Fail-closed policy
- Intake is separately gated by `ADIM_ENABLE_RETURN_INTAKE`, false by default. Web and physical POS also remain disabled by their distinct deployment flags.
- The cashier has `adim_pos_sell`, but cannot approve their own inspection. Approval requires `manage_woocommerce`.
- The order must be an authentic physical-store `adim-pos` order with `_adim_channel=in_store` and a real WooCommerce order item.
- Same UUID and identical payload replay returns the existing receipt. Same UUID with different parameters rejects.
- MySQL per-order-line named lock serializes independent return requests. Sum of intake quantities cannot exceed the sold item quantity.
- Every returned item begins in `quarantine`, not salable stock. Inspection outcome `accepted` or `rejected` is a **record of inspection**, not an instruction to restock or refund.
- WooCommerce order note records intake and manager review. If note persistence fails after intake row is saved, preserve the row for investigation rather than re-accepting the same return.
- Product legality, proof of sale, customer identity and refund eligibility remain subject to operator verification and applicable consumer law.

## Later phase requirements
- Receipt/reference to specific returned pair, customer identity verification and shipping intake without collecting excessive personal data.
- Separate financial refund workflow with gateway authorization, payment settlement and fiscal documentation.
- Reviewed stock restoration or quarantine disposal for non-salable goods, with one-time audited movement per unit and separate authorizer.
- Real device/browser accessibility testing, simultaneous returns pressure tests, DB crash/restart, backup/restore, personal data retention controls.
- Validate return entitlements and deadlines with qualified Turkish legal/accounting adviser before launch.

## Test coverage
`tests/pos-returns.php` uses disposable real WordPress/WooCommerce/MySQL:
1. intake disabled by default;
2. cashier accepts one return and repeat UUID is idempotent;
3. sum of returned units cannot exceed sold units;
4. cashier cannot perform manager inspection;
5. manager records inspection once with adequate reason;
6. no automatic refund, order cancellation or stock increase.

## Project links
[Draft PR #8](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/8) depends on [PR #7](https://github.com/emrekrt221-ship-it/adimaykkabi/pull/7), then #6, #5, #1. Keep the chain as draft until actual release readiness and all legally compliant merchandise checks.
