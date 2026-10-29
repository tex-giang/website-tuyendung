/**
 * TEX-GIANG RECRUITMENT PORTAL - ADMIN PORTAL JAVASCRIPT
 * Handles admin sidebar collapse, mobile drawer, table filtering, status updates, and mock actions.
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Admin Sidebar Toggle for Mobile & Desktop collapse
  const sidebar = document.getElementById('admin-sidebar');
  const toggleBtn = document.getElementById('toggle-sidebar-btn');
  const overlay = document.getElementById('sidebar-overlay');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
      if (overlay) overlay.classList.toggle('hidden');
    });
  }

  if (overlay && sidebar) {
    overlay.addEventListener('click', () => {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
    });
  }

  // 2. Candidate table quick search
  const candidateSearch = document.getElementById('admin-candidate-search');
  if (candidateSearch) {
    candidateSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      const rows = document.querySelectorAll('#candidate-table-body tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    });
  }

  // 3. Status filter for candidate table
  const statusFilter = document.getElementById('admin-status-filter');
  if (statusFilter) {
    statusFilter.addEventListener('change', function () {
      const val = this.value.toLowerCase();
      const rows = document.querySelectorAll('#candidate-table-body tr');
      rows.forEach(row => {
        const statusBadge = row.querySelector('.candidate-status-badge');
        if (!val || (statusBadge && statusBadge.textContent.toLowerCase().includes(val))) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  }
});

/**
 * Admin Mock Modal Actions
 */
function openStatusModal(candidateName, currentStatus) {
  const modal = document.getElementById('status-update-modal');
  const nameElem = document.getElementById('modal-candidate-name');
  if (nameElem) nameElem.textContent = candidateName;
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
}

function closeStatusModal() {
  const modal = document.getElementById('status-update-modal');
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}

function saveCandidateStatus() {
  const select = document.getElementById('modal-status-select');
  const statusText = select ? select.options[select.selectedIndex].text : 'Cập nhật';
  showToast(`Đã cập nhật trạng thái ứng viên thành: "${statusText}" [Demo Mode]`, 'success');
  closeStatusModal();
}

function confirmDeleteJob(jobTitle) {
  if (confirm(`Bạn có chắc chắn muốn xóa tin tuyển dụng "${jobTitle}"? Thao tác này sẽ ẩn tin khỏi website.`)) {
    showToast(`Đã xóa tin "${jobTitle}" khỏi hệ thống [Demo Mode]!`, 'success');
  }
}

function toggleJobStatus(btn, jobTitle) {
  const isClosing = btn.textContent.includes('Đóng');
  if (isClosing) {
    btn.innerHTML = '<i class="bi bi-unlock"></i> Mở lại';
    btn.className = 'px-2.5 py-1 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg font-bold text-xs';
    showToast(`Đã đóng tuyển dụng cho tin "${jobTitle}". Ứng viên không thể nộp thêm hồ sơ.`, 'success');
  } else {
    btn.innerHTML = '<i class="bi bi-lock"></i> Đóng tin';
    btn.className = 'px-2.5 py-1 text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg font-bold text-xs';
    showToast(`Đã kích hoạt mở lại tin tuyển dụng "${jobTitle}".`, 'success');
  }
}

function exportCandidatesExcel() {
  showToast('Đang kết xuất tệp Excel danh sách 142 ứng viên... Tải xuống hoàn tất! [Demo]', 'success');
}
