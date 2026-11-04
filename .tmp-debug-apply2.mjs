import { chromium } from "playwright";
const browser = await chromium.launch({ executablePath: "C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", headless: true });
const page = await (await browser.newContext({ viewport: { width: 1440, height: 1000 } })).newPage();
const errors = []; page.on("pageerror", e => errors.push(e.message));
await page.goto("http://127.0.0.1:4173/index.html", { waitUntil: "networkidle" });
console.log("TGApplyDialog:", await page.evaluate(() => typeof window.TGApplyDialog));
console.log("TGApplyForm:", await page.evaluate(() => typeof window.TGApplyForm));
console.log("dialog exists initial:", await page.locator("#apply-dialog").count());
await page.waitForSelector("#urgent-dialog[open]", { timeout: 4000 }).then(() => page.locator("#urgent-dialog [data-urgent-close]").first().click()).catch(() => {});
await page.waitForTimeout(300);
// header CTA click
await page.locator('#site-nav a[href="apply.html"]').first().click();
await page.waitForTimeout(600);
console.log("after header CTA dialog count:", await page.locator("#apply-dialog").count(), "open:", await page.locator("#apply-dialog").first().evaluate(el => el.open).catch(()=>"none"));
console.log("errors:", errors.length ? errors : "none");
await browser.close();
