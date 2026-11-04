import { chromium } from "playwright";
const browser = await chromium.launch({ executablePath: "C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", headless: true });
const ctx = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await ctx.newPage();
const errors = []; page.on("pageerror", e => errors.push(e.message));
const base = "http://127.0.0.1:4173";
const out = {};

await page.goto(`${base}/index.html`, { waitUntil: "networkidle" });
await page.waitForSelector("#urgent-dialog[open]", { timeout: 4000 });
out.autoOpenFirst = true;
// drag on job slider with mouse
await page.locator("#urgent-dialog [data-urgent-close]").first().click();
await page.waitForTimeout(250);
const trackX = () => page.locator("[data-job-track]").evaluate(el => { const m = /translateX\((-?[\d.]+)%\)/.exec(el.style.transform); return m ? Number(m[1]) : 0; });
out.beforeDrag = await trackX();
const box = await page.locator(".job-slider__viewport").boundingBox();
await page.mouse.move(box.x + box.width * 0.75, box.y + box.height / 2);
await page.mouse.down();
await page.mouse.move(box.x + box.width * 0.25, box.y + box.height / 2, { steps: 10 });
await page.mouse.up();
await page.waitForTimeout(700);
out.afterDrag = await trackX();
out.counts = await page.locator("[data-open-job-count]").allTextContents();

// urgent dialog: tick don't-show + Để sau, reload same day
await page.goto(`${base}/index.html`, { waitUntil: "networkidle" });
await page.waitForSelector("#urgent-dialog[open]", { timeout: 4000 });
await page.locator("#urgent-dialog [data-urgent-dont-show]").check();
await page.locator("#urgent-dialog [data-urgent-later]").click();
await page.waitForTimeout(300);
out.storedKey = await page.evaluate(() => localStorage.getItem("texgiang_urgent_hide_date"));
await page.reload({ waitUntil: "networkidle" });
await page.waitForTimeout(1400);
out.hiddenSameDay = (await page.locator("#urgent-dialog[open]").count()) === 0;
// simulate next day
await page.evaluate(() => localStorage.setItem("texgiang_urgent_hide_date", "2000-01-01"));
await page.reload({ waitUntil: "networkidle" });
await page.waitForSelector("#urgent-dialog[open]", { timeout: 4000 });
out.reopensNextDay = true;
await page.locator("#urgent-dialog [data-urgent-close]").first().click();
await page.evaluate(() => { try { localStorage.removeItem("texgiang_urgent_hide_date"); } catch(_) {} });

out.errors = errors;
console.log(JSON.stringify(out, null, 1));
await browser.close();
