(function () {
  'use strict';

  /**
   * Lightbox cho trang chi tiết tin tức.
   *
   * - Gom toàn bộ ảnh trong .news-reading (ảnh đại diện + ảnh trong nội dung bài).
   * - Bấm ảnh -> mở overlay, xem được ảnh trước/sau bằng nút, phím mũi tên, vuốt.
   * - Không phụ thuộc thư viện ngoài.
   */
  var article = document.querySelector('.news-reading');
  if (!article) return;

  var images = Array.prototype.slice.call(article.querySelectorAll('img')).filter(function (img) {
    var src = img.getAttribute('src') || '';
    return src !== '' && !/^data:/i.test(src) && img.getAttribute('data-lightbox') !== 'off';
  });
  if (images.length === 0) return;

  var current = -1;
  var lastFocus = null;
  var touchStartX = 0;
  var touchStartY = 0;
  var lastSwipeAt = 0;

  // --- Dựng overlay 1 lần, tái sử dụng ---
  var overlay = document.createElement('div');
  overlay.className = 'tg-lightbox';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-modal', 'true');
  overlay.setAttribute('aria-label', 'Xem ảnh');
  overlay.hidden = true;
  overlay.innerHTML =
    '<div class="tg-lightbox__bar">' +
      '<span class="tg-lightbox__count" aria-live="polite"></span>' +
      '<button type="button" class="tg-lightbox__close" aria-label="Đóng xem ảnh"><span aria-hidden="true">&times;</span></button>' +
    '</div>' +
    '<button type="button" class="tg-lightbox__nav tg-lightbox__nav--prev" aria-label="Ảnh trước"><span aria-hidden="true">&#8249;</span></button>' +
    '<button type="button" class="tg-lightbox__nav tg-lightbox__nav--next" aria-label="Ảnh sau"><span aria-hidden="true">&#8250;</span></button>' +
    '<figure class="tg-lightbox__stage">' +
      '<img class="tg-lightbox__image" alt="">' +
      '<figcaption class="tg-lightbox__caption"></figcaption>' +
    '</figure>';

  document.body.appendChild(overlay);

  var imageEl = overlay.querySelector('.tg-lightbox__image');
  var captionEl = overlay.querySelector('.tg-lightbox__caption');
  var countEl = overlay.querySelector('.tg-lightbox__count');
  var prevBtn = overlay.querySelector('.tg-lightbox__nav--prev');
  var nextBtn = overlay.querySelector('.tg-lightbox__nav--next');
  var closeBtn = overlay.querySelector('.tg-lightbox__close');

  function captionFor(img) {
    var figure = img.closest('figure');
    var caption = figure ? figure.querySelector('figcaption') : null;
    if (caption && caption.textContent.trim() !== '') return caption.textContent.trim();
    return (img.getAttribute('alt') || '').trim();
  }

  function fullSrc(img) {
    // Ảnh trong nội dung có thể kèm ?v=... — giữ nguyên để đúng cache-busting.
    return img.currentSrc || img.getAttribute('src') || '';
  }

  function preload(index) {
    var img = images[index];
    if (!img) return;
    var loader = new Image();
    loader.src = fullSrc(img);
  }

  function render(index) {
    if (index < 0 || index >= images.length) return;
    current = index;
    var img = images[index];
    imageEl.src = fullSrc(img);
    imageEl.alt = (img.getAttribute('alt') || '').trim();

    var caption = captionFor(img);
    captionEl.textContent = caption;
    captionEl.hidden = caption === '';

    countEl.textContent = images.length > 1 ? (index + 1) + ' / ' + images.length : '';
    prevBtn.hidden = images.length < 2;
    nextBtn.hidden = images.length < 2;

    preload(index - 1);
    preload(index + 1);
  }

  function open(index) {
    lastFocus = document.activeElement;
    overlay.hidden = false;
    document.documentElement.classList.add('tg-lightbox-open');
    render(index);
    closeBtn.focus({ preventScroll: true });
  }

  function close() {
    overlay.hidden = true;
    document.documentElement.classList.remove('tg-lightbox-open');
    imageEl.removeAttribute('src');
    imageEl.alt = '';
    captionEl.textContent = '';
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus({ preventScroll: true });
    }
    lastFocus = null;
    current = -1;
  }

  function go(step) {
    if (images.length < 2) return;
    var next = (current + step + images.length) % images.length;
    render(next);
  }

  // --- Gắn sự kiện cho từng ảnh ---
  images.forEach(function (img, index) {
    img.setAttribute('data-lightbox-ready', '');
    img.setAttribute('tabindex', '0');
    img.setAttribute('role', 'button');
    if (!img.hasAttribute('aria-label')) {
      var alt = (img.getAttribute('alt') || '').trim();
      img.setAttribute('aria-label', alt !== '' ? 'Xem ảnh: ' + alt : 'Xem ảnh lớn');
    }
    img.addEventListener('click', function (event) {
      event.preventDefault();
      open(index);
    });
    img.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        open(index);
      }
    });
  });

  // --- Sự kiện overlay ---
  closeBtn.addEventListener('click', close);
  prevBtn.addEventListener('click', function () { go(-1); });
  nextBtn.addEventListener('click', function () { go(1); });

  overlay.addEventListener('click', function (event) {
    if (event.target !== overlay) return;
    // Vuốt xong trình duyệt có thể phát sinh click ở vị trí chạm cuối -> đừng đóng.
    if (Date.now() - lastSwipeAt < 600) return;
    close();
  });

  document.addEventListener('keydown', function (event) {
    if (overlay.hidden) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
    } else if (event.key === 'ArrowLeft') {
      event.preventDefault();
      go(-1);
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      go(1);
    } else if (event.key === 'Tab') {
      // Giữ focus trong hộp thoại.
      var focusables = [closeBtn, prevBtn, nextBtn].filter(function (el) { return !el.hidden; });
      if (focusables.length === 0) return;
      var first = focusables[0];
      var last = focusables[focusables.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });

  // Kéo/vuốt trên điện thoại
  overlay.addEventListener('touchstart', function (event) {
    if (event.touches.length !== 1) return;
    touchStartX = event.touches[0].clientX;
    touchStartY = event.touches[0].clientY;
  }, { passive: true });
  // Chặn cử chỉ "vuốt để quay lại/tiến" của trình duyệt khi đang xem ảnh.
  overlay.addEventListener('touchmove', function (event) {
    if (event.touches.length !== 1) return;
    var dx = event.touches[0].clientX - touchStartX;
    var dy = event.touches[0].clientY - touchStartY;
    if (Math.abs(dx) > 10 && Math.abs(dx) > Math.abs(dy)) {
      event.preventDefault();
    }
  }, { passive: false });
  overlay.addEventListener('touchend', function (event) {
    if (event.changedTouches.length !== 1) return;
    var dx = event.changedTouches[0].clientX - touchStartX;
    var dy = event.changedTouches[0].clientY - touchStartY;
    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) {
      lastSwipeAt = Date.now();
      go(dx < 0 ? 1 : -1);
    }
  }, { passive: true });
})();
