import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = process.cwd();
const base = process.env.AUDIT_BASE_URL || 'http://127.0.0.1:4173';
const output = path.join(root, '.taste-skill/public-audit');
await mkdir(output, { recursive: true });
const pages = ['index','jobs','job-detail','apply','application-success','application-error','404','about','why-tex-giang','contact','news','news-detail'];
const widths = [1536,1440,1024,768,390];
const browser = await chromium.launch({ executablePath: 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', headless: true });
const results = [], failures = [];
const context = await browser.newContext();
const page = await context.newPage();
let runtime = [];
page.on('pageerror', error => runtime.push(error.message));
for (const name of pages) {
  for (const width of widths) {
    runtime = [];
    await page.setViewportSize({ width, height: width < 600 ? 844 : 1000 });
    await page.goto(`${base}/${name}.html`, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    await page.evaluate(async () => {
      document.querySelectorAll('img[loading="lazy"]').forEach(img => { img.loading = 'eager'; });
      await Promise.all([...document.images].filter(img => img.id !== 'qr-code-img').map(img => img.decode().catch(() => {})));
      for (let y=0;y<document.body.scrollHeight;y+=700) window.scrollTo(0,y);
      window.scrollTo(0,0);
    });
    await page.waitForTimeout(300);
    const metrics = await page.evaluate(() => {
      const h1 = document.querySelector('h1'), style = getComputedStyle(h1);
      const rect = h1.getBoundingClientRect();
      return {
        overflow: document.documentElement.scrollWidth > innerWidth,
        h1Count: document.querySelectorAll('h1').length,
        headlineLines: Math.round(rect.height / parseFloat(style.lineHeight)),
        missingImages: [...document.images].filter(img => img.id !== 'qr-code-img' && (!img.complete || !img.naturalWidth)).map(img => img.getAttribute('src')),
        duplicateIds: [...document.querySelectorAll('[id]')].map(el => el.id).filter((id,i,all) => all.indexOf(id) !== i),
        clipped: [...document.querySelectorAll('main h1,main h2,main p,main label,main .tg-btn')].filter(el => {
          const r = el.getBoundingClientRect(); return r.width && (r.left < -1 || r.right > innerWidth + 1);
        }).map(el => el.textContent.trim().slice(0,60))
      };
    });
    const issues = [];
    if (metrics.overflow) issues.push('horizontal overflow');
    if (metrics.h1Count !== 1) issues.push('h1 count ' + metrics.h1Count);
    if (metrics.headlineLines > 2) issues.push('headline ' + metrics.headlineLines + ' lines');
    if (metrics.missingImages.length) issues.push('broken images ' + metrics.missingImages.join(','));
    if (metrics.duplicateIds.length) issues.push('duplicate IDs');
    if (metrics.clipped.length) issues.push('clipped text: ' + metrics.clipped.join('; '));
    issues.push(...runtime);
    if (issues.length) failures.push({ name,width,issues });
    results.push({ name,width,...metrics,runtime:[...runtime] });
    await page.screenshot({ path: path.join(output,`${name}-${width}.png`), fullPage:true });
  }
  console.log('rendered', name);
}
const checks = [];
async function check(name, action) {
  try { await action(); checks.push({name,passed:true}); }
  catch(error) { checks.push({name,passed:false,error:error.message}); failures.push({name,issues:[error.message]}); }
}
await page.setViewportSize({width:1440,height:1000});
await check('department query, live filter, empty state, reset sort', async () => {
  await page.goto(`${base}/jobs.html?department=qc`);
  await page.waitForSelector('.job-row');
  assert.equal(await page.locator('.job-row').count(),1);
  assert.match(await page.locator('.job-row').innerText(),/QC/);
  await page.locator('#filter-keyword').fill('nothingmatches');
  await page.waitForFunction(() => !document.getElementById('jobs-empty-state').classList.contains('hidden'));
  await page.locator('#filter-sort').selectOption('urgent');
  await page.getByRole('button',{name:'Xoá hết',exact:true}).click();
  assert.equal(await page.locator('.job-row').count(),8);
  assert.equal(await page.locator('#filter-sort').inputValue(),'newest');
});
await check('filter user text is inert', async () => {
  await page.goto(`${base}/jobs.html?keyword=${encodeURIComponent('<img src=x onerror="window.injection=true">')}`);
  await page.waitForSelector('#active-filters-chips');
  assert.equal(await page.evaluate(() => window.injection),undefined);
  assert.equal(await page.locator('#active-filters-chips img[src="x"]').count(),0);
});
await check('mobile drawer, Escape, focus restore, menu', async () => {
  await page.setViewportSize({width:390,height:844});
  await page.goto(`${base}/jobs.html`);
  await page.locator('#toggle-mobile-filters').click();
  assert.equal(await page.locator('#toggle-mobile-filters').getAttribute('aria-expanded'),'true');
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('#toggle-mobile-filters').getAttribute('aria-expanded'),'false');
  assert.equal(await page.evaluate(() => document.activeElement.id),'toggle-mobile-filters');
  await page.locator('#mobile-menu-btn').click();
  assert.equal(await page.locator('#mobile-menu-btn').getAttribute('aria-expanded'),'true');
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('#mobile-menu-btn').getAttribute('aria-expanded'),'false');
});
await check('QR modal focus and Escape', async () => {
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(`${base}/jobs.html`);
  const button = page.locator('.open-qr-modal').first();
  await button.click();
  await page.waitForFunction(() => document.activeElement.id === 'close-qr-btn');
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('#qr-modal').isVisible(),false);
  assert.equal(await button.evaluate(el => el === document.activeElement),true);
});
await check('selected vacancy and company reach application', async () => {
  await page.goto(`${base}/job-detail.html?id=TG-02`);
  await page.waitForFunction(() => document.querySelector('h1').textContent.includes('QC'));
  assert.equal(await page.locator('main nav[aria-label="Breadcrumb"] > span:last-child').innerText(), 'Giám sát kiểm phẩm QC');
  const apply = page.locator('main aside a[href^="apply.html"]').first();
  await apply.click();
  await page.waitForLoadState('networkidle');
  assert.equal(await page.locator('#apply-position').inputValue(),'qc');
  assert.equal(await page.locator('#apply-company').inputValue(),'tg-apparel');
});
await check('inline required fields, invalid file, CV remove, demo submit', async () => {
  await page.goto(`${base}/apply.html`);
  await page.locator('button[type="submit"]').click();
  assert.equal(await page.locator('#apply-fullname').getAttribute('aria-invalid'),'true');
  assert.equal(await page.locator('#apply-address').getAttribute('aria-invalid'),'true');
  await page.locator('#cv-file-input').setInputFiles({name:'bad.exe',mimeType:'application/octet-stream',buffer:Buffer.from('invalid')});
  assert.match(await page.locator('#cv-error').innerText(),/PDF/);
  await page.locator('#cv-file-input').setInputFiles({name:'cv.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF-1.4 demo')});
  assert.equal(await page.locator('#file-preview-card').isVisible(),true);
  await page.locator('#remove-file-btn').click();
  assert.equal(await page.locator('#file-preview-card').isVisible(),false);
  await page.locator('#apply-fullname').fill('Nguyễn Thị An');
  await page.locator('#apply-phone').fill('0918123456');
  await page.locator('#apply-position').selectOption('cn-may');
  await page.locator('#apply-address').fill('Tân Hương');
  await page.locator('#apply-terms').check();
  await page.locator('button[type="submit"]').click();
  await page.waitForURL('**/application-success.html');
});
await check('legacy search redirect preserves query',async () => {
  await page.goto(`${base}/search-results.html?department=qc&company=tg-apparel`);
  await page.waitForURL(url => url.pathname.endsWith('/jobs.html') && url.searchParams.get('department') === 'qc' && url.searchParams.get('company') === 'tg-apparel');
  assert.equal(await page.locator('.job-row').count(),1);
});
await check('file URL and reduced motion', async () => {
  await page.emulateMedia({reducedMotion:'reduce'});
  await page.goto(new URL('file:///' + root.replaceAll('\\','/') + '/index.html').href);
  assert.equal(await page.locator('h1').isVisible(),true);
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),true);
});
await writeFile(path.join(output,'report.json'),JSON.stringify({results,checks,failures},null,2));
await browser.close();
console.log(JSON.stringify({renders:results.length,checks,failures},null,2));
if(failures.length) process.exitCode = 1;
