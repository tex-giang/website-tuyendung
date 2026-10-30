/**
 * TEX-GIANG RECRUITMENT PORTAL - MAIN JAVASCRIPT
 * Handles global UI interactions, modals, toasts, mobile menus, and sharing.
 */

const TG_ASSET_BASE = new URL('../images/', document.currentScript.src).href;

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Navigation Toggle
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', function () {
      mobileMenu.classList.toggle('hidden');
      const isExpanded = !mobileMenu.classList.contains('hidden');
      mobileMenuBtn.setAttribute('aria-expanded', isExpanded);
      mobileMenuBtn.setAttribute('aria-label', isExpanded ? 'Đóng menu' : 'Mở menu');
    });
  }
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && mobileMenu && !mobileMenu.classList.contains('hidden')) {
      mobileMenu.classList.add('hidden');
      mobileMenuBtn?.setAttribute('aria-expanded', 'false');
      mobileMenuBtn?.setAttribute('aria-label', 'Mở menu');
      mobileMenuBtn?.focus();
    }
  });

  // 2. Back To Top Button
  const backToTopBtn = document.getElementById('back-to-top');
  if (backToTopBtn) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 350) {
        backToTopBtn.classList.remove('opacity-0', 'pointer-events-none');
        backToTopBtn.classList.add('opacity-100');
      } else {
        backToTopBtn.classList.add('opacity-0', 'pointer-events-none');
        backToTopBtn.classList.remove('opacity-100');
      }
    });

    backToTopBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
    });
  }

  // 3. Banner pop-up tuyển dụng: chỉ hiện sau khi người dùng đã cuộn (không che màn đầu),
  //    tối đa một lần mỗi phiên và có nút tắt.
  const popupBanner = document.getElementById('recruitment-popup');
  const closePopupBtn = document.getElementById('close-popup-btn');
  const dontShowAgainBtn = document.getElementById('popup-dont-show-again');

  if (popupBanner) {
    const isDismissed = sessionStorage.getItem('texgiang_popup_seen');
    let popupShown = false;

    const showPopup = () => {
      if (popupShown || sessionStorage.getItem('texgiang_popup_seen')) return;
      popupShown = true;
      popupBanner.classList.remove('hidden');
      popupBanner.classList.add('flex');
      window.removeEventListener('scroll', onScroll);
    };

    const onScroll = () => {
      if (window.scrollY > window.innerHeight * 0.6) showPopup();
    };

    if (!isDismissed) {
      window.addEventListener('scroll', onScroll, { passive: true });
      // Dự phòng cho người chỉ đọc màn đầu rồi rời tab
      setTimeout(showPopup, 25000);
    }

    const hidePopup = () => {
      popupBanner.classList.add('hidden');
      popupBanner.classList.remove('flex');
    };

    if (closePopupBtn) closePopupBtn.addEventListener('click', hidePopup);
    if (dontShowAgainBtn) {
      dontShowAgainBtn.addEventListener('click', () => {
        sessionStorage.setItem('texgiang_popup_seen', 'true');
        hidePopup();
      });
    }

    popupBanner.addEventListener('click', (e) => {
      if (e.target === popupBanner) hidePopup();
    });
  }

  // 4. QR Code Modal
  const qrModal = document.getElementById('qr-modal');
  const closeQrBtn = document.getElementById('close-qr-btn');
  const qrTriggers = document.querySelectorAll('.open-qr-modal');
  const qrImageElem = document.getElementById('qr-code-img');
  const qrTargetUrl = document.getElementById('qr-target-url');
  let qrOpener = null;

  qrTriggers.forEach((trigger) => {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      const currentUrl = window.location.href;
      if (qrTargetUrl) qrTargetUrl.textContent = currentUrl;
      if (qrImageElem) {
        qrImageElem.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(currentUrl)}`;
      }
      if (qrModal) {
        qrOpener = trigger;
        qrModal.classList.remove('hidden');
        qrModal.classList.add('flex');
        document.body.classList.add('has-dialog');
        closeQrBtn?.focus();
      }
    });
  });

  if (closeQrBtn && qrModal) {
    const hideQr = () => {
      qrModal.classList.add('hidden');
      qrModal.classList.remove('flex');
      document.body.classList.remove('has-dialog');
      qrOpener?.focus();
    };
    closeQrBtn.addEventListener('click', hideQr);
    qrModal.addEventListener('click', (e) => {
      if (e.target === qrModal) hideQr();
    });
    document.addEventListener('keydown', event => {
      if (qrModal.classList.contains('hidden')) return;
      if (event.key === 'Escape') hideQr();
      if (event.key === 'Tab') {
        const focusable = [...qrModal.querySelectorAll('a[href],button,input,[tabindex="0"]')].filter(el => el.getClientRects().length);
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
      }
    });
  }
});

/**
 * Global Toast Notification Helper
 */
function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
  const isSuccess = type === 'success';
  const isError = type === 'error';

  let bgClass = 'bg-[#12345A] text-white';

  if (isSuccess) {
    bgClass = 'bg-[#087A55] text-white';
  } else if (isError) {
    bgClass = 'bg-[#DC2626] text-white';
  }

  toast.className = `${bgClass} p-4 rounded-2xl shadow-xl flex items-start gap-3 pointer-events-auto transform translate-y-3 opacity-0 transition-all duration-300 text-xs sm:text-sm font-medium border border-white/10`;
  const icon = document.createElement('img');
  icon.src = TG_ASSET_BASE + 'icons/' + (isError ? 'exclamation-triangle-fill' : isSuccess ? 'check-circle-fill' : 'info-circle-fill') + '.svg';
  icon.alt = '';
  icon.width = icon.height = 20;
  icon.style.filter = 'brightness(0) invert(1)';
  const text = document.createElement('div');
  text.className = 'flex-1 leading-snug';
  text.textContent = message;
  const close = document.createElement('button');
  close.type = 'button';
  close.setAttribute('aria-label', 'Đóng thông báo');
  close.textContent = '×';
  close.style.cssText = 'min-width:32px;min-height:32px;font-size:24px;';
  close.addEventListener('click', () => toast.remove());
  toast.append(icon, text, close);

  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.remove('translate-y-3', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
  });

  setTimeout(() => {
    toast.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

/**
 * Social Sharing Helper
 */
function shareJob(title, url) {
  const targetUrl = url || window.location.href;
  const targetTitle = title || document.title;

  if (navigator.share) {
    navigator.share({
      title: targetTitle,
      text: `Cơ hội việc làm tại CÔNG TY CỔ PHẦN TEX-GIANG: ${targetTitle}`,
      url: targetUrl
    }).catch(() => {});
  } else {
    copyLink(targetUrl);
  }
}

function copyLink(url) {
  const link = url || window.location.href;
  if (!navigator.clipboard) { showToast('Trình duyệt chưa hỗ trợ sao chép. Bạn có thể sao chép đường dẫn trên thanh địa chỉ.', 'error'); return; }
  navigator.clipboard.writeText(link).then(() => {
    showToast('Đã sao chép liên kết vào bộ nhớ tạm!', 'success');
  }).catch(() => {
    showToast('Không thể sao chép liên kết. Vui lòng thử lại.', 'error');
  });
}

function shareFacebook(url) {
  const targetUrl = encodeURIComponent(url || window.location.href);
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${targetUrl}`, '_blank', 'width=600,height=420');
}

function shareZalo(url) {
  const targetUrl = encodeURIComponent(url || window.location.href);
  window.open(`https://zalo.me/share?url=${targetUrl}`, '_blank', 'width=600,height=420');
}
