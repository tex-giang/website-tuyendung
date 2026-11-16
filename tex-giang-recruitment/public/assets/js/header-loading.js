(function () {
  'use strict';

  const root = document.documentElement;
  let navigationTimer = null;

  function hideLoading() {
    window.clearTimeout(navigationTimer);
    navigationTimer = null;
    root.classList.remove('is-page-loading');
  }

  function showNavigationLoading() {
    root.classList.add('is-page-loading');
    window.clearTimeout(navigationTimer);
    // A canceled navigation can leave the current document in place.
    navigationTimer = window.setTimeout(hideLoading, 30000);
  }

  function sameTab(target) {
    return !target || target.toLowerCase() === '_self';
  }

  root.classList.add('is-page-loading');
  document.addEventListener('DOMContentLoaded', function () {
    if (navigationTimer === null) hideLoading();
  });
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) hideLoading();
  });

  // Listen after the page's click and submit handlers, which may open a dialog or use AJAX.
  window.addEventListener('click', function (event) {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

    const link = event.target.closest?.('a[href]');
    if (!link || !sameTab(link.target) || link.hasAttribute('download')) return;

    const href = link.getAttribute('href');
    if (!href || link.origin !== window.location.origin) return;
    if (href.includes('#') && link.pathname === window.location.pathname && link.search === window.location.search) return;

    showNavigationLoading();
  });

  window.addEventListener('submit', function (event) {
    if (event.defaultPrevented) return;

    const form = event.target;
    const submitter = event.submitter;
    const target = submitter?.getAttribute('formtarget') ?? form.target;
    const method = submitter?.getAttribute('formmethod') ?? form.method;
    if (!sameTab(target) || method.toLowerCase() === 'dialog') return;

    showNavigationLoading();
  });
})();
