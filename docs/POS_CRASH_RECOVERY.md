# ADIM — POS Crash/Interruption Audit

> **Not production-ready.** This work tests transaction behavior with synthetic legally approved sample products. Neither online checkout nor physical sales are enabled on a live store.

## Threat model

A cashier physically receives cash/card on a separate terminal, then submits a WooCommerce in-store sale. PHP could terminate between non-atomic operations (ledger claim, order save, Woo reserved stock, Woo stock reduction, order completed, request ledger finalized). Retrying with a **new** request key without investigating could duplicate orders or stock movements.

The POS ledger is keyed by a UUID; it is intentionally independent of product stock. Actual stock is managed exclusively by WooCommerce.

## Explicit fault checkpoints (CI only)

| Checkpoint | Durable artifacts after forced process exit | Safe response |
|---|---|---|
| `ledger_claimed` | request key `processing`, no order, stock unchanged | Manager/developer investigates; never blindly retry |
| `order_saved_before_link` | request key with order_id=0, Woo order exists with same UUID, stock unchanged | Read-only candidate order shown; technician checks both records |
| `order_linked` | request key linked to pending Woo order, stock unchanged | Manual review, not automatic payment/stock adjustment |
| `stock_reserved` | linked pending order, Woo reservation exists, stock quantity unchanged | Verify reservation/expiry and physical transaction |
| `stock_reduced` | linked pending order, Woo stock decreased once | Verify physical delivery/payment; do not retry |
| `order_completed` | linked completed order, Woo stock decreased once, ledger still `processing` | Manager verifies and acknowledges without changing stock |

Test guard: `adim_pos_test_checkpoint` runs only if **WP_CLI=true AND ADIM_POS_TEST_MODE=true**, and tests activate a one-shot listener that stops the independent PHP process via exit code 86. This does NOT emulate a database server crash or sudden power loss, but it verifies persisted artifacts from real PHP/MySQL process termination at six critical boundaries.

## Test files

- `tests/pos-crash-setup.php`: isolated authorized synthetic SKU.
- `tests/pos-crash-worker.php`: abrupt PHP termination at selected checkpoint.
- `tests/pos-crash-verify.php`: independent process checks order/ledger/stock, duplicate request denial, review eligibility and lack of automatic stock changes.
- `tests/pos-crash-tests.sh`: executes all six independent cases.
- `.github/workflows/woocommerce-integration.yml`: installs disposable WordPress + WooCommerce + MySQL, runs regression suite and crash tests.

## Recovery policy — deliberately manual

- A repeat request with the same UUID cannot create a second sale while the ledger is `processing`, `needs_review`, `reviewed` or `completed`. The cashier should **not use a different UUID** to bypass the review.
- A request with no ledger order ID may have a WooCommerce order carrying the same UUID. `adim_pos_possible_orphan_orders()` is a **manager-only, read-only lookup**; candidate order IDs are shown in POS İnceleme but are not linked or marked resolved.
- Ledger entries with order_id=0 cannot be marked reviewed through the UI. An authorized technician must reconcile the evidence first.
- If the ledger has a valid linked order, the manager checks cash/terminal evidence, stock, order item quantities/status, and then provides a reason. A WooCommerce order note must be durably saved **before** the ledger changes to `reviewed`. If either step fails, do not assume the incident is fully closed.
- Reviewing never issues refunds, changes order status, releases reservations or mutates stock.
- Manager-reconciled records cannot be overwritten by a delayed POS worker, because completion/failure updates only affect rows still `processing`.

## Remaining non-covered failure modes

- DB server restart while a query or reservation row lock is active; power failure with partial fsync.
- Network partition, load balancer/client retries with fresh UUID, and stale cashiers with different devices.
- Gateway/terminal settlement confirmation (the POS currently **records**, not processes, external card charges).
- Correct Woo reservation cleanup under every checkout/expiration scenario.
- Physical refunds, exchanged goods, restock after inspection and fiscal receipts/invoices.
- Persistent staging, browser-level mobile tests, monitoring/backups and recovery drill.
- Final legal sourcing/brand-rights checks and merchant compliance. **Do not list or sell counterfeit trademarked goods.**

## Launch gate

Keep `ADIM_ENABLE_POS` and `ADIM_ENABLE_SALES` OFF until code review, positive real staging tests, authorized merchandise, payment/financial and commercial/legal prerequisites are complete.
