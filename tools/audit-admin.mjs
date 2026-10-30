import { chromium } from "playwright";
import { createServer } from "node:http";
import { createReadStream } from "node:fs";
import { stat, mkdir, writeFile, readFile } from "node:fs/promises";
import path from "node:path";
import assert from "node:assert/strict";

const root = process.cwd(),
  out = path.join(root, ".taste-skill/admin-final");
await mkdir(out, { recursive: true });
const mime = {
  ".html": "text/html;charset=utf-8",
  ".js": "text/javascript;charset=utf-8",
  ".css": "text/css;charset=utf-8",
  ".svg": "image/svg+xml",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".webp": "image/webp",
};
const server = createServer(async (req, res) => {
  try {
    const filename = path.resolve(
      root,
      "." + decodeURIComponent(new URL(req.url, "http://local").pathname),
    );
    if (!filename.startsWith(root + path.sep)) {
      res.writeHead(403);
      res.end();
      return;
    }
    const info = await stat(filename),
      file = info.isDirectory() ? path.join(filename, "index.html") : filename;
    res.writeHead(200, {
      "Content-Type": mime[path.extname(file)] || "application/octet-stream",
      "Cache-Control": "no-store",
    });
    createReadStream(file).pipe(res);
  } catch (_) {
    res.writeHead(404);
    res.end("not found");
  }
});
await new Promise((r) => server.listen(0, "127.0.0.1", r));
const base = "http://127.0.0.1:" + server.address().port;
const browser = await chromium.launch({
  executablePath:
    "C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe",
  headless: true,
});
const context = await browser.newContext({ acceptDownloads: true }),
  page = await context.newPage();
let runtime = [];
page.on("pageerror", (e) => runtime.push(e.message));
page.on("dialog", (d) =>
  d.type() === "beforeunload" ? d.accept() : d.dismiss(),
);
const failures = [],
  renders = [],
  checks = [];
const names = [
  "dashboard",
  "applications",
  "application-detail",
  "jobs",
  "job-form",
  "content",
  "banners",
  "settings",
  "login",
];
const go = async (name, query = "") => {
  await page.goto(base + "/admin/" + name + ".html" + query, {
    waitUntil: "networkidle",
  });
};
async function check(name, fn) {
  try {
    await fn();
    checks.push({ name, passed: true });
    console.log("PASS", name);
  } catch (e) {
    checks.push({ name, passed: false, error: e.message });
    failures.push({ name, error: e.message });
    console.log("FAIL", name, e.message.slice(0, 300));
  }
}
try {
  for (const name of names) {
    for (const width of [1536, 1440, 1024, 768, 390]) {
      runtime = [];
      await page.setViewportSize({ width, height: width < 600 ? 844 : 900 });
      await go(name, name === "application-detail" ? "?id=2" : "");
      await page.evaluate(() => document.fonts.ready);
      const m = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth > innerWidth,
        h1: document.querySelectorAll("h1").length,
        duplicateIds: [...document.querySelectorAll("[id]")]
          .map((e) => e.id)
          .filter((id, i, all) => all.indexOf(id) !== i),
        brokenImages: [...document.images]
          .filter((e) => !e.complete || !e.naturalWidth)
          .map((e) => e.src),
        unlabelled: [
          ...document.querySelectorAll(
            "input:not([type=hidden]),select,textarea",
          ),
        ]
          .filter(
            (e) =>
              e.getClientRects().length &&
              !e.labels?.length &&
              !e.getAttribute("aria-label"),
          )
          .map((e) => e.id),
        clipped: [
          ...document.querySelectorAll("main h1,main h2,main p,main .tg-btn"),
        ]
          .filter((e) => {
            const r = e.getBoundingClientRect();
            return r.width && (r.left < 0 || r.right > innerWidth + 1);
          })
          .map((e) => e.textContent.trim().slice(0, 40)),
      }));
      const issues = [
        ...(m.overflow ? ["overflow"] : []),
        ...(m.h1 !== 1 ? ["h1"] : []),
        ...m.duplicateIds,
        ...m.brokenImages,
        ...m.unlabelled,
        ...m.clipped,
        ...runtime,
      ];
      if (issues.length) failures.push({ name, width, issues });
      renders.push({ name, width, ...m, runtime: [...runtime] });
      await page.screenshot({
        path: path.join(out, name + "-" + width + ".png"),
        fullPage: true,
      });
    }
    console.log("rendered", name);
  }
  await page.setViewportSize({ width: 1440, height: 900 });
  await check("combined filters, empty state, CSV scope", async () => {
    await go("applications");
    await page.locator("#admin-candidate-search").fill("Mai");
    await page.locator("#admin-status-filter").selectOption("contacted");
    assert.equal(await page.locator("[data-candidate-id]").count(), 0);
    await page.locator("[data-reset-filters]").first().click();
    await page.locator("#admin-candidate-search").fill("Bảo");
    assert.equal(await page.locator("[data-candidate-id]").count(), 1);
    const download = page.waitForEvent("download");
    await page.locator("[data-export]").click();
    const file = await download;
    assert.match(file.suggestedFilename(), /\.csv$/);
    const data = await readFile(await file.path(), "utf8");
    assert.match(data, /Trần Quốc Bảo/);
    assert(!data.includes("Lê Thị Mai"));
  });
  await check("modal exact candidate, persistence and timeline", async () => {
    await go("applications");
    await page.locator('[data-status-candidate="2"]').click();
    assert.equal(
      await page.locator("#modal-status-select").inputValue(),
      "contacted",
    );
    await page.locator("#modal-status-select").selectOption("interview");
    await page.locator("#modal-note").fill("Hẹn trao đổi lúc 14 giờ.");
    await page.locator("#candidate-status-form button[type=submit]").click();
    await page.waitForFunction(
      () => !document.querySelector("#status-update-modal").open,
    );
    assert.match(
      await page.locator('[data-candidate-id="2"]').innerText(),
      /Hẹn phỏng vấn/,
    );
    await go("application-detail", "?id=2");
    assert.equal(await page.locator("h1").innerText(), "Trần Quốc Bảo");
    assert.match(
      await page.locator(".timeline").innerText(),
      /Hẹn trao đổi lúc 14 giờ/,
    );
  });
  await check("missing IDs never load first record", async () => {
    await go("application-detail", "?id=missing");
    assert.match(await page.locator("h1").innerText(), /Không tìm thấy/);
    await go("job-form", "?id=missing");
    assert.match(await page.locator("h1").innerText(), /Không tìm thấy/);
  });
  await check(
    "job ID, preview escaping, validation, save, close/reopen",
    async () => {
      await go("job-form", "?id=TG-02");
      assert.match(await page.locator("#job-title").inputValue(), /QC/);
      await page
        .locator("#job-title")
        .fill('QC <img src=x onerror="window.injected=true">');
      await page.locator("[data-preview-job]").click();
      assert.equal(
        await page.locator("#admin-preview-body img[src=x]").count(),
        0,
      );
      await page.locator("#admin-preview [data-close-dialog]").click();
      await page.locator("#job-salary-min").fill("20000000");
      await page.locator("#job-salary-max").fill("10000000");
      await page.locator("#job-editor-form [data-save]").click();
      assert.match(
        await page.locator("#job-salary-max-error").innerText(),
        /Mức đến/,
      );
      await page.locator("#job-salary-max").fill("25000000");
      await page.locator("#job-title").fill("Giám sát kiểm phẩm QC mới");
      await page.locator("#job-editor-form [data-save]").click();
      await page.waitForFunction(
        () =>
          document.querySelector("[data-save]").disabled &&
          !document.querySelector('[aria-busy="true"]'),
      );
      await go("jobs", "?keyword=TG-02");
      await page.locator('[data-job-id="TG-02"] summary').click();
      await page.locator('[data-toggle-job="TG-02"]').click();
      await page.locator("#confirm-action").click();
      assert.match(
        await page.locator('[data-job-id="TG-02"]').innerText(),
        /Đã đóng/,
      );
      await page.locator('[data-job-id="TG-02"] summary').click();
      await page.locator('[data-toggle-job="TG-02"]').click();
      await page.locator("#confirm-action").click();
      assert.match(
        await page.locator('[data-job-id="TG-02"]').innerText(),
        /Đang tuyển/,
      );
    },
  );
  await check("create clean job and persist draft", async () => {
    await go("job-form");
    assert.equal(await page.locator("#job-title").inputValue(), "");
    await page.locator("#job-title").fill("Tin thử nghiệm nội bộ");
    await page.locator("[data-save-draft]").click();
    await page.waitForFunction(() => location.search.includes("id="));
    await go("jobs", "?status=draft");
    assert.match(
      await page.locator("#job-list").innerText(),
      /Tin thử nghiệm nội bộ/,
    );
  });
  await check("settings validation, counters and saved state", async () => {
    await go("settings");
    await page.locator("#set-page-size").fill("0");
    await page.locator("[form=settings-form]").click();
    assert.equal(
      await page.locator("#set-page-size").getAttribute("aria-invalid"),
      "true",
    );
    await page.locator("#set-page-size").fill("2");
    await page.locator("#set-seo-title").fill("Tuyển dụng đã chỉnh");
    assert.match(await page.locator("#seo-preview").innerText(), /đã chỉnh/);
    await page.locator("[form=settings-form]").click();
    await page.waitForFunction(
      () =>
        document.querySelector("[data-save]").disabled &&
        !document.querySelector('[aria-busy="true"]'),
    );
    await go("settings");
    assert.equal(await page.locator("#set-page-size").inputValue(), "2");
  });
  await check("content preview inert and post editor saves", async () => {
    await go("content");
    await page
      .locator("#cms-slogan")
      .fill('<svg onload="window.injected=true">');
    assert.equal(await page.locator("#content-preview svg").count(), 0);
    await page.locator("[form=content-editor-form]").click();
    await page.waitForFunction(
      () =>
        document.querySelector("[data-save]").disabled &&
        !document.querySelector('[aria-busy="true"]'),
    );
    await page.locator("[data-content-tab=posts]").click();
    await page.locator('[data-edit-post="2"]').click();
    await page.locator("#post-title").fill("Bài viết mẫu đã chỉnh");
    await page.locator("#post-editor-form button[type=submit]").click();
    await page.waitForFunction(
      () => !document.querySelector("#post-editor-dialog").open,
    );
    assert.match(await page.locator("#cms-post-list").innerText(), /đã chỉnh/);
  });
  await check(
    "banner file errors, real preview, alt and session save",
    async () => {
      await go("banners");
      const src = await page
        .locator("#banner-preview-image")
        .getAttribute("src");
      await page
        .locator("#banner-file")
        .setInputFiles({
          name: "bad.svg",
          mimeType: "image/svg+xml",
          buffer: Buffer.from("<svg/>"),
        });
      assert.match(await page.locator("#banner-file-error").innerText(), /JPG/);
      assert.equal(
        await page.locator("#banner-preview-image").getAttribute("src"),
        src,
      );
      await page
        .locator("#banner-file")
        .setInputFiles(path.join(root, "assets/images/banner-texgiang.webp"));
      await page.waitForFunction(() =>
        document.querySelector("#banner-preview-image").src.startsWith("data:"),
      );
      await page.locator("#banner-alt").fill("Ảnh banner demo đã chọn");
      await page.locator("[data-media-mode=phone]").click();
      assert(
        await page
          .locator("#banner-preview-wrap")
          .evaluate((e) => e.classList.contains("is-phone")),
      );
      await page.locator("[form=banner-form]").click();
      await page.waitForFunction(
        () =>
          document.querySelector("[data-save]").disabled &&
          !document.querySelector('[aria-busy="true"]'),
      );
      await go("banners");
      assert.equal(
        await page.locator("#banner-alt").inputValue(),
        "Ảnh banner demo đã chọn",
      );
    },
  );
  await check("mobile drawer Escape and dialog focus", async () => {
    await page.setViewportSize({ width: 390, height: 844 });
    await go("applications");
    await page.locator("#toggle-sidebar-btn").click();
    assert.equal(
      await page.locator("#toggle-sidebar-btn").getAttribute("aria-expanded"),
      "true",
    );
    await page.keyboard.press("Escape");
    assert.equal(
      await page.evaluate(() => document.activeElement.id),
      "toggle-sidebar-btn",
    );
    await page.locator("[data-status-candidate]").first().click();
    await page.keyboard.press("Escape");
    assert.equal(
      await page.locator("#status-update-modal").evaluate((e) => e.open),
      false,
    );
  });
  await check("dirty navigation guard and demo login", async () => {
    await go("job-form", "?id=TG-01");
    await page.locator("#job-title").fill("Chưa lưu");
    await page.locator('.admin-head-actions a[href="jobs.html"]').click();
    assert.equal(
      await page.locator("#admin-confirm").evaluate((e) => e.open),
      true,
    );
    await page.locator("#confirm-action").click();
    await page.waitForURL("**/jobs.html");
    await go("login");
    await page.locator("#login-username").fill("user");
    await page.locator("#login-password").fill("demo-only");
    await page.locator("button[type=submit]").click();
    assert.match(
      await page.locator(".form-errors").innerText(),
      /Chưa kết nối/,
    );
    await page.locator("#enter-demo").click();
    await page.waitForURL("**/dashboard.html");
  });
  await check(
    "storage failure keeps form and blocks false success",
    async () => {
      await go("settings");
      await page.locator("#set-site-name").fill("Không mất dữ liệu");
      await page.evaluate(() => {
        Storage.prototype.setItem = () => {
          throw new Error("QuotaExceededError");
        };
      });
      await page.locator("[form=settings-form]").click();
      await page.waitForFunction(() =>
        document
          .querySelector("#settings-form .form-errors")
          .textContent.includes("Chưa lưu được"),
      );
      assert.equal(
        await page.locator("#set-site-name").inputValue(),
        "Không mất dữ liệu",
      );
    },
  );
  await check("public regression and untouched pagination", async () => {
    for (const name of ["index", "jobs", "job-detail", "apply", "news"]) {
      await page.goto(base + "/" + name + ".html", {
        waitUntil: "networkidle",
      });
      assert.equal(await page.locator("h1").count(), 1);
      assert.equal(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= innerWidth,
        ),
        true,
      );
    }
    await page.goto(base + "/jobs.html", { waitUntil: "networkidle" });
    assert.equal(await page.locator(".job-row").count(), 6);
  });
  await check("all candidate and job IDs match their records", async () => {
    for (const id of ["1", "2", "3", "4"]) {
      await go("application-detail", "?id=" + id);
      const expected = await page.evaluate(id => AdminData.candidate(id).name, id);
      assert.equal(await page.locator("h1").innerText(), expected);
    }
    for (let n=1; n<=8; n++) {
      const id="TG-0"+n;
      await go("job-form", "?id=" + id);
      const expected=await page.evaluate(id=>AdminData.job(id).title,id);
      assert.equal(await page.locator("#job-title").inputValue(),expected);
    }
  });
  await check("closed mobile drawer is outside the keyboard order", async () => {
    await page.setViewportSize({width:390,height:844});
    await go("applications");
    assert.equal(await page.locator("#admin-sidebar").evaluate(e=>e.inert),true);
    await page.locator("#toggle-sidebar-btn").click();
    assert.equal(await page.locator("#admin-sidebar").evaluate(e=>e.inert),false);
    await page.keyboard.press("Escape");
    assert.equal(await page.locator("#admin-sidebar").evaluate(e=>e.inert),true);
  });
  await check("invalid stored session recovers without executing markup", async () => {
    await go("dashboard");
    await page.evaluate(()=>{const state=AdminData.snapshot();state.candidates[0].birth='<img src=x onerror="window.injected=true">';sessionStorage.setItem("texgiang_admin_demo_v2",JSON.stringify(state));});
    await go("application-detail","?id=1");
    assert.equal(await page.evaluate(()=>window.injected),undefined);
    assert.equal(await page.locator("#admin-recovery").isVisible(),true);
    assert.equal(await page.locator("h1").innerText(),"Lê Thị Mai");
  });
  await check("direct file and reduced motion", async () => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.goto(
      new URL("file:///" + root.replaceAll("\\", "/") + "/admin/dashboard.html")
        .href,
      { waitUntil: "networkidle" },
    );
    assert.equal(await page.locator("h1").innerText(), "Bảng điều khiển");
  });
} finally {
  await writeFile(
    path.join(out, "report.json"),
    JSON.stringify({ renders, checks, failures }, null, 2),
  );
  await browser.close();
  await new Promise((r) => server.close(r));
}
console.log(
  JSON.stringify(
    { renders: renders.length, checks: checks.length, failures },
    null,
    2,
  ),
);
if (failures.length) process.exitCode = 1;
