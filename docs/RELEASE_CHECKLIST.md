# Release gate (initial version)

**Not production ready.** The current repository only contains theme scaffolding and a prototype compliance gate.

## Business and legal checks — all required
- [ ] Verify business identity, payment provider eligibility and invoicing.
- [ ] Verify each product's legal sourcing, trademark/design rights and supplier paperwork.
- [ ] Do not publish or sell counterfeit branded products, even if described as replicas.
- [ ] ETBIS applicability/registration, Turkish consumer law, privacy/KVKK and legal pages reviewed with qualified advisers.
- [ ] Shipping rates, delivery promises, returns and cancellation procedures reviewed.
- [ ] Written ownership and domain/trademark checks for ADIM AYAKKABI completed.

## Technology and testing
- [ ] WordPress/WooCommerce installed on isolated staging, compatible PHP and MySQL.
- [ ] PHP lint and JS syntax checks pass on PR.
- [ ] Mobile viewports 360/390/430/768/1280 tested; keyboard navigation, focus and menu work.
- [ ] Woo shop archives, product variations, search, cart and checkout browser-tested.
- [ ] Unapproved product hidden from regular listings and blocked from purchase via direct URL, Store API, REST and other plugin integrations; gate fixed if bypass found.
- [ ] Payment test callbacks signature/order/amount/currency checked, duplicate notifications safe, no card secrets stored.
- [ ] Last-size stock race between physical and online sale proven safe.
- [ ] Cart/checkout/account excluded from public page cache.
- [ ] Orders/stock restore from offsite backup successfully tested.
- [ ] SMTP, order email, shipping tracking and returns tested.
- [ ] 10/25/50 visitor staging load tests complete, no live gateway billing.
- [ ] Core Web Vitals and server performance measurements recorded.
- [ ] Production release requires explicit operator approval.

## Deployment order
1. Merge reviewed code after passing checks.
2. Provision staging and connect WooCommerce settings and legal pages.
3. Install theme and operations plugin; test failure paths.
4. Add only approved products, then payment sandbox and real provider approval.
5. Repeat tests on production with a limited, approved launch.
