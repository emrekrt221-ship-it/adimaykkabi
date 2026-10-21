import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';

const baseUrl = process.env.ADIM_TEST_URL ?? 'http://127.0.0.1:8888';
const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const widths = [360, 390, 768, 1280];
await mkdir('browser-screenshots', { recursive: true });

try {
  for (const width of widths) {
    const page = await browser.newPage({
      viewport: { width, height: 850 },
      deviceScaleFactor: 1,
    });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const response = await page.goto(baseUrl + '/', { waitUntil: 'domcontentloaded' });
    assert(response && response.status() === 200, `Homepage HTTP 200 at ${width}px`);
    await page.locator('#adim-hero-title').waitFor({ state: 'visible' });
    assert.match(await page.locator('h1').first().innerText(), /Tarzına Yeni Bir Adım/);

    const layout = await page.evaluate(() => ({
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
    }));
    assert(
      layout.scrollWidth <= layout.clientWidth + 2,
      `Horizontal overflow at ${width}px: ${JSON.stringify(layout)}`
    );
    if (width <= 960) {
      const toggle = page.locator('.adim-menu-toggle');
      await toggle.waitFor({ state: 'visible' });
      try {
        await page.waitForFunction(() => document.body.classList.contains('adim-js'),
          null, { timeout: 7000 });
      } catch (error) {
        const debug = await page.evaluate(() => ({
          bodyClass: document.body.className,
          scriptSources: Array.from(document.querySelectorAll('script[src]'))
            .map(script => script.src),
          navExists: Boolean(document.getElementById('adim-primary-navigation')),
          buttonExists: Boolean(document.querySelector('.adim-menu-toggle')),
        }));
        console.error('Mobile navigation initialization diagnostic:', JSON.stringify(debug));
        console.error('JavaScript page errors:', JSON.stringify(errors));
        throw error;
      }
      assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
      await toggle.click();
      assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
      await page.locator('#adim-primary-navigation').waitFor({ state: 'visible' });
      await page.keyboard.press('Escape');
      assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
    }
    await page.screenshot({ path: `browser-screenshots/home-${width}.png`, fullPage: true });
    assert.deepEqual(errors, [], `No JS errors at ${width}px`);
    console.log(`PASS Browser: homepage, nav and no horizontal overflow at ${width}px`);
    await page.close();
  }

  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const home = await page.goto(baseUrl + '/', { waitUntil: 'domcontentloaded' });
  assert(home?.status() === 200, 'home is reachable');
  const first = page.locator('.woocommerce ul.products li.product a.woocommerce-LoopProduct-link').first();
  await first.waitFor({ state: 'visible' });
  const url = await first.getAttribute('href');
  assert(url?.startsWith('http'), 'approved sample product link is absolute');
  const response = await page.goto(url, { waitUntil: 'domcontentloaded' });
  assert(response?.status() === 200, 'approved product detail reachable');
  await page.locator('.adim-size-help summary').waitFor({ state: 'visible' });
  await page.locator('.adim-size-help summary').click();
  assert(await page.locator('.adim-size-help').getAttribute('open') !== null,
    'size-help disclosure can be opened on mobile');
  const layout = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }));
  assert(layout.scrollWidth <= layout.clientWidth + 2,
    'Product page has no horizontal overflow at 390px');
  await page.screenshot({ path: 'browser-screenshots/product-390.png', fullPage: true });
  console.log('PASS Browser: responsive product detail and fitting guidance');
  // Native WooCommerce cart and checkout render real, disabled-sale states.
  const cartHref = await page.locator('.adim-cart-link').getAttribute('href');
  assert(cartHref, 'WooCommerce cart link present');
  const cartPage = await page.goto(cartHref, { waitUntil: 'domcontentloaded' });
  assert(cartPage?.status() === 200, 'native empty-cart page responds');
  await page.locator('.adim-journey').waitFor({ state: 'visible' });
  await page.locator('.adim-checkout-alert').waitFor({ state: 'visible' });
  assert.equal(await page.locator('.adim-journey [aria-current="step"]').count(), 1);
  await page.screenshot({ path: 'browser-screenshots/cart-390.png', fullPage: true });
  console.log('PASS Browser: empty WooCommerce cart shows accessible checkout guide and disabled notice');

  const checkoutUrl = process.env.ADIM_CI_CHECKOUT_URL;
  assert(checkoutUrl, 'disposable WooCommerce checkout page URL provided');
  const checkoutPage = await page.goto(checkoutUrl, { waitUntil: 'domcontentloaded' });
  assert(checkoutPage?.status() === 200, 'empty checkout resolves without creating an order');
  const finalCheckoutUrl = new URL(page.url());
  const requestedCheckoutUrl = new URL(checkoutUrl);
  const atCheckout = finalCheckoutUrl.pathname === requestedCheckoutUrl.pathname
    && finalCheckoutUrl.searchParams.get('page_id') === requestedCheckoutUrl.searchParams.get('page_id');
  if (atCheckout) {
    // In configurations that keep empty checkout visible, explanatory guidance
    // and sales-off notice must be present.
    await page.locator('.adim-checkout-help').waitFor({ state: 'visible' });
    await page.locator('.adim-checkout-alert').waitFor({ state: 'visible' });
    console.log('PASS Browser: native empty checkout renders customer delivery guidance');
  } else {
    // Current WooCommerce commonly redirects empty checkout to the cart;
    // do not force a checkout page or fabricate a payment option.
    assert.equal(finalCheckoutUrl.origin, new URL(cartHref).origin,
      'empty checkout stays on same store');
    assert.equal(finalCheckoutUrl.pathname, new URL(cartHref).pathname,
      'native empty checkout returns customer to cart');
    await page.locator('.adim-journey').waitFor({ state: 'visible' });
    await page.locator('.adim-checkout-alert').waitFor({ state: 'visible' });
    console.log('PASS Browser: native empty checkout redirects to cart with truthful sales-off notice');
  }
  const checkoutWidth = await page.evaluate(() => ({
    document: document.documentElement.scrollWidth,
    viewport: document.documentElement.clientWidth,
  }));
  assert(checkoutWidth.document <= checkoutWidth.viewport + 2,
    'empty-checkout destination has no horizontal overflow at 390px');
  await page.screenshot({ path: 'browser-screenshots/checkout-empty-390.png', fullPage: true });
  await page.close();
} finally {
  await browser.close();
}
