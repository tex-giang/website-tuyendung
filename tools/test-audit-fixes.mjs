import { chromium } from "playwright";
import { createServer } from "node:http";
import { readFile, mkdir, writeFile } from "node:fs/promises";
import path from "node:path";
import assert from "node:assert/strict";
const root = process.cwd(),
  out = path.join(root, ".taste-skill/audit-fixes");
await mkdir(out, { recursive: true });
const mime = {
  ".html": "text/html;charset=utf-8",
  ".js": "text/javascript;charset=utf-8",
  ".css": "text/css;charset=utf-8",
  ".webp": "image/webp",
  ".png": "image/png",
  ".svg": "image/svg+xml",
};
const server = createServer(async (req, res) => {
  try {
    const url = new URL(req.url, "http://local");
    const f = path.resolve(root, "." + decodeURIComponent(url.pathname));
    if (!f.startsWith(root + path.sep)) throw Error();
    const body = await readFile(f);
    res.writeHead(200, {
      "Content-Type": mime[path.extname(f)] || "application/octet-stream",
    });
    res.end(body);
  } catch {
    res.writeHead(404);
    res.end();
  }
});
await new Promise((r) => server.listen(0, "127.0.0.1", r));
const base = "http://127.0.0.1:" + server.address().port;
const browser = await chromium.launch({
  executablePath:
    "C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe",
  headless: true,
});
const results = [];
let context, page;
async function reset(width = 1440) {
  await context?.close();
  context = await browser.newContext({
    viewport: { width, height: width < 600 ? 844 : 1000 },
  });
  page = await context.newPage();
  page.setDefaultTimeout(6000);
  page.on("dialog", (d) => d.accept());
}
async function go(route) {
  await page.goto(base + "/" + route, { waitUntil: "domcontentloaded" });
}
async function fill(name = "Ứng viên kiểm thử", position = "qc") {
  await page.locator("#apply-fullname").fill(name);
  await page.locator("#apply-phone").fill("+84 918 123 456");
  await page.locator("#apply-position").selectOption(position);
  await page.locator("#apply-company").selectOption("cai-lay");
  await page.locator("#apply-address").fill("Địa chỉ kiểm thử");
  await page
    .locator("#apply-note")
    .fill("5 năm kinh nghiệm QC; mong muốn Cai Lậy.");
  await page.locator("#apply-terms").check();
}
async function submit() {
  await page
    .locator("#online-apply-form button[type=submit],.apply-dialog__submit")
    .last()
    .click();
}
async function check(name, fn) {
  await reset();
  try {
    await fn();
    results.push({ name, passed: true });
    console.log("PASS", name);
  } catch (e) {
    results.push({ name, passed: false, error: e.message });
    console.log("FAIL", name, e.message);
  }
}
await check(
  "F01/F02 application identity, nullable birth, normalized phone and reload preserve all edits",
  async () => {
    await go("apply.html?jobId=TG-02&khu=tan-huong");
    assert.equal(await page.locator("#apply-position").inputValue(), "qc");
    await fill();
    await submit();
    await page.waitForURL("**/application-success.html");
    const pending = await page.evaluate(() => TGPendingApplications.read()[0]);
    assert.equal(pending.jobId, "TG-02");
    assert.equal(pending.phone, "0918123456");
    assert.equal(pending.birth, null);
    assert.equal(pending.locationCode, "cai-lay");
    assert.match(pending.message, /5 năm/);
    await go("admin/applications.html");
    let cs = await page.evaluate(() => AdminData.snapshot().candidates);
    assert.equal(cs.length, 5);
    assert.equal(cs.find((c) => c.id === pending.id).jobId, "TG-02");
    await page.evaluate(
      (id) =>
        AdminData.update((s) => {
          s.candidates.find((c) => c.id === id).status = "contacted";
          s.jobs[1].title = "QC sửa trong phiên";
        }),
      pending.id,
    );
    await page.reload();
    const state = await page.evaluate(() => ({
      s: AdminData.snapshot(),
      recovered: AdminData.recovered,
    }));
    assert.equal(state.recovered, false);
    assert.equal(state.s.candidates.length, 5);
    assert.equal(
      state.s.candidates.find((c) => c.id === pending.id).status,
      "contacted",
    );
    assert.equal(state.s.jobs[1].title, "QC sửa trong phiên");
    await go("admin/application-detail.html?id=" + pending.id);
    assert.match(await page.locator("#candidate-detail").innerText(), /5 năm/);
    assert.match(
      await page.locator("#candidate-detail").innerText(),
      /Khu Cai Lậy/,
    );
    assert.equal(
      await page
        .locator("dt")
        .filter({ hasText: "Năm sinh" })
        .locator("..")
        .locator("dd")
        .innerText(),
      "—",
    );
  },
);
await check(
  "F03 all homepage cards navigate to their matching vacancy",
  async () => {
    for (const [i, id] of ["TG-01", "TG-02", "TG-03"].entries()) {
      await go("index.html");
      await page.locator(".job-card h3 a").nth(i).click();
      assert.equal(new URL(page.url()).searchParams.get("id"), id);
      assert.match(
        await page.locator("h1").innerText(),
        i === 0 ? /may/ : i === 1 ? /QC/ : /IE/,
      );
    }
  },
);
await check(
  "F04 each news ID renders matching content, metadata, related links; missing ID ->404",
  async () => {
    await go("news.html");
    const posts = await page.evaluate(() =>
      TGNews.map((p) => ({ id: p.id, title: p.title, excerpt: p.excerpt })),
    );
    assert.equal(posts.length, 15);
    for (const p of posts) {
      await go("news-detail.html?id=" + p.id);
      assert.equal(await page.locator("h1").innerText(), p.title);
      assert.equal(
        await page.locator("meta[name=description]").getAttribute("content"),
        p.excerpt,
      );
      assert.equal(
        await page.locator('.news-related a[href*="id=' + p.id + '"]').count(),
        0,
      );
    }
    await go("news-detail.html?id=unknown");
    await page.waitForURL("**/404.html");
  },
);
await check(
  "F05 quota failure keeps form values, prevents false success and allows one retry",
  async () => {
    await go("apply.html");
    await fill();
    await page.evaluate(() => {
      window.realSet = Storage.prototype.setItem;
      Storage.prototype.setItem = () => {
        throw new DOMException("Simulated quota", "QuotaExceededError");
      };
    });
    await submit();
    await page.waitForFunction(() =>
      document
        .querySelector("#apply-error-summary")
        .textContent.includes("Chưa lưu"),
    );
    assert.match(page.url(), /apply.html$/);
    assert.equal(
      await page.locator("#apply-fullname").inputValue(),
      "Ứng viên kiểm thử",
    );
    assert.equal(await page.locator("button[type=submit]").isEnabled(), true);
    await page.evaluate(() => (Storage.prototype.setItem = window.realSet));
    await submit();
    await page.waitForURL("**/application-success.html");
    assert.equal(
      await page.evaluate(() => TGPendingApplications.read().length),
      1,
    );
  },
);
await check(
  "queue import storage failure retains queue and does not publish unpersisted candidate",
  async () => {
    await go("apply.html");
    await fill();
    await submit();
    await page.waitForURL("**/application-success.html");
    await page.addInitScript(() => {
      const real = Storage.prototype.setItem;
      Storage.prototype.setItem = function (k, v) {
        if (k === "texgiang_admin_demo_v2")
          throw new DOMException("Simulated quota", "QuotaExceededError");
        return real.call(this, k, v);
      };
    });
    await go("admin/applications.html");
    assert.equal(
      await page.evaluate(() => AdminData.snapshot().candidates.length),
      4,
    );
    assert.equal(
      await page.evaluate(
        () =>
          JSON.parse(sessionStorage.getItem("texgiang_pending_applications"))
            .length,
      ),
      1,
    );
    assert.equal(await page.evaluate(() => AdminData.importError), true);
  },
);
await check('F06 website application stays in dialog with vacancy prefill and inline validation',async()=>{
 await go('job-detail.html?id=TG-04');
 await page.locator('main aside a[href^="apply.html"]').first().click();
 assert.equal(await page.locator('#apply-position').inputValue(),'merchandiser');
 assert.equal(await page.locator('#apply-company').inputValue(),'cai-lay');
 assert.equal(await page.locator('#apply-dialog[open]').count(),1);
 assert.equal(await page.locator('[data-tg-apply-page-link]').count(),0);
 assert.equal(new URL(page.url()).searchParams.get('id'),'TG-04');
 await submit();
 assert.equal(await page.locator('#apply-fullname').getAttribute('aria-invalid'),'true');
 assert.ok(await page.locator('#apply-fullname').getAttribute('aria-describedby'));
});
await check(
  "unknown position retained, no first-job fallback; repeated import is idempotent",
  async () => {
    await go("apply.html?jobId=UNKNOWN");
    assert.equal(await page.locator("#apply-position").inputValue(), "other");
    await fill("Hồ sơ chưa xác định", "other");
    await submit();
    await page.waitForURL("**/application-success.html");
    const saved = await page.evaluate(() =>
      sessionStorage.getItem("texgiang_pending_applications"),
    );
    await go("admin/applications.html");
    assert.equal(
      await page.evaluate(() => AdminData.snapshot().candidates[0].jobId),
      "",
    );
    await page.evaluate(
      (s) => sessionStorage.setItem("texgiang_pending_applications", s),
      saved,
    );
    await page.reload();
    assert.equal(
      await page.evaluate(() => AdminData.snapshot().candidates.length),
      5,
    );
  },
);
await check(
  "F07 expired/public/admin state and counts agree; default list prioritizes open jobs",
  async () => {
    await go("jobs.html");
    const counts = await page.evaluate(() => ({
      expected: TGJobs.all.filter((j) => TGJobs.status(j) === "published")
        .length,
      shown: Number(document.querySelector("#results-count").textContent),
    }));
    assert.equal(counts.expected, counts.shown);
    await go("jobs.html?status=all&keyword=Kế toán");
    assert.match(await page.locator(".job-row").innerText(), /Hết hạn/);
    assert.equal(
      await page.locator(".job-row a").filter({ hasText: "Ứng tuyển" }).count(),
      0,
    );
    await go("job-detail.html?id=TG-06");
    assert.match(await page.locator("main aside").innerText(), /Đã kết thúc/);
    assert.match(
      await page
        .locator('main aside a[href^="apply.html"]')
        .first()
        .innerText(),
      /dự phòng/,
    );
    await go("admin/jobs.html");
    assert.equal(
      await page.evaluate(() => AdminData.jobStatus(AdminData.job("TG-06"))),
      "expired",
    );
  },
);
await check(
  "untrusted pending values never become executable markup",
  async () => {
    await go("apply.html");
    await page.evaluate(() =>
      sessionStorage.setItem(
        "texgiang_pending_applications",
        JSON.stringify([
          {
            id: "pbad",
            name: "Bad",
            phone: "0918123456",
            birth: "<img src=x onerror=window.injected=true>",
          },
        ]),
      ),
    );
    await go("admin/applications.html");
    assert.equal(await page.evaluate(() => window.injected), undefined);
    assert.equal(await page.evaluate(() => AdminData.importError), true);
    assert.equal(
      await page.evaluate(() => AdminData.snapshot().candidates.length),
      4,
    );
  },
);
await check(
  "mobile logo exactly 60x60 across public, standalone and admin; no overflow",
  async () => {
    for (const width of [360, 390, 767]) {
      await page.setViewportSize({ width, height: 844 });
      for (const route of [
        "index.html",
        "jobs.html",
        "apply.html",
        "ung-tuyen.html",
        "admin/applications.html",
        "admin/login.html",
      ]) {
        await go(route);
        const logo = page.locator(
          route.startsWith("admin/")
            ? route.includes("login")
              ? ".login-brand img"
              : ".admin-mobile-logo img"
            : route.startsWith("ung-")
              ? ".gf-brand img"
              : ".site-logo",
        );
        const box = await logo.boundingBox();
        assert.equal(box.width, 60, route);
        assert.equal(box.height, 60, route);
        assert.equal(
          await page.evaluate(
            () => document.documentElement.scrollWidth > innerWidth,
          ),
          false,
          route,
        );
      }
    }
  },
);
await check(
  "new mobile compositions render with complete content and visible first job",
  async () => {
    await page.setViewportSize({ width: 390, height: 844 });
    for (const route of [
      "index",
      "jobs",
      "ung-tuyen",
      "news",
      "news-detail",
      "admin/applications",
    ]) {
      await go(route + ".html");
      await page.waitForTimeout(1700);
      await page.evaluate(async()=>{document.querySelectorAll('img[loading=lazy]').forEach(i=>i.loading='eager');await Promise.race([Promise.all([...document.images].filter(i=>i.id!=='qr-code-img').map(i=>i.decode().catch(()=>{}))),new Promise(r=>setTimeout(r,3000))]);for(let y=0;y<document.body.scrollHeight;y+=500){window.scrollTo(0,y);await new Promise(r=>setTimeout(r,30));}window.scrollTo(0,0);});await page.waitForTimeout(600);
      await page.screenshot({
        path: path.join(out, route.replace("/", "-") + "-390.png"),
        fullPage: true,
      });
    }
    await go("jobs.html");
    const y = (await page.locator(".job-row").first().boundingBox()).y;
    assert.ok(y < 650, "First job should start before 650px; got " + y);
  },
);
await check('mobile secondary filters expand without truncation; dashboard includes new website source',async()=>{
  await page.setViewportSize({width:390,height:844});await go('admin/applications.html');
  assert.equal(await page.locator('.admin-extra-filters').getAttribute('open'),null);
  assert.equal(await page.locator('#admin-status-filter').isVisible(),true);
  await page.locator('.admin-extra-filters summary').click();
  await page.locator('#admin-position-filter').selectOption('TG-02');
  await page.setViewportSize({width:1440,height:1000});await page.waitForTimeout(100);
  const tops=await page.locator('#admin-candidate-search,#admin-status-filter,#admin-position-filter,#admin-company-filter').evaluateAll(es=>es.map(e=>e.getBoundingClientRect().top));
  assert.ok(Math.max(...tops)-Math.min(...tops)<3,'Desktop filters must share a row');
  assert.equal(await page.locator('#candidate-table-body tr').count(),1);
  await go('apply.html');await fill();await submit();await page.waitForURL('**/application-success.html');
  await go('admin/dashboard.html');const labels=await page.locator('#dashboard-sources .bar-track').evaluateAll(es=>es.map(e=>e.getAttribute('aria-label')));
  assert.ok(labels.includes('Website: 3 trong 5 hồ sơ'));
});
await context?.close();
await browser.close();
await new Promise((r) => server.close(r));
await writeFile(
  path.join(out, "report.json"),
  JSON.stringify(results, null, 2),
);
console.log(
  JSON.stringify(
    { checks: results.length, failures: results.filter((r) => !r.passed) },
    null,
    2,
  ),
);
if (results.some((r) => !r.passed)) process.exitCode = 1;
