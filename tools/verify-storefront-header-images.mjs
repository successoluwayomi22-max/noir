import { mkdir, writeFile } from 'node:fs/promises';
import { chromium } from '@playwright/test';

const baseUrl = process.argv[2] || 'http://127.0.0.1:8000';
const outDir = 'storage/logs/storefront-header-images';

await mkdir(outDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
const results = [];

async function checkHeader(path, name) {
  const page = await context.newPage();
  try {
    await page.goto(baseUrl + path, { waitUntil: 'domcontentloaded', timeout: 15000 });
  } catch (error) {
    results.push({ name, path, skipped: true, reason: error.message });
    await page.close();
    return;
  }
  const toggle = page.locator('.mobile-menu-toggle').first();
  const visible = await toggle.isVisible().catch(() => false);
  let open = false;
  let headerBox = null;
  let panelBox = null;
  let panelGap = null;
  if (visible) {
    await toggle.click();
    await page.waitForTimeout(100);
    const panel = page.locator('.mobile-menu-panel.is-open').first();
    open = await panel.isVisible().catch(() => false);
    headerBox = await page.locator('.site-header, .shop-header').first().boundingBox().catch(() => null);
    panelBox = open ? await panel.boundingBox() : null;
    panelGap = headerBox && panelBox ? Math.round(panelBox.y - (headerBox.y + headerBox.height)) : null;
  }
  const screenshot = `${outDir}/${name}.png`;
  await page.screenshot({ path: screenshot, fullPage: false });
  results.push({ name, path, visible, open, headerBox, panelBox, panelGap, screenshot });
  await page.close();
}

const page = await context.newPage();
await page.goto(baseUrl + '/store', { waitUntil: 'domcontentloaded', timeout: 15000 });
const firstProductHref = await page.locator('a[href*="/store/product/"]').first().getAttribute('href');
const productPath = firstProductHref ? new URL(firstProductHref, baseUrl).pathname : null;
await page.close();

await checkHeader('/store', 'store-mobile-header');
if (productPath) await checkHeader(productPath, 'product-mobile-header');
await checkHeader('/privacy-policy', 'privacy-mobile-header');
await checkHeader('/checkout', 'checkout-mobile-header');

const cataloguePage = await context.newPage();
await cataloguePage.goto(baseUrl + '/store', { waitUntil: 'domcontentloaded', timeout: 15000 });
const productImages = await cataloguePage.locator('.shop-catalogue-grid .store-card-media img').evaluateAll((images) => images.map((image) => ({
  alt: image.getAttribute('alt') || '',
  src: image.getAttribute('src') || '',
})));
await cataloguePage.close();
const srcCounts = productImages.reduce((counts, image) => {
  counts[image.src] = (counts[image.src] || 0) + 1;
  return counts;
}, {});
const repeated = Object.entries(srcCounts).filter(([, count]) => count > 1).map(([src, count]) => ({ src, count }));

results.push({
  name: 'catalogue-images',
  totalImages: productImages.length,
  uniqueImages: Object.keys(srcCounts).length,
  repeated,
  images: productImages,
});

await writeFile(`${outDir}/report.json`, JSON.stringify(results, null, 2));
console.log(JSON.stringify(results, null, 2));

await browser.close();

const failures = [];
for (const result of results.filter((item) => item.path)) {
  if (result.skipped) continue;
  if (!result.visible) failures.push(`${result.name}: hamburger not visible`);
  if (!result.open || !result.panelBox || result.panelBox.width < 260 || result.panelBox.height < 120) failures.push(`${result.name}: mobile menu did not open correctly`);
  if (typeof result.panelGap === 'number' && (result.panelGap < 0 || result.panelGap > 18)) failures.push(`${result.name}: mobile menu detached from header by ${result.panelGap}px`);
}

const imageResult = results.find((item) => item.name === 'catalogue-images');
if (imageResult && imageResult.totalImages > 1 && imageResult.repeated.length > 0) {
  failures.push(`catalogue-images: ${imageResult.repeated.length} repeated image source(s)`);
}

if (failures.length) {
  console.error(failures.join('\n'));
  process.exitCode = 1;
}
