(function () {
  'use strict';

  const main = document.getElementById('admin-content');

  function insertRow(template, container, indexes) {
    const row = template.content.firstElementChild.cloneNode(true);
    row.querySelectorAll('[name]').forEach(input => {
      input.name = input.name.replace(/__INDEX__|__G__|__I__/g, marker => indexes[marker]);
    });
    container.append(row);
    row.querySelector('input:not([type="hidden"]), textarea')?.focus();
  }

  function moveRow(row, direction) {
    if (direction === 'up' && row.previousElementSibling) row.previousElementSibling.before(row);
    if (direction === 'down' && row.nextElementSibling) row.nextElementSibling.after(row);
  }

  function updateSimpleRows(repeater, rows) {
    Array.from(rows.children).forEach((row, index) => {
      row.querySelectorAll('[name]').forEach(input => {
        input.name = input.name.replace(/\[(\d+_)?\d+\]/, (_, prefix = '') => '[' + prefix + index + ']');
      });
    });
    const count = repeater.closest('.admin-card')?.querySelector('[data-count]');
    if (count) count.textContent = rows.children.length;
  }

  function updateGroups(form, list) {
    Array.from(list.children).forEach((group, groupIndex) => {
      group.querySelector('[data-group-number]').textContent = groupIndex + 1;
      group.querySelectorAll('[name]').forEach(input => {
        input.name = input.name.replace(/groups\[\d+\]/, 'groups[' + groupIndex + ']');
      });
      group.querySelectorAll('[data-item-row]').forEach((row, itemIndex) => {
        row.querySelectorAll('[name]').forEach(input => {
          input.name = input.name.replace(/items\[\d+\]/, 'items[' + itemIndex + ']');
        });
      });
    });
    const count = form.querySelector('[data-group-count]');
    if (count) count.textContent = list.children.length;
  }

  main.addEventListener('input', event => {
    const field = event.target;
    if (field.matches('[data-benefit-title]')) {
      const title = field.value.trim();
      field.closest('[data-group]').querySelector('[data-group-title]').textContent = title ? ' · ' + title : '';
    }
    if (field.matches('[data-benefit-icon]')) {
      field.closest('[data-group]').querySelector('[data-benefit-icon-preview]').src = field.selectedOptions[0].dataset.preview;
    }
    if (field.matches('[name="address"], [name="map_link"]')) {
      const link = field.form?.querySelector('[data-branch-map]');
      if (!link) return;
      const address = field.form.elements.address.value.trim();
      const custom = field.form.elements.map_link.value.trim();
      const href = /^https?:\/\/[^\s]+$/i.test(custom) ? custom
        : address ? 'https://www.google.com/maps/search/?' + new URLSearchParams({ api: '1', query: address }) : '';
      if (href) {
        link.href = href;
        link.removeAttribute('aria-disabled');
      } else {
        link.removeAttribute('href');
        link.setAttribute('aria-disabled', 'true');
      }
    }
  });

  // Uỷ quyền từ khung chính để các nút mới vẫn hoạt động sau khi cập nhật HTML.
  main.addEventListener('click', event => {
    const button = event.target.closest('button[type="button"]');
    if (!button) return;

    const repeater = button.closest('[data-repeater]');
    if (repeater && button.matches('[data-add], [data-remove], [data-move]')) {
      const form = button.form;
      const rows = repeater.querySelector('[data-rows]');
      const row = button.closest('[data-row]');
      if (button.hasAttribute('data-add')) {
        insertRow(repeater.querySelector('[data-template]'), rows, { __INDEX__: rows.children.length });
      } else if (button.hasAttribute('data-remove')) {
        row.remove();
      } else {
        moveRow(row, button.dataset.move);
        button.focus();
      }
      updateSimpleRows(repeater, rows);
      form?.dispatchEvent(new Event('input', { bubbles: true }));
      return;
    }

    if (!button.matches('[data-group-add], [data-group-remove], [data-item-add], [data-item-remove], [data-move]')) return;
    const form = button.form;
    const list = form?.querySelector('[data-group-list]');
    if (!list) return;
    const group = button.closest('[data-group]');
    const item = button.closest('[data-item-row]');
    if (button.hasAttribute('data-group-add')) {
      insertRow(form.querySelector('#group-template'), list, { __G__: list.children.length });
    } else if (button.hasAttribute('data-item-add')) {
      const rows = group.querySelector('[data-item-rows]');
      insertRow(form.querySelector('#item-template'), rows, {
        __G__: Array.from(list.children).indexOf(group),
        __I__: rows.children.length,
      });
    } else if (button.matches('[data-group-remove], [data-item-remove]')) {
      (item || group).remove();
    } else {
      moveRow(item || group, button.dataset.move);
      button.focus();
    }
    updateGroups(form, list);
    form.dispatchEvent(new Event('input', { bubbles: true }));
  });
})();
