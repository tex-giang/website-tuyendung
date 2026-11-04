import { chromium } from "playwright";
const browser = await chromium.launch({ executablePath: "C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", headless: true });
const page = await (await browser.newContext({ viewport: { width: 1440, height: 1000 } })).newPage();
await page.goto("http://127.0.0.1:4173/index.html", { waitUntil: "networkidle" });
await page.waitForSelector("#urgent-dialog[open]", { timeout: 4000 }).then(() => page.locator("#urgent-dialog [data-urgent-close]").first().click()).catch(() => {});
await page.waitForTimeout(300);
await page.evaluate(() => {
  window.__ev = [];
  ["pointerdown","pointermove","pointerup","pointercancel","dragstart","mousedown","mouseup","click"].forEach(t => {
    document.addEventListener(t, e => { if (window.__ev.length < 40) window.__ev.push(t + " @" + Math.round(e.clientX) + " tgt=" + (e.target.tagName) + "." + String(e.target.className).slice(0,25)); }, true);
  });
});
const box = await page.locator(".job-slider__viewport").boundingBox();
await page.mouse.move(box.x + box.width * 0.8, box.y + box.height / 2);
await page.mouse.down();
await page.mouse.move(box.x + box.width * 0.2, box.y + box.height / 2, { steps: 8 });
await page.mouse.up();
await page.waitForTimeout(500);
console.log((await page.evaluate(() => window.__ev)).join("\n"));
await browser.close();
