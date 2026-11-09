(function () {
  'use strict';

  const main = document.getElementById('admin-content');

  function insertRow(template, container, indexes) {
    const row = template.content.firstElementChild.cloneNode(true);
    row.querySelectorAll('[name]').forEach(input => {
      input.name = input.name.replace(/__INDEX__|__G__|__I__/g, marker => indexes[marker]);
    });
    container.append(row);
    row.querySelector('input[type="text"]')?.focus();
  }

  function moveRow(row, direction) {
    if (direction === 'up' && row.previousElementSibling) row.previousElementSibling.before(row);
    if (direction === 'down' && row.nextElementSibling) row.nextElementSibling.after(row);
  }

  function updateSimpleRows(repeater, rows) {
    Array.from(rows.children).forEach((row, index) => {
      row.querySelectorAll('[name]').forEach(input => {
        input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
      });
    });
    const count = repeater.closest('.admin-card')?.querySelector('[data-count]');
    if (count) count.textContent = rows.children.length;
  }

  function updateGroups(form, list) {
    const checked = list.querySelectorAll('input[type="radio"]:checked');
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
    checked.forEach(input => { input.checked = true; });
    form.querySelector('[data-group-count]').textContent = list.children.length;
  }

  // Uỷ quyền từ khung chính để các nút mới vẫn hoạt động sau khi cập nhật HTML.
  main.addEventListener('click', event => {
    const button = event.target.closest('button[type="button"]');
    if (!button) return;

    const repeater = button.closest('[data-repeater]');
    if (repeater && button.matches('[data-add], [data-remove], [data-move]')) {
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
  });
})();
