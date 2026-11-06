document.addEventListener("DOMContentLoaded", () => {
  const id =
    new URLSearchParams(location.search).get("id") ||
    "ngay-hoi-viec-lam-chau-thanh";
  const post = TGNews.find((p) => p.id === id);
  if (!post) {
    location.replace("404.html");
    return;
  }
  const esc = (s) =>
    String(s).replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  document.title = post.title + " | Tuyển dụng TEX-GIANG";
  document.querySelector("meta[name=description]").content = post.excerpt;
  document.querySelector("main").innerHTML =
    `<article class="tg-container news-reading"><nav aria-label="Breadcrumb"><a href="index.html">Trang chủ</a> / <a href="news.html">Tin tức</a> / ${esc(post.title)}</nav><h1>${esc(post.title)}</h1><p class="news-reading-meta">${esc(post.category)} · Cẩm nang tham khảo · Cập nhật 03/11/2026</p><p class="news-reading-lead">${esc(post.excerpt)}</p><figure><img src="${post.img}" alt="${esc(post.alt)}" width="1280" height="850"><figcaption>${esc(post.caption)} <a href="https://tex-giang.com.vn/" target="_blank" rel="noopener">TEX-GIANG</a>.</figcaption></figure><h2>Thông tin cần biết</h2>${post.paragraphs.map((p) => `<p>${esc(p)}</p>`).join("")}<div class="article-actions"><a class="tg-btn tg-btn--primary" href="jobs.html">Xem việc đang tuyển</a><a class="tg-btn tg-btn--ghost" href="apply.html">Đăng ký hồ sơ demo</a></div><div class="share-row"><button type="button" onclick="copyLink()" class="tg-link">Sao chép liên kết</button><a class="tg-link" href="news.html">Về danh sách tin</a></div></article><section class="tg-container news-related"><h2>Đọc thêm</h2><ul>${TGNews.filter(
      (p) => p.id !== id,
    )
      .sort(
        (a, b) =>
          Number(b.category === post.category) -
          Number(a.category === post.category),
      )
      .slice(0, 3)
      .map(
        (p) =>
          `<li><a href="news-detail.html?id=${p.id}"><span>${esc(p.category)}</span><strong>${esc(p.title)}</strong></a></li>`,
      )
      .join("")}</ul></section>`;
});
