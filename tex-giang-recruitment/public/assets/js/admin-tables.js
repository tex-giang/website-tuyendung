/**
 * TEX-GIANG — Bảng quản trị dùng chung: Bootstrap 5 + DataTables
 *
 * Mọi bảng danh sách trong admin chỉ cần thêm class `js-datatable`
 * (kèm `table` của Bootstrap). Script tự:
 *   - Dịch giao diện DataTables sang tiếng Việt (tìm kiếm, số dòng, phân trang, sắp xếp)
 *   - Chuyển ngày dạng dd/mm/yyyy [hh:mm] sang data-order ISO để sắp xếp đúng
 *   - Tắt sắp xếp/tìm kiếm ở cột "Thao tác"
 *   - Bọc bảng trong khung cuộn ngang của admin
 */
(function () {
  'use strict';

  var $ = window.jQuery;
  var scrollObservers = new WeakMap();

  var VI = {
    search: 'Tìm nhanh:',
    searchPlaceholder: 'Nhập từ khoá…',
    lengthMenu: 'Hiện _MENU_ dòng',
    info: 'Hiển thị _START_–_END_ / _TOTAL_ dòng',
    infoEmpty: 'Chưa có dòng nào',
    infoFiltered: '(lọc từ _MAX_ dòng)',
    zeroRecords: 'Không tìm thấy dữ liệu phù hợp',
    emptyTable: 'Chưa có dữ liệu',
    loadingRecords: 'Đang tải…',
    processing: 'Đang xử lý…',
    paginate: {
      first: '«',
      previous: '‹',
      next: '›',
      last: '»'
    },
    aria: {
      sortAscending: ': sắp xếp tăng dần',
      sortDescending: ': sắp xếp giảm dần'
    }
  };

  /**
   * Ô ngày dạng "06/11/2026" hoặc "06/11/2026 13:34" chuyển thành data-order
   * ISO để DataTables sắp xếp đúng thay vì so sánh chuỗi.
   */
  function normalizeDates(table) {
    var cells = table.querySelectorAll('tbody td');
    Array.prototype.forEach.call(cells, function (cell) {
      if (cell.hasAttribute('data-order')) return;
      var text = (cell.textContent || '').trim();
      var match = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:[ ,]+(\d{1,2}):(\d{2}))?$/);
      if (!match) return;
      var iso = match[3] + '-' +
        String(match[2]).padStart(2, '0') + '-' +
        String(match[1]).padStart(2, '0');
      if (match[4]) {
        iso += ' ' + String(match[4]).padStart(2, '0') + ':' + match[5];
      }
      cell.setAttribute('data-order', iso);
    });
  }

  function initTable(table) {
    normalizeDates(table);

    var actionsIndex = -1;
    Array.prototype.forEach.call(table.querySelectorAll('thead th'), function (th, index) {
      if (th.classList.contains('is-actions')) actionsIndex = index;
    });

    var columnDefs = [];
    if (actionsIndex >= 0) {
      columnDefs.push({ targets: actionsIndex, orderable: false, searchable: false });
    }

    // Chỉ bảng ít dòng được đánh dấu data-dt-compact mới chạy chế độ gọn.
    var compact = table.hasAttribute('data-dt-compact');

    var options = {
      language: VI,
      order: [],
      columnDefs: columnDefs
    };

    if (compact) {
      // Bảng ít dòng (vd. danh mục): chỉ cần sắp xếp, ẩn thanh tìm kiếm/phân trang.
      options.paging = false;
      options.info = false;
      options.searching = false;
      options.lengthChange = false;
      options.layout = { topStart: null, topEnd: null, bottomStart: null, bottomEnd: null };
    } else {
      options.pageLength = 10;
      options.lengthMenu = [[10, 25, 50, -1], [10, 25, 50, 'Tất cả']];
      options.layout = {
        topStart: 'pageLength',
        topEnd: 'search',
        bottomStart: 'info',
        bottomEnd: 'paging'
      };
    }

    $(table).DataTable(options);

    var wrap = table.closest('.admin-table-wrap');
    if (wrap) wrap.classList.add('admin-datatable-wrap');
    table.classList.add('admin-datatable');
  }

  function boot() {
    if ($ && $.fn.DataTable) {
      document.querySelectorAll('table.js-datatable').forEach(function (table) {
        if (!$.fn.DataTable.isDataTable(table)) initTable(table);
      });
    }

    document.querySelectorAll('.admin-table-wrap, .visit-chart').forEach(function (wrap) {
      var scroller = wrap.querySelector('.dt-layout-table table')?.parentElement || wrap;
      if (scroller.dataset.scrollHint) return;
      scroller.dataset.scrollHint = 'ready';
      var hint = document.createElement('p');
      hint.className = 'admin-table-scroll-hint';
      hint.textContent = wrap.matches('.visit-chart') ? 'Cuộn ngang để xem thêm ngày.' : 'Cuộn ngang để xem các cột còn lại.';
      wrap.before(hint);

      function updateScrollHint() {
        var overflow = scroller.scrollWidth > scroller.clientWidth + 1;
        hint.hidden = !overflow;
        if (overflow) {
          scroller.setAttribute('tabindex', '0');
          if (scroller !== wrap || !wrap.matches('.visit-chart')) {
            scroller.setAttribute('role', 'region');
            scroller.setAttribute('aria-label', 'Bảng có thể cuộn ngang');
          }
        } else {
          scroller.removeAttribute('tabindex');
          if (scroller.getAttribute('role') === 'region') {
            scroller.removeAttribute('role');
            scroller.removeAttribute('aria-label');
          }
        }
      }

      updateScrollHint();
      if ('ResizeObserver' in window) {
        var observer = new ResizeObserver(updateScrollHint);
        scrollObservers.set(wrap, observer);
        observer.observe(scroller);
        var table = scroller.querySelector('table');
        if (table) observer.observe(table);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  function destroy(root) {
    root.querySelectorAll('.admin-table-wrap, .visit-chart').forEach(function (wrap) {
      scrollObservers.get(wrap)?.disconnect();
    });
    if ($ && $.fn.DataTable) {
      root.querySelectorAll('table.js-datatable').forEach(function (table) {
        if ($.fn.DataTable.isDataTable(table)) $(table).DataTable().destroy();
      });
    }
  }

  window.TGDataTable = { init: boot, destroy: destroy };
})();
