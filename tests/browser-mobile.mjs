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
      await page.waitForFunction(() => document.body.classList.contains('adim-js'));
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
  await page.close();
} finally {
  await browser.close();
}
