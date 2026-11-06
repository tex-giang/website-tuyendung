/**
 * TEX-GIANG — SLIDER ẢNH TRANG CHỦ
 * - Tự chuyển mỗi 5 giây, lặp vô hạn (có bản sao slide đầu để chuyển mượt).
 * - Nút trước/sau, chấm chọn ảnh, kéo vuốt trên mobile, phím ← →.
 * - Tạm dừng khi rê chuột, khi focus vào vùng slider hoặc tab bị ẩn.
 * - Tôn trọng prefers-reduced-motion: không tự chạy, không hiệu ứng trượt.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-hero-slider]');
  if (!root) return;

  var track = root.querySelector('[data-slider-track]');
  var dotsWrap = root.querySelector('[data-slider-dots]');
  var prevBtn = root.querySelector('[data-slider-prev]');
  var nextBtn = root.querySelector('[data-slider-next]');
  var live = document.querySelector('[data-slider-live]');
  if (!track || !dotsWrap) return;

  var slides = Array.prototype.slice.call(track.querySelectorAll('[data-slide]'));
  var total = slides.length;
  if (total < 2) return;

  var INTERVAL = 5000;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var pos = 0;
  var timer = null;
  var snapTimer = null;

  // Bản sao slide đầu đặt cuối track để chuyển từ slide cuối -> đầu mượt.
  var firstClone = slides[0].cloneNode(true);
  firstClone.setAttribute('aria-hidden', 'true');
  firstClone.removeAttribute('data-slide');
  track.appendChild(firstClone);

  var dots = slides.map(function (slide, index) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'hero-slider__dot';
    var img = slide.querySelector('img');
    button.setAttribute('aria-label', 'Ảnh ' + (index + 1) + ' / ' + total + (img && img.alt ? ': ' + img.alt : ''));
    button.addEventListener('click', function () {
      stopAuto();
      if (pos >= total) snap(0);
      if (index !== pos) {
        pos = index;
        render(true);
      }
      startAuto();
    });
    dotsWrap.appendChild(button);
    return button;
  });

  function currentIndex() {
    return pos >= total ? 0 : pos;
  }

  function render(withTransition) {
    if (!withTransition || reduced) {
      track.style.transition = 'none';
    } else {
      track.style.transition = '';
    }
    track.style.transform = 'translateX(-' + (pos * 100) + '%)';
    var active = currentIndex();
    dots.forEach(function (dot, index) {
      if (index === active) dot.setAttribute('aria-current', 'true');
      else dot.removeAttribute('aria-current');
    });
    // Slide ngoài khung bị ẩn khỏi trình đọc màn hình.
    slides.forEach(function (slide, index) {
      if (index === active) slide.removeAttribute('aria-hidden');
      else slide.setAttribute('aria-hidden', 'true');
    });
    if (live) {
      var img = slides[active].querySelector('img');
      live.textContent = 'Ảnh ' + (active + 1) + ' trên ' + total + (img && img.alt ? ': ' + img.alt : '');
    }
  }

  function snap(index) {
    pos = index;
    render(false);
    // Buộc trình duyệt áp transform tức thời trước khi bật lại transition.
    void track.offsetWidth;
    track.style.transition = '';
  }

  function next() {
    if (snapTimer) { clearTimeout(snapTimer); snapTimer = null; }
    if (pos >= total) snap(0);
    pos += 1;
    render(true);
    if (pos >= total) {
      // Đang hiển thị bản sao slide đầu: trượt xong thì nhảy về slide thật.
      snapTimer = setTimeout(function () {
        snap(0);
        snapTimer = null;
      }, reduced ? 0 : 700);
    }
  }

  function prev() {
    if (snapTimer) { clearTimeout(snapTimer); snapTimer = null; }
    if (pos === 0) {
      // Nhảy tức thời tới bản sao (giống hệt slide đầu) rồi trượt sang trái.
      snap(total);
      pos = total - 1;
      render(true);
    } else {
      pos -= 1;
      render(true);
    }
  }

  function startAuto() {
    if (reduced || timer) return;
    timer = setInterval(next, INTERVAL);
  }

  function stopAuto() {
    if (timer) { clearInterval(timer); timer = null; }
  }

  function restartAuto() {
    stopAuto();
    startAuto();
  }

  prevBtn && prevBtn.addEventListener('click', function () { prev(); restartAuto(); });
  nextBtn && nextBtn.addEventListener('click', function () { next(); restartAuto(); });

  root.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowRight') { event.preventDefault(); next(); restartAuto(); }
    else if (event.key === 'ArrowLeft') { event.preventDefault(); prev(); restartAuto(); }
  });

  root.addEventListener('mouseenter', stopAuto);
  root.addEventListener('mouseleave', startAuto);
  root.addEventListener('focusin', stopAuto);
  root.addEventListener('focusout', function (event) {
    if (!root.contains(event.relatedTarget)) startAuto();
  });
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) stopAuto();
    else startAuto();
  });

  // Kéo/vuốt trên mobile.
  var startX = null;
  root.addEventListener('pointerdown', function (event) {
    if (event.pointerType === 'mouse') return;
    startX = event.clientX;
  });
  root.addEventListener('pointerup', function (event) {
    if (startX === null) return;
    var delta = event.clientX - startX;
    startX = null;
    if (Math.abs(delta) < 40) return;
    if (delta < 0) next(); else prev();
    restartAuto();
  });
  root.addEventListener('pointercancel', function () { startX = null; });

  render(false);
  startAuto();
})();
