import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';

/**
 * CI-only filled guest cart → checkout smoke.
 * Runs against disposable localhost WordPress with synthetic goods, private
 * test-only checkout switch and *all payment gateways disabled*.
 * This script NEVER submits an order or clicks Place Order.
 */
const base = process.env.ADIM_TEST_URL ?? 'http://127.0.0.1:8888';
const id = Number(process.env.ADIM_CI_PRODUCT_ID);
const checkoutUrl = process.env.ADIM_CI_CHECKOUT_URL;
assert(Number.isInteger(id) && id > 0, 'CI sample product ID required');
assert(checkoutUrl?.startsWith(base), 'CI native checkout URL required');
const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });

await mkdir('browser-screenshots', { recursive: true });
try {
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));

  const add = await page.goto(`${base}/?add-to-cart=${id}&quantity=2`,
    { waitUntil: 'domcontentloaded' });
  assert(add?.status() === 200, 'synthetic guest add-to-cart request responds');
  const cartUrl = await page.locator('.adim-cart-link').getAttribute('href');
  assert(cartUrl?.startsWith(base), 'WooCommerce cart link is local');
  const cart = await page.goto(cartUrl, { waitUntil: 'domcontentloaded' });
  assert.equal(cart?.status(), 200);
  await page.getByText('ADIM CI Synthetic Guest Shoe').first()
    .waitFor({ state: 'visible' });
  assert(await page.locator('.woocommerce-cart-form').count() > 0,
    'native WooCommerce cart form contains synthetic item');
  assert.equal(await page.locator('.adim-checkout-alert').count(), 0,
    'no sales-off notice in the isolated test-only browser process');
  await page.screenshot({ path: 'browser-screenshots/guest-cart-390.png', fullPage: true });
  console.log('PASS Browser: synthetic item is in native guest cart');

  const checkout = await page.goto(checkoutUrl, { waitUntil: 'domcontentloaded' });
  assert.equal(checkout?.status(), 200);
  await page.locator('.adim-checkout-help').waitFor({ state: 'visible' });
  await page.locator('#billing_first_name').waitFor({ state: 'visible' });
  await page.locator('#billing_first_name').fill('Test');
  await page.locator('#billing_last_name').fill('Müşteri');
  await page.locator('#billing_address_1').fill('Deneme Sokak 1');
  await page.locator('#billing_city').fill('Reyhanlı');
  const country = page.locator('#billing_country');
  if (await country.count() && await country.evaluate(el => el.tagName === 'SELECT')) {
    await country.selectOption('TR');
  }
  const postcode = page.locator('#billing_postcode');
  if (await postcode.count()) {
    await postcode.fill('31500');
    await postcode.press('Tab');
  }
  const email = page.locator('#billing_email');
  if (await email.count()) await email.fill('test@example.test');

  await page.getByText('ADIM CI Fixed Delivery').first().waitFor({ state: 'visible' });
  const shippingMethod = page.locator('input.shipping_method').first();
  assert(await shippingMethod.count() > 0, 'native Woo flat-rate shipping method is available');
  const rateValue = await shippingMethod.getAttribute('value');
  assert(rateValue?.startsWith('flat_rate:'), `Unexpected Woo rate: ${rateValue}`);
  assert.equal(await page.locator('.adim-checkout-alert').count(), 0,
    'test-only enabled storefront does not display closed-sales alert');
  assert(await page.locator('#order_review').count() > 0,
    'WooCommerce native order summary remains available');

  const width = await page.evaluate(() => ({
    content: document.documentElement.scrollWidth,
    viewport: document.documentElement.clientWidth,
  }));
  assert(width.content <= width.viewport + 2,
    `390px guest checkout horizontal overflow: ${JSON.stringify(width)}`);
  await page.screenshot({ path: 'browser-screenshots/guest-checkout-390.png', fullPage: true });
  assert.deepEqual(errors, [], 'no uncaught JavaScript errors');
  console.log('PASS Browser: guest address and native configured shipping rate display without payment');
} finally {
  await browser.close();
}
