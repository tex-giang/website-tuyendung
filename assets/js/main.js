/**
 * TEX-GIANG RECRUITMENT PORTAL - MAIN JAVASCRIPT
 * Handles global UI interactions, modals, toasts, mobile menus, and sharing.
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Navigation Toggle
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', function () {
      mobileMenu.classList.toggle('hidden');
      const isExpanded = !mobileMenu.classList.contains('hidden');
      mobileMenuBtn.setAttribute('aria-expanded', isExpanded);
    });
  }

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
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // 3. Recruitment Announcement Popup (Shows after 1.5s once per session)
  const popupBanner = document.getElementById('recruitment-popup');
  const closePopupBtn = document.getElementById('close-popup-btn');
  const dontShowAgainBtn = document.getElementById('popup-dont-show-again');

  if (popupBanner) {
    const isDismissed = sessionStorage.getItem('texgiang_popup_seen');
    if (!isDismissed) {
      setTimeout(() => {
        popupBanner.classList.remove('hidden');
        popupBanner.classList.add('flex');
      }, 1500);
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

  qrTriggers.forEach((trigger) => {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      const currentUrl = window.location.href;
      if (qrTargetUrl) qrTargetUrl.textContent = currentUrl;
      if (qrImageElem) {
        qrImageElem.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(currentUrl)}`;
      }
      if (qrModal) {
        qrModal.classList.remove('hidden');
        qrModal.classList.add('flex');
      }
    });
  });

  if (closeQrBtn && qrModal) {
    const hideQr = () => {
      qrModal.classList.add('hidden');
      qrModal.classList.remove('flex');
    };
    closeQrBtn.addEventListener('click', hideQr);
    qrModal.addEventListener('click', (e) => {
      if (e.target === qrModal) hideQr();
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
  const isSuccess = type === 'success';
  const isError = type === 'error';

  let bgClass = 'bg-[#12345A] text-white';
  let iconClass = 'bi-info-circle-fill text-amber-400';

  if (isSuccess) {
    bgClass = 'bg-[#087A55] text-white';
    iconClass = 'bi-check-circle-fill text-emerald-300';
  } else if (isError) {
    bgClass = 'bg-[#DC2626] text-white';
    iconClass = 'bi-exclamation-triangle-fill text-yellow-300';
  }

  toast.className = `${bgClass} p-4 rounded-2xl shadow-xl flex items-start gap-3 pointer-events-auto transform translate-y-3 opacity-0 transition-all duration-300 text-xs sm:text-sm font-medium border border-white/10`;
  toast.innerHTML = `
    <i class="bi ${iconClass} text-lg leading-none mt-0.5 flex-shrink-0"></i>
    <div class="flex-1 leading-snug">${message}</div>
    <button onclick="this.parentElement.remove()" class="text-white/70 hover:text-white leading-none ml-2">
      <i class="bi bi-x-lg text-xs"></i>
    </button>
  `;

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
