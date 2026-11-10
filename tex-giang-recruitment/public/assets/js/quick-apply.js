/**
 * TEX-GIANG — Trang nộp hồ sơ nhanh (/tuyen-dung, layout độc lập)
 * Chọn nhà máy trước → danh sách vị trí lọc theo đúng tin đang tuyển của khu đó.
 * Option vị trí do server render kèm data-branch; JS chỉ ẩn/hiện theo khu.
 */
(function () {
  'use strict';

  var form = document.getElementById('gf-form');
  if (!form) return;

  var branchRadios = form.querySelectorAll('input[name="branch_slug"]');
  var select = document.getElementById('gf-position');
  var hint = document.getElementById('gf-position-hint');

  if (!select) return;

  // Lưu toàn bộ option server render để dựng lại khi đổi nhà máy.
  var allOptions = Array.prototype.slice.call(select.options).map(function (option) {
    return {
      value: option.value,
      label: option.textContent.trim(),
      branch: option.getAttribute('data-branch') || '',
      isPlaceholder: option.value === '',
      isOther: option.value === 'khac'
    };
  });

  function checkedBranch() {
    for (var i = 0; i < branchRadios.length; i++) {
      if (branchRadios[i].checked) return branchRadios[i].value;
    }
    return '';
  }

  function rebuild(selectedValue) {
    var branch = checkedBranch();
    var matched = 0;

    select.innerHTML = '';

    allOptions.forEach(function (item) {
      if (item.isPlaceholder) {
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = branch ? '— Chọn vị trí —' : '— Chọn nhà máy trước —';
        select.appendChild(placeholder);
        return;
      }
      if (!item.isOther && (!branch || item.branch !== branch)) return;

      var option = document.createElement('option');
      option.value = item.value;
      option.textContent = item.label;
      if (item.branch) option.setAttribute('data-branch', item.branch);
      select.appendChild(option);
      if (!item.isOther) matched++;
    });

    select.disabled = !branch;
    if (branch && selectedValue) {
      select.value = selectedValue;
      if (select.value !== selectedValue) select.value = '';
    }

    if (hint) {
      if (!branch) {
        hint.textContent = 'Chọn nhà máy trước để xem vị trí đang tuyển tại khu đó.';
      } else if (matched === 0) {
        hint.textContent = 'Nhà máy này chưa có vị trí đang tuyển — chọn “Vị trí khác” hoặc nhà máy khác.';
      } else {
        hint.textContent = matched + ' vị trí đang tuyển tại nhà máy này.';
      }
    }
  }

  branchRadios.forEach(function (radio) {
    radio.addEventListener('change', function () { rebuild(''); });
  });

  // ---- Bắt buộc đồng ý điều khoản mới cho phép gửi hồ sơ ----
  var termsBox = document.getElementById('gf-terms');
  var submitBtn = form.querySelector('button[type="submit"]');
  if (termsBox && submitBtn) {
    var syncTerms = function () {
      submitBtn.disabled = !termsBox.checked;
      if (!termsBox.checked) {
        submitBtn.setAttribute('title', 'Vui lòng đồng ý để nhân sự liên hệ về hồ sơ');
        submitBtn.setAttribute('aria-disabled', 'true');
      } else {
        submitBtn.removeAttribute('title');
        submitBtn.removeAttribute('aria-disabled');
      }
    };
    termsBox.addEventListener('change', syncTerms);
    syncTerms();
  }
  // Giữ lựa chọn cũ khi server trả lại form kèm lỗi.
  rebuild(select.value);
})();
