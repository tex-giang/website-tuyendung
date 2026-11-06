/**
 * Chuyển một trang HTML tĩnh (đã hoàn thiện design) sang PHP view.
 *
 * Cách dùng:
 *   node tools/port-static-page.mjs about
 *   node tools/port-static-page.mjs why-tex-giang
 *
 * Script chỉ xử lý phần <main id="main-content">...</main> và viết lại:
 *  - src/srcset/href của assets  -> <?= asset('...') ?>
 *  - link nội bộ .html           -> <?= url('...') ?>
 * Không tự bỏ nội dung demo — người port phải rà lại sau khi chạy.
 */
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';

const PAGES = {
  about: {
    source: 'E:/websitetuyendung/about.html',
    view: 'app/Views/public/about.php',
    bodyClass: 'tg-public page-about',
    nav: 'about',
  },
  'why-tex-giang': {
    source: 'E:/websitetuyendung/why-tex-giang.html',
    view: 'app/Views/public/why.php',
    bodyClass: 'tg-public page-why',
    nav: 'why-tex-giang',
  },
  contact: {
    source: 'E:/websitetuyendung/contact.html',
    view: 'app/Views/public/contact.php',
    bodyClass: 'tg-public page-contact',
    nav: 'contact',
  },
};

const INTERNAL = {
  'index.html': "<?= url('') ?>",
  'jobs.html': "<?= url('jobs') ?>",
  'about.html': "<?= url('about') ?>",
  'why-tex-giang.html': "<?= url('why-tex-giang') ?>",
  'news.html': "<?= url('news') ?>",
  'contact.html': "<?= url('contact') ?>",
  'apply.html': "<?= url('apply') ?>",
  'admin/login.html': "<?= url('admin/login') ?>",
};

const slug = process.argv[2];
const cfg = PAGES[slug];
if (!cfg) {
  console.error('Không có cấu hình cho trang:', slug);
  process.exit(1);
}

const ROOT = process.cwd();
const html = readFileSync(cfg.source, 'utf8');

const title = (html.match(/<title>([\s\S]*?)<\/title>/) || [])[1]?.trim() ?? '';
const description = (html.match(/<meta name="description" content="([\s\S]*?)"/) || [])[1]?.trim() ?? '';

const mainMatch = html.match(/<main id="main-content"[^>]*>([\s\S]*?)<\/main>/);
if (!mainMatch) {
  console.error('Không tìm thấy <main id="main-content"> trong', cfg.source);
  process.exit(1);
}
let content = mainMatch[1].trim();

// srcset: mỗi URL -> <?= asset(...) ?>
content = content.replace(/srcset="([^"]+)"/g, (_, value) => {
  const parts = value
    .split(',')
    .map((chunk) => chunk.trim())
    .filter(Boolean)
    .map((chunk) => {
      const [path, descriptor] = chunk.split(/\s+/);
      const expr = path.startsWith('assets/')
        ? `<?= asset('${path.slice('assets/'.length)}') ?>`
        : path;
      return descriptor ? `${expr} ${descriptor}` : expr;
    });
  return `srcset="${parts.join(', ')}"`;
});

// src / href tới assets
content = content.replace(/(src|href|poster)="assets\/([^"]+)"/g, (_, attr, path) => {
  return `${attr}="<?= asset('${path}') ?>"`;
});

// link nội bộ .html (kèm query nếu có) -> route PHP
content = content.replace(/href="([a-z0-9-]+\.html)(\?[^"]*)?"/g, (match, file) => {
  if (INTERNAL[file]) {
    return `href="${INTERNAL[file]}"`;
  }
  if (file === 'job-detail.html') {
    return 'href="<?= url(\'jobs\') ?>"';
  }
  if (file === 'news-detail.html') {
    return 'href="<?= url(\'news\') ?>"';
  }
  return match;
});

// Ảnh không có assets/ nhưng bắt đầu bằng images/ (hiếm)
content = content.replace(/(src|href)="images\/([^"]+)"/g, (_, attr, path) => {
  return `${attr}="<?= asset('images/${path}') ?>"`;
});

const header = [
  '<?php',
  `/**`,
  ` * Trang ${slug} — port tự động từ ${cfg.source.replace(ROOT, '')}.`,
  ` * Chạy: node tools/port-static-page.mjs ${slug}`,
  ` * Nội dung tĩnh; phần cần động (số liệu, timeline, form) chỉnh tay sau khi port.`,
  ` */`,
  '?>',
  '',
].join('\n');

const outPath = join(ROOT, cfg.view);
mkdirSync(dirname(outPath), { recursive: true });
writeFileSync(outPath, header + content + '\n', 'utf8');

console.log(JSON.stringify({ slug, view: cfg.view, title, description, bytes: content.length }, null, 2));
