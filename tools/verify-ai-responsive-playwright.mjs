import { mkdir, writeFile } from 'node:fs/promises';
import { chromium } from '@playwright/test';

const url = process.argv[2] || 'http://localhost/noir/public/store';
const outDir = 'storage/logs/ai-responsive';
const viewports = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'tablet', width: 820, height: 1180 },
  { name: 'mobile', width: 390, height: 844 },
  { name: 'short-mobile', width: 360, height: 560 },
];

await mkdir(outDir, { recursive: true });

const browser = await chromium.launch({
  headless: true,
  args: [
    '--disable-gpu',
    '--disable-gpu-sandbox',
    '--disable-accelerated-2d-canvas',
    '--disable-webgl',
    '--disable-3d-apis',
  ],
});

const results = [];

try {
  for (const viewport of viewports) {
    const page = await browser.newPage({ viewport });
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.locator('.noir-ai-launcher').click({ timeout: 5000 });
    await page.waitForTimeout(500);

    const screenshot = `${outDir}/pw-${viewport.name}.png`;
    await page.screenshot({ path: screenshot, fullPage: false });

    const metrics = await page.evaluate(() => {
      const rect = (selector) => {
        const el = document.querySelector(selector);
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return {
          top: r.top,
          right: r.right,
          bottom: r.bottom,
          left: r.left,
          width: r.width,
          height: r.height,
        };
      };
      const selectors = [
        '.noir-ai-panel',
        '.noir-ai-header',
        '.noir-ai-messages',
        '.noir-ai-suggestions',
        '.noir-ai-form',
        '.noir-ai-disclaimer',
        '.noir-ai-message-copy',
      ];
      const clipped = selectors
        .map((selector) => ({ selector, rect: rect(selector) }))
        .filter((item) => item.rect && (
          item.rect.left < -1 ||
          item.rect.top < -1 ||
          item.rect.right > window.innerWidth + 1 ||
          item.rect.bottom > window.innerHeight + 1
        ));
      const messages = document.querySelector('.noir-ai-messages');
      const panel = document.querySelector('.noir-ai-panel');

      return {
        viewport: { width: window.innerWidth, height: window.innerHeight },
        open: panel ? !panel.hidden : false,
        panel: rect('.noir-ai-panel'),
        messages: messages ? {
          ...rect('.noir-ai-messages'),
          scrollHeight: messages.scrollHeight,
          clientHeight: messages.clientHeight,
        } : null,
        form: rect('.noir-ai-form'),
        suggestions: rect('.noir-ai-suggestions'),
        disclaimer: rect('.noir-ai-disclaimer'),
        clipped,
      };
    });

    results.push({ name: viewport.name, screenshot, ...metrics });
    await page.close();
  }
} finally {
  await browser.close();
}

await writeFile(`${outDir}/playwright-report.json`, JSON.stringify(results, null, 2));
console.log(JSON.stringify(results, null, 2));

const failures = results.flatMap((result) => {
  const issues = [];
  if (!result.open) issues.push(`${result.name}: panel did not open`);
  if (!result.panel || result.panel.width < 280 || result.panel.height < 320) issues.push(`${result.name}: panel is too small`);
  if (result.clipped.length) issues.push(`${result.name}: ${result.clipped.length} element(s) overflow viewport`);
  if (!result.messages || result.messages.clientHeight < 100) issues.push(`${result.name}: message area is too short`);
  return issues;
});

if (failures.length) {
  console.error(failures.join('\n'));
  process.exitCode = 1;
}
