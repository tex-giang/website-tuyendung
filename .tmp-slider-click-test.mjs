import { chromium } from "playwright";
const browser = await chromium.launch({ executablePath: "C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe", headless: true });
const page = await (await browser.newContext({ viewport: { width: 1440, height: 1000 } })).newPage();
const errors = []; page.on("pageerror", e => errors.push(e.message));
await page.goto("http://127.0.0.1:4173/index.html", { waitUntil: "networkidle" });
await page.waitForSelector("#urgent-dialog[open]", { timeout: 4000 }).then(() => page.locator("#urgent-dialog [data-urgent-close]").first().click()).catch(() => {});
await page.waitForTimeout(300);

// 1) click "Ứng tuyển" trong slider -> mở dialog ứng tuyển
await page.locator('[data-job-slider] a[href^="apply.html?job="]').first().click();
await page.waitForSelector("#apply-dialog[open]", { timeout: 5000 });
console.log("apply dialog from job card:", await page.locator("#apply-position").inputValue());
await page.locator(".apply-dialog__close").click();
await page.waitForTimeout(300);

// 2) drag desktop
const trackX = () => page.locator("[data-job-track]").evaluate(el => { const m = /translateX\((-?[\d.]+)%\)/.exec(el.style.transform); return m ? Number(m[1]) : 0; });
await page.evaluate(() => document.querySelector("[data-job-slider]").scrollIntoView({ block: "center" }));
await page.waitForTimeout(300);
const b = await page.locator(".job-slider__viewport").boundingBox();
await page.mouse.move(b.x + b.width * 0.8, b.y + b.height / 2);
await page.mouse.down();
await page.mouse.move(b.x + b.width * 0.2, b.y + b.height / 2, { steps: 12 });
await page.mouse.up();
await page.waitForTimeout(750);
console.log("after drag left:", await trackX(), "url:", page.url());

// 3) click "Xem chi tiết" sau khi kéo: click thường vẫn phải điều hướng
await page.locator('[data-job-slider] .job-slider__page').first().locator('a[href^="job-detail.html"]').first().click();
await page.waitForLoadState("networkidle");
console.log("detail url:", page.url());
console.log("errors:", errors.length ? errors : "none");
await browser.close();
